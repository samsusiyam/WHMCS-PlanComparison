<?php
/**
 * Bahari Plan & Tier Comparison - WHMCS Client Area Hooks
 *
 * @package    WHMCS
 * @subpackage AddonModules
 * @author     Bahari Host <admin@baharihost.com>
 * @license    MIT
 * @link       https://baharihost.com
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

if (!function_exists('plancomparison_clean_html')) {
    function plancomparison_clean_html($raw) {
        $clean = html_entity_decode((string)$raw, ENT_QUOTES, 'UTF-8');
        if (strpos($clean, '&lt;') !== false || strpos($clean, '&gt;') !== false) {
            $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');
        }
        return trim($clean);
    }
}

if (!function_exists('plancomparison_is_truthy')) {
    function plancomparison_is_truthy($val) {
        return in_array(strtolower(trim((string)$val)), ['1', 'on', 'true', 'yes'], true);
    }
}

if (!function_exists('plancomparison_hook_get_setting')) {
    function plancomparison_hook_get_setting($setting, $default = '') {
        static $settingsCache = null;
        if ($settingsCache === null) {
            $settingsCache = [];
            try {
                if (Capsule::schema()->hasTable('mod_plancomparison_settings')) {
                    $rows = Capsule::table('mod_plancomparison_settings')->get();
                    foreach ($rows as $r) {
                        $settingsCache[$r->setting] = $r->value;
                    }
                }
            } catch (\Exception $e) {}
        }
        return isset($settingsCache[$setting]) ? $settingsCache[$setting] : $default;
    }
}

if (!function_exists('plancomparison_get_matrix_for_cart')) {
    function plancomparison_get_matrix_for_cart($gid = 0) {
        try {
            if (!Capsule::schema()->hasTable('mod_plancomparison_matrices')) {
                return null;
            }

            $allMatrices = Capsule::table('mod_plancomparison_matrices')
                ->where('enabled', 1)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($allMatrices->isEmpty()) {
                return null;
            }

            // 1. Try to find an exact match for active GID
            if ($gid > 0) {
                foreach ($allMatrices as $m) {
                    $gList = json_decode($m->groups, true);
                    if (is_array($gList) && in_array($gid, $gList)) {
                        return $m;
                    }
                }
            }

            // 2. Fall back to global matrix (groups is empty or contains "all" or 0)
            foreach ($allMatrices as $m) {
                $gList = json_decode($m->groups, true);
                if (empty($gList) || !is_array($gList) || in_array('all', $gList) || in_array(0, $gList)) {
                    return $m;
                }
            }

            // 3. Fallback to the first available matrix
            return $allMatrices->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}

/**
 * 1. ClientAreaHeadOutput Hook: Inject Scoped Styles & FontAwesome / Variables
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    $status = plancomparison_hook_get_setting('status', 'on');
    if (!plancomparison_is_truthy($status)) {
        return '';
    }

    $primaryColor = plancomparison_hook_get_setting('highlight_color', '#1E60F8');
    if (empty($primaryColor)) {
        $primaryColor = '#1E60F8';
    }

    $customCss = plancomparison_clean_html(plancomparison_hook_get_setting('custom_css', ''));

    $css = '
    <style id="pc-comparison-styles">
    :root {
        --pc-primary: ' . htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') . ';
        --pc-primary-soft: rgba(30, 96, 248, 0.08);
        --pc-primary-border: rgba(30, 96, 248, 0.35);
        --pc-amber: #EA580C;
        --pc-amber-soft: rgba(234, 88, 12, 0.1);
        --pc-success: #16A34A;
        --pc-success-bg: #DCFCE7;
        --pc-slate-muted: #64748B;
        --pc-card-bg: #FFFFFF;
        --pc-border-color: #E2E8F0;
        --pc-text-dark: #0F172A;
    }

    /* Container Card */
    .pc-matrix-section {
        width: 100%;
        margin: 45px 0 35px 0;
        clear: both;
        font-family: inherit;
    }

    .pc-matrix-card {
        background: var(--pc-card-bg);
        border: 1px solid var(--pc-border-color);
        border-radius: 24px;
        box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.04), 0 0 1px 1px rgba(0, 0, 0, 0.02);
        padding: 32px 36px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s ease;
    }

    /* Header Row */
    .pc-matrix-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .pc-header-title-box {
        max-width: 420px;
    }

    .pc-matrix-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--pc-text-dark);
        letter-spacing: 0.5px;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        line-height: 1.2;
    }

    .pc-matrix-title .pc-title-icon {
        color: var(--pc-primary);
        font-size: 19px;
    }

    .pc-matrix-subtitle {
        font-size: 13.5px;
        color: var(--pc-slate-muted);
        margin: 0;
        line-height: 1.5;
        font-weight: 400;
    }

    /* Responsive Table Container */
    .pc-table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 10px;
        border-radius: 16px;
    }

    .pc-table-responsive::-webkit-scrollbar {
        height: 6px;
    }
    .pc-table-responsive::-webkit-scrollbar-track {
        background: #F1F5F9;
        border-radius: 3px;
    }
    .pc-table-responsive::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 3px;
    }

    /* Table Grid */
    .pc-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        text-align: left;
        min-width: 680px;
    }

    /* Column Sizing */
    .pc-table th, .pc-table td {
        padding: 16px 20px;
        vertical-align: middle;
        font-size: 14px;
        border-bottom: 1px solid #F1F5F9;
    }

    .pc-col-feature {
        width: 32%;
        min-width: 200px;
    }

    .pc-col-tier {
        width: calc(68% / 3);
        min-width: 155px;
        text-align: center;
    }

    /* Tier Headers */
    .pc-tier-header {
        position: relative;
        padding: 20px 16px 22px 16px !important;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
        background: #F8FAFC;
        border-bottom: 2px solid #E2E8F0 !important;
    }

    /* Highlighted / Pro Tier Column */
    .pc-col-highlighted {
        background: #F8FAFF !important;
        position: relative;
    }

    .pc-tier-header.pc-col-highlighted {
        background: #EEF4FF !important;
        border-top: 2px solid var(--pc-primary);
        border-left: 2px solid var(--pc-primary-border);
        border-right: 2px solid var(--pc-primary-border);
        border-bottom: 2px solid var(--pc-primary) !important;
        box-shadow: 0 -4px 18px rgba(30, 96, 248, 0.08);
    }

    .pc-cell-highlighted {
        background: #FAF5FF;
        background: rgba(30, 96, 248, 0.02) !important;
        border-left: 1px solid rgba(30, 96, 248, 0.15);
        border-right: 1px solid rgba(30, 96, 248, 0.15);
    }

    /* Pill Badges */
    .pc-badge-wrap {
        min-height: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 8px;
    }

    .pc-pill-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 9999px;
        line-height: 1;
        white-space: nowrap;
    }

    .pc-badge-blue {
        background: #1E293B;
        color: #FFFFFF;
        box-shadow: 0 2px 6px rgba(30, 41, 59, 0.2);
    }

    .pc-badge-blue-primary {
        background: var(--pc-primary);
        color: #FFFFFF;
        box-shadow: 0 2px 8px rgba(30, 96, 248, 0.35);
    }

    .pc-badge-amber {
        background: #FFF7ED;
        color: #EA580C;
        border: 1px solid #FFEDD5;
        font-weight: 800;
    }

    .pc-badge-amber-solid {
        background: #EA580C;
        color: #FFFFFF;
        box-shadow: 0 2px 8px rgba(234, 88, 12, 0.3);
    }

    .pc-badge-green {
        background: #DCFCE7;
        color: #16A34A;
    }

    /* Tier Titles */
    .pc-tier-name {
        font-size: 19px;
        font-weight: 800;
        color: var(--pc-text-dark);
        margin: 0 0 4px 0;
        line-height: 1.2;
    }

    .pc-tier-name.pc-is-popular {
        color: var(--pc-primary);
    }

    .pc-tier-tagline {
        font-size: 12.5px;
        color: var(--pc-slate-muted);
        margin: 0;
        line-height: 1.3;
        font-weight: 500;
    }

    /* Spec Rows */
    .pc-spec-row:hover td {
        background-color: #F8FAFC;
    }

    .pc-spec-row:hover .pc-cell-highlighted {
        background-color: #EEF4FF !important;
    }

    /* Feature Label Cell */
    .pc-feature-cell-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .pc-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #F1F5F9;
        border: 1px solid #E2E8F0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        font-size: 14px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .pc-spec-row:hover .pc-icon-box {
        transform: scale(1.06);
        background: #E2E8F0;
        color: var(--pc-primary);
    }

    .pc-feature-label-text {
        font-weight: 600;
        color: #1E293B;
        font-size: 14px;
    }

    /* Tooltip */
    .pc-tooltip-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
    }

    .pc-info-icon {
        color: #94A3B8;
        font-size: 13px;
        margin-left: 5px;
        transition: color 0.15s ease;
    }

    .pc-tooltip-wrapper:hover .pc-info-icon {
        color: var(--pc-primary);
    }

    .pc-tooltip-bubble {
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        background: #0F172A;
        color: #F8FAFC;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 400;
        line-height: 1.35;
        white-space: normal;
        min-width: 170px;
        max-width: 240px;
        text-align: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.25);
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 1000;
    }

    .pc-tooltip-bubble::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 5px;
        border-style: solid;
        border-color: #0F172A transparent transparent transparent;
    }

    .pc-tooltip-wrapper:hover .pc-tooltip-bubble {
        opacity: 1;
        visibility: visible;
    }

    /* Value Display Elements */
    .pc-val-box {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .pc-val-text {
        font-size: 13.5px;
        font-weight: 500;
        color: #334155;
    }

    .pc-cell-highlighted .pc-val-text {
        font-weight: 700;
        color: var(--pc-text-dark);
    }

    .pc-val-check {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #DCFCE7;
        color: #16A34A;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
    }

    .pc-val-dash {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #F1F5F9;
        color: #94A3B8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
    }

    /* CTA Bottom Actions */
    .pc-tier-footer {
        padding: 20px 16px !important;
        border-bottom: none !important;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
        background: #F8FAFC;
    }

    .pc-tier-footer.pc-col-highlighted {
        background: #EEF4FF !important;
        border-left: 2px solid var(--pc-primary-border);
        border-right: 2px solid var(--pc-primary-border);
        border-bottom: 2px solid var(--pc-primary) !important;
    }

    .pc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 9999px;
        text-decoration: none !important;
        transition: all 0.2s ease;
        width: 85%;
        max-width: 160px;
        line-height: 1.2;
    }

    .pc-btn-primary {
        background: var(--pc-primary);
        color: #FFFFFF !important;
        box-shadow: 0 4px 12px rgba(30, 96, 248, 0.25);
        border: 1px solid transparent;
    }

    .pc-btn-primary:hover {
        background: #174EC4;
        box-shadow: 0 6px 16px rgba(30, 96, 248, 0.35);
        transform: translateY(-1px);
    }

    .pc-btn-outline {
        background: #FFFFFF;
        color: #334155 !important;
        border: 1.5px solid #CBD5E1;
    }

    .pc-btn-outline:hover {
        border-color: var(--pc-primary);
        color: var(--pc-primary) !important;
        background: #F8FAFC;
        transform: translateY(-1px);
    }

    /* Mobile Responsive Adaptation */
    @media (max-width: 768px) {
        .pc-matrix-card {
            padding: 20px 16px;
            border-radius: 18px;
        }
        .pc-matrix-title {
            font-size: 18px;
        }
        .pc-table th, .pc-table td {
            padding: 12px 14px;
        }
        .pc-col-feature {
            position: sticky;
            left: 0;
            background: #FFFFFF;
            z-index: 2;
            box-shadow: 2px 0 5px rgba(0,0,0,0.04);
        }
    }

    ' . $customCss . '
    </style>
    ';

    return $css;
});

/**
 * 2. ClientAreaFooterOutput Hook: Fetch Matrix & Auto-Inject into Storefront DOM
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $status = plancomparison_hook_get_setting('status', 'on');
    if (!plancomparison_is_truthy($status)) {
        return '';
    }

    // Determine current group ID (GID)
    $gid = 0;
    if (isset($vars['gid']) && (int)$vars['gid'] > 0) {
        $gid = (int)$vars['gid'];
    } elseif (isset($_GET['gid']) && (int)$_GET['gid'] > 0) {
        $gid = (int)$_GET['gid'];
    } elseif (isset($vars['productGroup']) && is_object($vars['productGroup']) && isset($vars['productGroup']->id)) {
        $gid = (int)$vars['productGroup']->id;
    }

    // Only inject on cart / store order pages
    $template = isset($vars['templatefile']) ? $vars['templatefile'] : '';
    $filename = isset($vars['filename']) ? $vars['filename'] : '';

    $isCartPage = ($filename === 'cart' || strpos($template, 'cart') !== false || strpos($template, 'products') !== false || $gid > 0);
    if (!$isCartPage) {
        return '';
    }

    $matrix = plancomparison_get_matrix_for_cart($gid);
    if (!$matrix) {
        return '';
    }

    $title = !empty($matrix->title) ? plancomparison_clean_html($matrix->title) : '⚡ KEY SPECS';
    $subtitle = !empty($matrix->subtitle) ? plancomparison_clean_html($matrix->subtitle) : 'Compare hardware capabilities across all tiers.';

    $tiers = json_decode($matrix->tiers_data, true);
    $specs = json_decode($matrix->specs_data, true);

    if (!is_array($tiers) || empty($tiers) || !is_array($specs) || empty($specs)) {
        return '';
    }

    // Generate table HTML
    $html = '<div id="pc-matrix-container" class="pc-matrix-section">';
    $html .= '  <div class="pc-matrix-card">';

    // Section Header
    $html .= '    <div class="pc-matrix-header">';
    $html .= '      <div class="pc-header-title-box">';
    $html .= '        <div class="pc-matrix-title">';
    if (strpos($title, '⚡') === false) {
        $html .= '          <span class="pc-title-icon"><i class="fas fa-bolt"></i></span> ';
    }
    $html .= '          ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $html .= '        </div>';
    $html .= '        <p class="pc-matrix-subtitle">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</p>';
    $html .= '      </div>';
    $html .= '    </div>';

    // Responsive Table
    $html .= '    <div class="pc-table-responsive">';
    $html .= '      <table class="pc-table">';

    // Thead / Columns
    $html .= '        <thead><tr>';
    $html .= '          <th class="pc-col-feature"></th>';

    foreach ($tiers as $t) {
        $tId = isset($t['id']) ? $t['id'] : '';
        $tName = isset($t['name']) ? $t['name'] : 'Tier';
        $tTagline = isset($t['tagline']) ? $t['tagline'] : '';
        $tBadge = isset($t['badge']) ? trim($t['badge']) : '';
        $tBadgeColor = isset($t['badge_color']) ? $t['badge_color'] : 'blue';
        $isHighlight = !empty($t['highlight']);

        $thClasses = 'pc-col-tier';
        if ($isHighlight) {
            $thClasses .= ' pc-col-highlighted';
        }

        $html .= '          <th class="' . $thClasses . ' pc-tier-header">';
        $html .= '            <div class="pc-badge-wrap">';
        if (!empty($tBadge)) {
            $bClass = 'pc-badge-blue';
            if ($tBadgeColor === 'amber' || stripos($tBadge, 'ultimate') !== false) {
                $bClass = 'pc-badge-amber';
            } elseif ($tBadgeColor === 'blue_primary' || stripos($tBadge, 'popular') !== false) {
                $bClass = 'pc-badge-blue';
            } elseif ($tBadgeColor === 'green') {
                $bClass = 'pc-badge-green';
            }
            $html .= '              <span class="pc-pill-badge ' . $bClass . '">' . htmlspecialchars($tBadge, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '            </div>';

        $nameClasses = 'pc-tier-name';
        if ($isHighlight) {
            $nameClasses .= ' pc-is-popular';
        }
        $html .= '            <div class="' . $nameClasses . '">' . htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') . '</div>';
        if (!empty($tTagline)) {
            $html .= '            <div class="pc-tier-tagline">' . htmlspecialchars($tTagline, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '          </th>';
    }
    $html .= '        </tr></thead>';

    // Tbody / Rows
    $html .= '        <tbody>';
    foreach ($specs as $s) {
        $label = isset($s['label']) ? $s['label'] : '';
        $icon = isset($s['icon']) ? $s['icon'] : 'fa-microchip';
        $tooltip = isset($s['tooltip']) ? $s['tooltip'] : '';
        $values = isset($s['values']) && is_array($s['values']) ? $s['values'] : [];

        $html .= '        <tr class="pc-spec-row">';

        // Feature Label Cell
        $html .= '          <td class="pc-col-feature">';
        $html .= '            <div class="pc-feature-cell-content">';
        $html .= '              <div class="pc-icon-box">';
        if (strpos($icon, 'fa-') !== false) {
            $html .= '                <i class="fas ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i>';
        } else {
            $html .= '                <i class="fas fa-check"></i>';
        }
        $html .= '              </div>';
        $html .= '              <span class="pc-feature-label-text">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';

        if (!empty($tooltip)) {
            $html .= '              <div class="pc-tooltip-wrapper">';
            $html .= '                <i class="fas fa-info-circle pc-info-icon"></i>';
            $html .= '                <div class="pc-tooltip-bubble">' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '</div>';
            $html .= '              </div>';
        }
        $html .= '            </div>';
        $html .= '          </td>';

        // Tier Value Cells
        foreach ($tiers as $t) {
            $tId = isset($t['id']) ? $t['id'] : '';
            $isHighlight = !empty($t['highlight']);
            $val = isset($values[$tId]) ? trim((string)$values[$tId]) : '—';

            $tdClasses = 'pc-col-tier';
            if ($isHighlight) {
                $tdClasses .= ' pc-cell-highlighted';
            }

            $html .= '          <td class="' . $tdClasses . '">';
            $html .= '            <div class="pc-val-box">';

            $valLow = strtolower($val);
            if (in_array($valLow, ['yes', 'true', '1', '✓', 'enabled'])) {
                $html .= '              <span class="pc-val-check"><i class="fas fa-check"></i></span>';
            } elseif (in_array($valLow, ['no', 'false', '0', '—', '-', 'none', 'disabled'])) {
                $html .= '              <span class="pc-val-dash">—</span>';
            } else {
                $html .= '              <span class="pc-val-text">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</span>';
            }

            $html .= '            </div>';
            $html .= '          </td>';
        }
        $html .= '        </tr>';
    }

    // Tfoot / CTA Buttons Row
    $html .= '        <tr class="pc-spec-footer-row">';
    $html .= '          <td class="pc-col-feature" style="border-bottom:none;"></td>';
    foreach ($tiers as $t) {
        $btnText = !empty($t['button_text']) ? $t['button_text'] : 'View Plans';
        $btnLink = !empty($t['button_link']) ? $t['button_link'] : '#';
        $isHighlight = !empty($t['highlight']);

        $tdClasses = 'pc-col-tier pc-tier-footer';
        if ($isHighlight) {
            $tdClasses .= ' pc-col-highlighted';
        }

        $btnClass = $isHighlight ? 'pc-btn pc-btn-primary' : 'pc-btn pc-btn-outline';

        $html .= '          <td class="' . $tdClasses . '">';
        $html .= '            <a href="' . htmlspecialchars($btnLink, ENT_QUOTES, 'UTF-8') . '" class="' . $btnClass . '">' . htmlspecialchars($btnText, ENT_QUOTES, 'UTF-8') . '</a>';
        $html .= '          </td>';
    }
    $html .= '        </tr>';

    $html .= '        </tbody>';
    $html .= '      </table>';
    $html .= '    </div>'; // .pc-table-responsive
    $html .= '  </div>'; // .pc-matrix-card
    $html .= '</div>'; // #pc-matrix-container

    // Client-side DOM Injection Javascript
    $placement = plancomparison_hook_get_setting('placement', 'below_features');

    $encodedHtml = json_encode($html);

    $js = '
    <script id="pc-injector-script">
    (function() {
        function injectPlanComparison() {
            if (document.getElementById("pc-matrix-container")) return;

            var htmlContent = ' . $encodedHtml . ';
            var tempDiv = document.createElement("div");
            tempDiv.innerHTML = htmlContent;
            var node = tempDiv.firstElementChild;
            if (!node) return;

            var placementMode = ' . json_encode($placement) . ';

            // Target Priority:
            // 1. Below Advanced Features / Category wrapper if exists
            var featureWrapper = document.querySelector(".fd-category-wrapper") ||
                                  document.querySelector(".category-features-wrapper") ||
                                  document.querySelector(".section-features") ||
                                  document.querySelector(".features-wrapper");

            // 2. Below Products / Packages grid container
            var productsGrid = document.querySelector("#products") ||
                               document.querySelector(".section.products") ||
                               document.querySelector(".packages-list") ||
                               document.querySelector(".products-row") ||
                               document.querySelector(".row-eq-height") ||
                               (document.querySelector(".package") ? document.querySelector(".package").closest(".row") : null);

            var mainContent = document.querySelector(".main-content") ||
                              document.querySelector(".cart-body") ||
                              document.querySelector(".order-form-body");

            var target = null;
            if (placementMode === "below_features" && featureWrapper) {
                target = featureWrapper;
            } else if (placementMode === "below_packages" && productsGrid) {
                target = productsGrid;
            } else if (featureWrapper) {
                target = featureWrapper;
            } else if (productsGrid) {
                target = productsGrid;
            } else if (mainContent) {
                target = mainContent;
            }

            if (target && target.parentNode) {
                target.parentNode.insertBefore(node, target.nextSibling);
            } else if (document.body) {
                document.body.appendChild(node);
            }
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", injectPlanComparison);
        } else {
            injectPlanComparison();
        }
        setTimeout(injectPlanComparison, 300);
        setTimeout(injectPlanComparison, 1200);
    })();
    </script>
    ';

    return $js;
});
