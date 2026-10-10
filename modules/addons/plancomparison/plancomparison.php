<?php
/**
 * Bahari Plan & Tier Comparison - WHMCS Addon Module
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

if (!function_exists('plancomparison_config')) {
    function plancomparison_config() {
        return [
            'name'        => 'Bahari Plan & Tier Comparison',
            'description' => 'Interactive Hardware Capability & Tier Comparison Matrix for WHMCS Cart & Order Forms',
            'version'     => '1.0.0',
            'author'      => 'Bahari Host',
            'fields'      => [
                'status' => [
                    'FriendlyName' => 'Module Status',
                    'Type'         => 'yesno',
                    'Description'  => 'Enable or disable comparison matrices on order forms.',
                    'Default'      => 'on',
                ],
            ],
        ];
    }
}

if (!function_exists('plancomparison_ensure_tables')) {
    function plancomparison_ensure_tables() {
        // 1. Settings Table
        if (!Capsule::schema()->hasTable('mod_plancomparison_settings')) {
            Capsule::schema()->create('mod_plancomparison_settings', function ($table) {
                $table->increments('id');
                $table->string('setting', 100)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            $defaults = [
                'status'          => 'on',
                'placement'       => 'below_features',
                'theme'           => 'modern_white',
                'highlight_color' => '#1E60F8',
                'custom_css'      => '',
            ];
            foreach ($defaults as $k => $v) {
                Capsule::table('mod_plancomparison_settings')->insert([
                    'setting'    => $k,
                    'value'      => $v,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Matrices Table
        if (!Capsule::schema()->hasTable('mod_plancomparison_matrices')) {
            Capsule::schema()->create('mod_plancomparison_matrices', function ($table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('title', 255)->default('⚡ KEY SPECS');
                $table->string('subtitle', 255)->default('Compare hardware capabilities across all tiers.');
                $table->text('groups')->nullable();
                $table->longText('tiers_data')->nullable();
                $table->longText('specs_data')->nullable();
                $table->tinyInteger('enabled')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Seed initial matrix matching Bahari Host tiers (Budget vs Pro vs Turbo)
            $defaultTiers = [
                [
                    'id'          => 'budget',
                    'name'        => 'Budget',
                    'tagline'     => 'For Personal Sites',
                    'badge'       => '',
                    'badge_color' => 'default',
                    'highlight'   => 0,
                    'button_text' => 'View Plans',
                    'button_link' => 'cart.php?gid=2',
                ],
                [
                    'id'          => 'pro',
                    'name'        => 'Pro',
                    'tagline'     => 'For Growing Business',
                    'badge'       => 'MOST POPULAR',
                    'badge_color' => 'blue',
                    'highlight'   => 1,
                    'button_text' => 'View Plans',
                    'button_link' => 'cart.php?gid=3',
                ],
                [
                    'id'          => 'turbo',
                    'name'        => 'Turbo',
                    'tagline'     => 'For High Traffic',
                    'badge'       => 'ULTIMATE POWER',
                    'badge_color' => 'amber',
                    'highlight'   => 0,
                    'button_text' => 'View Plans',
                    'button_link' => 'cart.php?gid=20',
                ],
            ];

            $defaultSpecs = [
                [
                    'id'      => 'spec_cpu',
                    'label'   => 'Processor',
                    'icon'    => 'fa-microchip',
                    'tooltip' => 'Server CPU generation, core clock and architecture',
                    'values'  => [
                        'budget' => 'Intel XEON',
                        'pro'    => 'Ryzen 9 (4.5Ghz)',
                        'turbo'  => 'Ryzen 9 (5.7Ghz)',
                    ],
                ],
                [
                    'id'      => 'spec_ram',
                    'label'   => 'RAM Speed',
                    'icon'    => 'fa-heartbeat',
                    'tooltip' => 'Memory technology and bus frequency',
                    'values'  => [
                        'budget' => 'DDR4 2400Mhz',
                        'pro'    => 'DDR4 3200Mhz',
                        'turbo'  => 'DDR5 6000Mhz',
                    ],
                ],
                [
                    'id'      => 'spec_storage',
                    'label'   => 'Storage Tech',
                    'icon'    => 'fa-hdd',
                    'tooltip' => 'Fast enterprise NVMe SSD generation',
                    'values'  => [
                        'budget' => 'NVMe Gen3',
                        'pro'    => 'NVMe Gen4',
                        'turbo'  => 'NVMe Gen5',
                    ],
                ],
                [
                    'id'      => 'spec_resources',
                    'label'   => 'Resources',
                    'icon'    => 'fa-server',
                    'tooltip' => 'Guaranteed hardware resource isolation model',
                    'values'  => [
                        'budget' => 'Shared CPU/RAM',
                        'pro'    => 'Shared CPU/RAM',
                        'turbo'  => 'Dedicated Resources',
                    ],
                ],
                [
                    'id'      => 'spec_lb',
                    'label'   => 'Load Balancer',
                    'icon'    => 'fa-shield-alt',
                    'tooltip' => 'Automated high availability traffic distribution',
                    'values'  => [
                        'budget' => 'no',
                        'pro'    => 'no',
                        'turbo'  => 'yes',
                    ],
                ],
                [
                    'id'      => 'spec_net',
                    'label'   => 'Network Speed',
                    'icon'    => 'fa-network-wired',
                    'tooltip' => 'Server uplink bandwidth port speed',
                    'values'  => [
                        'budget' => '500 Mbps',
                        'pro'    => '1 Gbps',
                        'turbo'  => '10 Gbps',
                    ],
                ],
            ];

            Capsule::table('mod_plancomparison_matrices')->insert([
                'name'        => 'Hardware Capabilities Comparison (Budget vs Pro vs Turbo)',
                'title'       => '⚡ KEY SPECS',
                'subtitle'    => 'Compare hardware capabilities across all tiers.',
                'groups'      => json_encode([2, 3, 20]),
                'tiers_data'  => json_encode($defaultTiers),
                'specs_data'  => json_encode($defaultSpecs),
                'enabled'     => 1,
                'sort_order'  => 0,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

if (!function_exists('plancomparison_activate')) {
    function plancomparison_activate() {
        plancomparison_ensure_tables();
        return ['status' => 'success', 'description' => 'Bahari Plan & Tier Comparison activated successfully!'];
    }
}

if (!function_exists('plancomparison_deactivate')) {
    function plancomparison_deactivate() {
        return ['status' => 'success', 'description' => 'Bahari Plan & Tier Comparison deactivated successfully.'];
    }
}

if (!function_exists('plancomparison_upgrade')) {
    function plancomparison_upgrade($vars) {
        plancomparison_ensure_tables();
    }
}

if (!function_exists('plancomparison_get_setting')) {
    function plancomparison_get_setting($setting, $default = '') {
        $row = Capsule::table('mod_plancomparison_settings')->where('setting', $setting)->first();
        return $row ? $row->value : $default;
    }
}

if (!function_exists('plancomparison_save_setting')) {
    function plancomparison_save_setting($setting, $value) {
        Capsule::table('mod_plancomparison_settings')->updateOrInsert(
            ['setting' => $setting],
            ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }
}

if (!function_exists('plancomparison_h')) {
    function plancomparison_h($val) {
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('plancomparison_output')) {
    function plancomparison_output($vars) {
        plancomparison_ensure_tables();

        $moduleLink = $vars['modulelink'];
        $action = isset($_GET['action']) ? $_GET['action'] : 'matrices';

        // 1. Handle Save Settings POST
        if ($action === 'save_display_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            plancomparison_save_setting('status', isset($_POST['status']) ? 'on' : 'off');
            plancomparison_save_setting('placement', trim($_POST['placement']));
            plancomparison_save_setting('highlight_color', trim($_POST['highlight_color']));
            plancomparison_save_setting('custom_css', trim($_POST['custom_css']));

            header('Location: ' . $moduleLink . '&action=display_settings&saved=1');
            exit;
        }

        // 2. Handle Save Matrix POST
        if ($action === 'save_matrix' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $matrixId = isset($_POST['matrix_id']) ? (int)$_POST['matrix_id'] : 0;
            $name = trim($_POST['name']);
            $title = trim($_POST['title']);
            $subtitle = trim($_POST['subtitle']);
            $enabled = isset($_POST['enabled']) ? 1 : 0;
            $selectedGroups = isset($_POST['groups']) && is_array($_POST['groups']) ? array_map('intval', $_POST['groups']) : [];

            // Parse Tiers
            $rawTiers = isset($_POST['tiers']) && is_array($_POST['tiers']) ? $_POST['tiers'] : [];
            $cleanTiers = [];
            foreach ($rawTiers as $t) {
                if (empty($t['id']) || empty($t['name'])) continue;
                $cleanTiers[] = [
                    'id'          => trim($t['id']),
                    'name'        => trim($t['name']),
                    'tagline'     => trim($t['tagline']),
                    'badge'       => trim($t['badge']),
                    'badge_color' => trim($t['badge_color']),
                    'highlight'   => !empty($t['highlight']) ? 1 : 0,
                    'button_text' => trim($t['button_text']),
                    'button_link' => trim($t['button_link']),
                ];
            }

            // Parse Specs
            $rawSpecs = isset($_POST['specs']) && is_array($_POST['specs']) ? $_POST['specs'] : [];
            $cleanSpecs = [];
            foreach ($rawSpecs as $s) {
                if (empty($s['label'])) continue;
                $sId = !empty($s['id']) ? trim($s['id']) : 'spec_' . uniqid();
                $values = isset($s['values']) && is_array($s['values']) ? $s['values'] : [];
                $cleanSpecs[] = [
                    'id'      => $sId,
                    'label'   => trim($s['label']),
                    'icon'    => trim($s['icon']),
                    'tooltip' => trim($s['tooltip']),
                    'values'  => $values,
                ];
            }

            $matrixData = [
                'name'       => $name,
                'title'      => $title,
                'subtitle'   => $subtitle,
                'groups'     => json_encode($selectedGroups),
                'tiers_data' => json_encode($cleanTiers),
                'specs_data' => json_encode($cleanSpecs),
                'enabled'    => $enabled,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($matrixId > 0) {
                Capsule::table('mod_plancomparison_matrices')->where('id', $matrixId)->update($matrixData);
            } else {
                $matrixData['created_at'] = date('Y-m-d H:i:s');
                $matrixId = Capsule::table('mod_plancomparison_matrices')->insertGetId($matrixData);
            }

            header('Location: ' . $moduleLink . '&action=editor&id=' . $matrixId . '&saved=1');
            exit;
        }

        // 3. Handle Delete Matrix
        if ($action === 'delete_matrix') {
            $delId = (int)$_GET['id'];
            if ($delId > 0) {
                Capsule::table('mod_plancomparison_matrices')->where('id', $delId)->delete();
            }
            header('Location: ' . $moduleLink . '&action=matrices&deleted=1');
            exit;
        }

        // Header CSS & HTML
        echo '
        <style>
        .pc-admin-header {
            background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #2563EB 100%);
            border-radius: 14px;
            padding: 20px 24px;
            color: #FFFFFF;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            box-shadow: 0 10px 25px -5px rgba(30, 58, 138, 0.25);
        }
        .pc-admin-header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .pc-admin-logo-box {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #FFFFFF;
            flex-shrink: 0;
        }
        .pc-admin-title {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            color: #FFFFFF;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        .pc-admin-desc {
            font-size: 13px;
            margin: 3px 0 0 0;
            color: #E2E8F0;
            font-weight: 400;
            line-height: 1.3;
        }
        .pc-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            white-space: nowrap;
        }
        .pc-admin-tabs-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 20px;
            border-bottom: 2px solid #E2E8F0;
        }
        .pc-admin-tabs {
            display: flex;
            gap: 6px;
            padding-bottom: 2px;
            min-width: max-content;
        }
        .pc-admin-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-decoration: none !important;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s ease;
            position: relative;
            bottom: -2px;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        .pc-admin-tab-btn:hover {
            color: #2563EB;
            background: #F8FAFC;
        }
        .pc-admin-tab-btn.active {
            color: #2563EB;
            background: #FFFFFF;
            border-bottom: 2px solid #2563EB;
            font-weight: 700;
        }
        .pc-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }
        .pc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none !important;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .pc-btn-primary {
            background: #2563EB;
            color: #FFFFFF !important;
        }
        .pc-btn-primary:hover {
            background: #1D4ED8;
        }
        .pc-btn-secondary {
            background: #F1F5F9;
            color: #334155 !important;
            border-color: #CBD5E1;
        }
        .pc-btn-secondary:hover {
            background: #E2E8F0;
        }
        .pc-btn-danger {
            background: #EF4444;
            color: #FFFFFF !important;
        }
        .pc-btn-danger:hover {
            background: #DC2626;
        }
        .pc-btn-success {
            background: #10B981;
            color: #FFFFFF !important;
        }
        .pc-btn-success:hover {
            background: #059669;
        }
        .pc-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            font-size: 13.5px;
            box-sizing: border-box;
        }
        .pc-input:focus {
            border-color: #2563EB;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .pc-form-group {
            margin-bottom: 16px;
        }
        .pc-form-label {
            font-weight: 700;
            font-size: 12.5px;
            color: #1E293B;
            margin-bottom: 5px;
            display: block;
        }
        .pc-table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 10px;
        }
        .pc-table-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 600px;
        }
        .pc-table-grid th, .pc-table-grid td {
            padding: 10px 12px;
            border: 1px solid #E2E8F0;
            vertical-align: middle;
        }
        .pc-table-grid th {
            background: #F8FAFC;
            font-weight: 700;
            color: #334155;
        }

        /* Responsive Layout Grid */
        .pc-editor-top-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .pc-editor-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .pc-tiers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        
        /* Sleek In-Module Confirmation Modal */
        .pc-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }
        .pc-modal-backdrop.pc-modal-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .pc-modal-card {
            background: #FFFFFF;
            border-radius: 18px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 440px;
            padding: 28px 24px;
            text-align: center;
            transform: scale(0.92) translateY(8px);
            transition: transform 0.24s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .pc-modal-backdrop.pc-modal-open .pc-modal-card {
            transform: scale(1) translateY(0);
        }
        .pc-modal-icon-wrap {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: #FEE2E2;
            color: #EF4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        .pc-modal-title {
            font-size: 19px;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 8px 0;
        }
        .pc-modal-desc {
            font-size: 13.5px;
            color: #64748B;
            line-height: 1.5;
            margin: 0 0 24px 0;
        }
        .pc-modal-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .pc-modal-actions .pc-btn {
            flex: 1;
            padding: 11px 16px;
            font-size: 13.5px;
            border-radius: 10px;
        }
        @media (max-width: 480px) {
            .pc-modal-actions {
                flex-direction: column-reverse;
            }
            .pc-modal-card {
                padding: 22px 18px;
            }
        }

        /* Mobile Breakpoints */
        @media (max-width: 991px) {
            .pc-editor-top-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .pc-tiers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 680px) {
            .pc-card {
                padding: 16px 12px;
                border-radius: 10px;
            }
            .pc-admin-header {
                padding: 16px;
            }
            .pc-admin-title {
                font-size: 17px;
            }
            .pc-admin-desc {
                font-size: 12px;
            }
            .pc-editor-meta-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .pc-tiers-grid {
                grid-template-columns: 1fr;
            }
            .pc-btn {
                width: 100%;
                margin-top: 6px;
            }
            .pc-card-header-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px;
            }
        }
</style>
        ';

        echo '
        <div class="pc-admin-header">
            <div class="pc-admin-header-brand">
                <div class="pc-admin-logo-box"><i class="fas fa-columns"></i></div>
                <div>
                    <h2 class="pc-admin-title">Bahari Plan & Tier Comparison</h2>
                    <p class="pc-admin-desc">Visual Hardware Capability & Tier Comparison Matrix for WHMCS Cart & Order Forms</p>
                </div>
            </div>
            <div>
                <span class="pc-admin-badge"><i class="fas fa-check-circle"></i> v1.0.0 • Bahari Host</span>
            </div>
        </div>

        <div class="pc-admin-tabs-wrapper"><div class="pc-admin-tabs">
            <a href="' . $moduleLink . '&action=matrices" class="pc-admin-tab-btn ' . ($action === 'matrices' ? 'active' : '') . '"><i class="fas fa-th-list"></i> Matrices</a>
            <a href="' . $moduleLink . '&action=display_settings" class="pc-admin-tab-btn ' . ($action === 'display_settings' ? 'active' : '') . '"><i class="fas fa-sliders-h"></i> Display Settings</a>
            <a href="' . $moduleLink . '&action=integration_guide" class="pc-admin-tab-btn ' . ($action === 'integration_guide' ? 'active' : '') . '"><i class="fas fa-code"></i> Integration Guide</a>
        </div></div>
        ';

        // 1. Matrices List
        if ($action === 'matrices') {
            $matrices = Capsule::table('mod_plancomparison_matrices')->orderBy('sort_order', 'asc')->get();

            echo '<div class="pc-card">';
            echo '  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">';
            echo '    <div>';
            echo '      <h3 style="margin:0 0 4px 0; font-size:17px; font-weight:800; color:#0F172A;">Configured Comparison Matrices</h3>';
            echo '      <p style="margin:0; font-size:13px; color:#64748B;">Manage comparison tables rendered across product groups.</p>';
            echo '    </div>';
            echo '    <a href="' . $moduleLink . '&action=editor&id=0" class="pc-btn pc-btn-primary"><i class="fas fa-plus"></i> Create New Matrix</a>';
            echo '  </div>';

            if ($matrices->isEmpty()) {
                echo '<p style="padding:24px; text-align:center; color:#64748B;">No comparison matrices found. Click "Create New Matrix" to build one!</p>';
            } else {
                echo '<div class="pc-table-responsive"><table class="pc-table-grid">';
                echo '<thead><tr>';
                echo '  <th style="width:40px;">#</th>';
                echo '  <th>Matrix Name</th>';
                echo '  <th>Title & Subtitle</th>';
                echo '  <th>Target Product Groups</th>';
                echo '  <th style="width:90px; text-align:center;">Status</th>';
                echo '  <th style="width:160px; text-align:center;">Actions</th>';
                echo '</tr></thead><tbody>';

                foreach ($matrices as $m) {
                    $groups = json_decode($m->groups, true);
                    $groupLabels = [];
                    if (empty($groups) || in_array('all', $groups)) {
                        $groupLabels[] = '<span style="background:#EEF2FF; color:#4F46E5; padding:2px 8px; border-radius:4px; font-size:11.5px; font-weight:600;">All Product Groups</span>';
                    } else {
                        foreach ($groups as $gid) {
                            $grp = Capsule::table('tblproductgroups')->where('id', $gid)->first();
                            $gName = $grp ? $grp->name : 'GID: ' . $gid;
                            $groupLabels[] = '<span style="background:#F1F5F9; color:#334155; padding:2px 8px; border-radius:4px; font-size:11.5px;">' . plancomparison_h($gName) . '</span>';
                        }
                    }

                    echo '<tr>';
                    echo '  <td>' . (int)$m->id . '</td>';
                    echo '  <td><strong style="color:#0F172A;">' . plancomparison_h($m->name) . '</strong></td>';
                    echo '  <td><div><strong style="color:#2563EB;">' . plancomparison_h($m->title) . '</strong></div><span style="font-size:12px; color:#64748B;">' . plancomparison_h($m->subtitle) . '</span></td>';
                    echo '  <td>' . implode(' ', $groupLabels) . '</td>';
                    echo '  <td style="text-align:center;">' . ($m->enabled ? '<span style="color:#10B981; font-weight:700;"><i class="fas fa-check-circle"></i> Active</span>' : '<span style="color:#94A3B8;">Disabled</span>') . '</td>';
                    echo '  <td style="text-align:center;">';
                    echo '    <a href="' . $moduleLink . '&action=editor&id=' . (int)$m->id . '" class="pc-btn pc-btn-secondary" style="padding:4px 10px; font-size:12px;"><i class="fas fa-edit"></i> Edit</a> ';
                    echo '    <button type="button" class="pc-btn pc-btn-danger" style="padding:4px 8px; font-size:12px;" onclick="pcOpenDeleteModal(\'' . $moduleLink . '&action=delete_matrix&id=' . (int)$m->id . '\', \'' . addslashes(plancomparison_h($m->name)) . '\')"><i class="fas fa-trash"></i></button>';
                    echo '  </td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            echo '</div>';
        }

        // 2. Visual Editor
        if ($action === 'editor') {
            $matrixId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $matrix = $matrixId > 0 ? Capsule::table('mod_plancomparison_matrices')->where('id', $matrixId)->first() : null;

            $name = $matrix ? $matrix->name : 'Hardware Capabilities Comparison';
            $title = $matrix ? $matrix->title : '⚡ KEY SPECS';
            $subtitle = $matrix ? $matrix->subtitle : 'Compare hardware capabilities across all tiers.';
            $enabled = $matrix ? $matrix->enabled : 1;
            $selectedGroups = $matrix ? json_decode($matrix->groups, true) : [2, 3, 20];
            if (!is_array($selectedGroups)) $selectedGroups = [];

            $tiers = $matrix ? json_decode($matrix->tiers_data, true) : [
                ['id' => 'budget', 'name' => 'Budget', 'tagline' => 'For Personal Sites', 'badge' => '', 'badge_color' => 'blue', 'highlight' => 0, 'button_text' => 'View Plans', 'button_link' => 'cart.php?gid=2'],
                ['id' => 'pro', 'name' => 'Pro', 'tagline' => 'For Growing Business', 'badge' => 'MOST POPULAR', 'badge_color' => 'blue', 'highlight' => 1, 'button_text' => 'View Plans', 'button_link' => 'cart.php?gid=3'],
                ['id' => 'turbo', 'name' => 'Turbo', 'tagline' => 'For High Traffic', 'badge' => 'ULTIMATE POWER', 'badge_color' => 'amber', 'highlight' => 0, 'button_text' => 'View Plans', 'button_link' => 'cart.php?gid=20'],
            ];

            $specs = $matrix ? json_decode($matrix->specs_data, true) : [
                ['id' => 'spec_cpu', 'label' => 'Processor', 'icon' => 'fa-microchip', 'tooltip' => 'Server CPU generation, core clock and architecture', 'values' => ['budget' => 'Intel XEON', 'pro' => 'Ryzen 9 (4.5Ghz)', 'turbo' => 'Ryzen 9 (5.7Ghz)']],
                ['id' => 'spec_ram', 'label' => 'RAM Speed', 'icon' => 'fa-heartbeat', 'tooltip' => 'Memory technology and bus frequency', 'values' => ['budget' => 'DDR4 2400Mhz', 'pro' => 'DDR4 3200Mhz', 'turbo' => 'DDR5 6000Mhz']],
                ['id' => 'spec_storage', 'label' => 'Storage Tech', 'icon' => 'fa-hdd', 'tooltip' => 'Fast enterprise NVMe SSD generation', 'values' => ['budget' => 'NVMe Gen3', 'pro' => 'NVMe Gen4', 'turbo' => 'NVMe Gen5']],
                ['id' => 'spec_resources', 'label' => 'Resources', 'icon' => 'fa-server', 'tooltip' => 'Guaranteed hardware resource isolation model', 'values' => ['budget' => 'Shared CPU/RAM', 'pro' => 'Shared CPU/RAM', 'turbo' => 'Dedicated Resources']],
                ['id' => 'spec_lb', 'label' => 'Load Balancer', 'icon' => 'fa-shield-alt', 'tooltip' => 'Automated high availability traffic distribution', 'values' => ['budget' => 'no', 'pro' => 'no', 'turbo' => 'yes']],
                ['id' => 'spec_net', 'label' => 'Network Speed', 'icon' => 'fa-network-wired', 'tooltip' => 'Server uplink bandwidth port speed', 'values' => ['budget' => '500 Mbps', 'pro' => '1 Gbps', 'turbo' => '10 Gbps']],
            ];

            $allGroups = Capsule::table('tblproductgroups')->select('id', 'name')->orderBy('order', 'asc')->get();
            $allProducts = Capsule::table('tblproducts')->select('id', 'gid', 'name')->where('hidden', 0)->orderBy('order', 'asc')->get();

            // Library of 25+ Industry Standard Specs
            $specLibrary = [
                'Compute & Processor' => [
                    ['label' => 'Processor', 'icon' => 'fa-microchip', 'tooltip' => 'CPU generation and base/boost frequencies', 'def' => 'Intel Xeon / AMD Ryzen 9'],
                    ['label' => 'CPU Cores Allocation', 'icon' => 'fa-layer-group', 'tooltip' => 'Dedicated or virtual CPU core count allocated', 'def' => '1 Core / 2 Cores / 4 Cores'],
                    ['label' => 'Turbo Boost Technology', 'icon' => 'fa-bolt', 'tooltip' => 'Max boosted turbo clock capability', 'def' => 'Up to 5.7 GHz'],
                    ['label' => 'CPU Architecture', 'icon' => 'fa-cogs', 'tooltip' => 'Underlying CPU core microarchitecture (Zen4/Zen5)', 'def' => 'Zen 4 Architecture'],
                ],
                'Memory (RAM)' => [
                    ['label' => 'RAM Speed', 'icon' => 'fa-heartbeat', 'tooltip' => 'Memory frequency and DDR bus generation', 'def' => 'DDR4 3200Mhz / DDR5 6000Mhz'],
                    ['label' => 'RAM Memory Allocation', 'icon' => 'fa-memory', 'tooltip' => 'Guaranteed allocated memory limit', 'def' => '2 GB / 4 GB / 8 GB'],
                    ['label' => 'Resource Isolation', 'icon' => 'fa-server', 'tooltip' => 'Shared vs 100% Dedicated isolated CPU & RAM', 'def' => 'Shared CPU/RAM / Dedicated Resources'],
                    ['label' => 'ECC Memory Protection', 'icon' => 'fa-check-double', 'tooltip' => 'Error-Correcting Code enterprise server memory', 'def' => 'yes'],
                ],
                'Storage & I/O' => [
                    ['label' => 'Storage Tech', 'icon' => 'fa-hdd', 'tooltip' => 'PCIe NVMe SSD generation and performance tier', 'def' => 'NVMe Gen3 / NVMe Gen4 / NVMe Gen5'],
                    ['label' => 'NVMe SSD Storage', 'icon' => 'fa-database', 'tooltip' => 'Allocated ultrafast NVMe storage capacity', 'def' => '10 GB / 25 GB / Unlimited NVMe'],
                    ['label' => 'Disk I/O Speed', 'icon' => 'fa-tachometer-alt', 'tooltip' => 'Input/Output read and write bandwidth limit', 'def' => '50 MB/s / 100 MB/s / 250 MB/s'],
                    ['label' => 'IOPS Allocation', 'icon' => 'fa-wave-square', 'tooltip' => 'Input/Output operations per second threshold', 'def' => '2048 IOPS / 5000 IOPS / 10000 IOPS'],
                    ['label' => 'Inodes Limit', 'icon' => 'fa-folder', 'tooltip' => 'Total allowable files and folders count', 'def' => '250,000 / 500,000 / Unlimited Inodes'],
                    ['label' => 'RAID Mirroring', 'icon' => 'fa-clone', 'tooltip' => 'Hardware RAID data redundancy and disk fault tolerance', 'def' => 'Enterprise RAID 10'],
                ],
                'Network & Traffic' => [
                    ['label' => 'Network Speed', 'icon' => 'fa-network-wired', 'tooltip' => 'Server port uplink network interface speed', 'def' => '500 Mbps / 1 Gbps / 10 Gbps'],
                    ['label' => 'Monthly Bandwidth', 'icon' => 'fa-exchange-alt', 'tooltip' => 'Monthly premium data transfer allocation', 'def' => '500 GB / 1 TB / Unmetered Bandwidth'],
                    ['label' => 'BDIX Ultra-Low Latency', 'icon' => 'fa-project-diagram', 'tooltip' => 'Direct BDIX routing with sub-5ms latency across BD', 'def' => 'yes'],
                    ['label' => 'Anti-DDoS Mitigation', 'icon' => 'fa-shield-virus', 'tooltip' => 'Automated scrubbing and multi-Tbps DDoS protection', 'def' => '10+ Tbps Multi-Layer DDoS Guard'],
                    ['label' => 'Dedicated IPv4', 'icon' => 'fa-globe', 'tooltip' => 'Dedicated clean static IPv4 address included', 'def' => 'no / no / 1 Dedicated IP'],
                ],
                'High Availability & Security' => [
                    ['label' => 'Load Balancer', 'icon' => 'fa-shield-alt', 'tooltip' => 'Automated traffic distribution and cluster redundancy', 'def' => 'no / no / yes'],
                    ['label' => 'Automated Backups', 'icon' => 'fa-history', 'tooltip' => 'Automated off-site disaster recovery server backups', 'def' => 'Weekly Backup / Daily Cloud Backup'],
                    ['label' => 'LiteSpeed Web Server', 'icon' => 'fa-rocket', 'tooltip' => 'LiteSpeed Enterprise web server with LSCache acceleration', 'def' => 'yes'],
                    ['label' => 'Imunify360 Protection', 'icon' => 'fa-user-shield', 'tooltip' => '6-layer proactive AI security suite & malware cleaner', 'def' => 'yes'],
                    ['label' => 'Free SSL & HTTP/3', 'icon' => 'fa-lock', 'tooltip' => 'Auto-renewing Wildcard Let\'s Encrypt SSL with HTTP/3', 'def' => 'yes'],
                ],
                'Web & Developer Tools' => [
                    ['label' => 'Websites Hosted', 'icon' => 'fa-sitemap', 'tooltip' => 'Number of independent domain websites allowed', 'def' => '1 Website / 5 Websites / Unlimited Websites'],
                    ['label' => 'NodeJS / Python / Git', 'icon' => 'fa-code', 'tooltip' => 'Developer app selector and version management', 'def' => 'yes'],
                    ['label' => 'SSH & Terminal Access', 'icon' => 'fa-terminal', 'tooltip' => 'Encrypted secure command-line shell access', 'def' => 'no / yes / yes'],
                    ['label' => 'MySQL Databases', 'icon' => 'fa-database', 'tooltip' => 'High-performance MariaDB databases allowed', 'def' => '5 Databases / Unlimited MySQL'],
                ],
            ];

            echo '<div class="pc-card" style="box-shadow:0 8px 30px rgba(0,0,0,0.06); border-radius:16px;">';
            echo '<form method="post" action="' . $moduleLink . '&action=save_matrix" id="pcMatrixForm">';
            echo '<input type="hidden" name="matrix_id" value="' . $matrixId . '">';

            // Top Actions Bar
            echo '<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #E2E8F0;">';
            echo '  <div>';
            echo '    <h3 style="margin:0 0 4px 0; font-size:19px; font-weight:800; color:#0F172A;"><i class="fas fa-sliders-h text-primary" style="margin-right:6px;"></i> Pro Matrix Editor</h3>';
            echo '    <p style="margin:0; font-size:13px; color:#64748B;">Visually configure hosting tiers, sync with WHMCS product groups, and insert specs from library.</p>';
            echo '  </div>';
            echo '  <div style="display:flex; gap:10px; align-items:center;">';
            echo '    <a href="' . $moduleLink . '&action=matrices" class="pc-btn pc-btn-secondary"><i class="fas fa-arrow-left"></i> Back to Matrices</a>';
            echo '    <button type="submit" class="pc-btn pc-btn-success" style="padding:10px 24px; font-size:14px; font-weight:700;"><i class="fas fa-save"></i> 💾 Save Matrix Specifications</button>';
            echo '  </div>';
            echo '</div>';

            // 1-Click Starter Template Bar
            echo '<div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:14px 18px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">';
            echo '  <div style="display:flex; align-items:center; gap:8px;">';
            echo '    <span style="font-size:13px; font-weight:700; color:#1E293B;"><i class="fas fa-magic text-warning" style="margin-right:4px;"></i> 1-Click Starter Presets:</span>';
            echo '    <span style="font-size:12px; color:#64748B;">Instantly load ready-to-use tiers & specs</span>';
            echo '  </div>';
            echo '  <div style="display:flex; gap:8px; flex-wrap:wrap;">';
            echo '    <button type="button" class="pc-btn pc-btn-secondary" style="font-size:12px; padding:6px 12px;" onclick="pcLoadStarterPreset(\'hosting\')"><i class="fas fa-bolt text-primary"></i> Web Hosting (Budget / Pro / Turbo)</button>';
            echo '    <button type="button" class="pc-btn pc-btn-secondary" style="font-size:12px; padding:6px 12px;" onclick="pcLoadStarterPreset(\'vps\')"><i class="fas fa-server text-success"></i> BDIX High-Performance VPS</button>';
            echo '    <button type="button" class="pc-btn pc-btn-secondary" style="font-size:12px; padding:6px 12px;" onclick="pcLoadStarterPreset(\'reseller\')"><i class="fas fa-layer-group text-info"></i> Reseller Hosting Tiers</button>';
            echo '  </div>';
            echo '</div>';

            // Top Metadata Settings
            echo '<div class="pc-editor-top-grid">';
            echo '  <div>';
            echo '    <div class="pc-form-group">';
            echo '      <label class="pc-form-label"><i class="fas fa-tag text-primary"></i> Internal Matrix Name</label>';
            echo '      <input type="text" name="name" class="pc-input" value="' . plancomparison_h($name) . '" placeholder="e.g. Shared Hosting Comparison" required>';
            echo '    </div>';
            echo '    <div class="pc-editor-meta-grid">';
            echo '      <div class="pc-form-group">';
            echo '        <label class="pc-form-label"><i class="fas fa-heading text-primary"></i> Storefront Section Title</label>';
            echo '        <input type="text" name="title" class="pc-input" value="' . plancomparison_h($title) . '" placeholder="⚡ KEY SPECS">';
            echo '      </div>';
            echo '      <div class="pc-form-group">';
            echo '        <label class="pc-form-label"><i class="fas fa-paragraph text-primary"></i> Section Subtitle</label>';
            echo '        <input type="text" name="subtitle" class="pc-input" value="' . plancomparison_h($subtitle) . '" placeholder="Compare hardware capabilities across all tiers.">';
            echo '      </div>';
            echo '    </div>';
            echo '  </div>';

            echo '  <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:16px;">';
            echo '    <label class="pc-form-label"><i class="fas fa-bullseye text-primary"></i> Target WHMCS Product Groups</label>';
            echo '    <select name="groups[]" multiple size="5" class="pc-input" style="height:115px; font-size:12.5px;">';
            foreach ($allGroups as $g) {
                $sel = in_array($g->id, $selectedGroups) ? 'selected' : '';
                echo '<option value="' . (int)$g->id . '" ' . $sel . '>' . plancomparison_h($g->name) . '</option>';
            }
            echo '    </select>';
            echo '    <div style="margin-top:10px;"><label style="display:flex; align-items:center; gap:6px; cursor:pointer;"><input type="checkbox" name="enabled" value="1" ' . ($enabled ? 'checked' : '') . '> <strong style="font-size:12.5px; color:#0F172A;">Enable Matrix on Target Groups</strong></label></div>';
            echo '  </div>';
            echo '</div>';

            // Section 1: Tier Columns Manager
            echo '<div style="margin-bottom:32px;">';
            echo '  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:16px;">';
            echo '    <div>';
            echo '      <h4 style="margin:0 0 2px 0; font-size:16px; font-weight:800; color:#0F172A;"><i class="fas fa-columns text-primary" style="margin-right:6px;"></i> Plan Tiers (Matrix Columns)</h4>';
            echo '      <span style="font-size:12.5px; color:#64748B;">Each tier represents a column in your comparison matrix. Use dropdowns to sync directly with WHMCS groups or packages.</span>';
            echo '    </div>';
            echo '    <button type="button" class="pc-btn pc-btn-primary" style="font-size:12.5px; padding:7px 14px;" onclick="pcAddTierColumn()"><i class="fas fa-plus"></i> Add New Tier Column</button>';
            echo '  </div>';

            echo '  <div class="pc-tiers-grid" id="pcTiersContainer">';
            foreach ($tiers as $idx => $t) {
                $isHigh = !empty($t['highlight']);
                echo '  <div class="pc-tier-editor-card" data-tier-index="' . $idx . '" style="background:#FFFFFF; border:2px solid ' . ($isHigh ? '#2563EB' : '#E2E8F0') . '; border-radius:14px; padding:18px; position:relative; box-shadow:0 4px 12px rgba(0,0,0,0.03);">';
                echo '    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #F1F5F9;">';
                echo '      <span style="font-weight:800; font-size:13px; color:' . ($isHigh ? '#2563EB' : '#475569') . ';"><i class="fas fa-layer-group"></i> Tier #' . ($idx + 1) . '</span>';
                echo '      <button type="button" class="pc-btn pc-btn-danger" style="padding:3px 8px; font-size:11px;" onclick="pcRemoveTierColumn(' . $idx . ')" title="Remove Tier"><i class="fas fa-trash"></i></button>';
                echo '    </div>';

                // WHMCS Group Quick-Link Dropdown
                echo '    <div class="pc-form-group" style="margin-bottom:10px;">';
                echo '      <label class="pc-form-label" style="font-size:11.5px; color:#64748B;"><i class="fas fa-link text-primary"></i> Sync from WHMCS Group (Auto-Fill)</label>';
                echo '      <select class="pc-input" style="font-size:12px; padding:6px 8px; background:#F8FAFC;" onchange="pcSyncTierFromGroup(this, ' . $idx . ')">';
                echo '        <option value="">-- Choose WHMCS Group to Auto-Fill --</option>';
                foreach ($allGroups as $g) {
                    echo '        <option value="' . (int)$g->id . '" data-name="' . plancomparison_h($g->name) . '" data-link="cart.php?gid=' . (int)$g->id . '">' . plancomparison_h($g->name) . ' (GID: ' . (int)$g->id . ')</option>';
                }
                echo '      </select>';
                echo '    </div>';

                echo '    <div class="pc-form-group" style="margin-bottom:10px;"><label class="pc-form-label">Tier Slug / ID</label><input type="text" name="tiers[' . $idx . '][id]" value="' . plancomparison_h($t['id']) . '" class="pc-input pc-tier-slug" style="font-size:12.5px;" onchange="pcUpdateTierSlug(' . $idx . ', this.value)" required></div>';
                echo '    <div class="pc-form-group" style="margin-bottom:10px;"><label class="pc-form-label">Tier Title</label><input type="text" name="tiers[' . $idx . '][name]" value="' . plancomparison_h($t['name']) . '" class="pc-input pc-tier-name" style="font-weight:700; font-size:13.5px;" required></div>';
                echo '    <div class="pc-form-group" style="margin-bottom:10px;"><label class="pc-form-label">Tagline / Target Audience</label><input type="text" name="tiers[' . $idx . '][tagline]" value="' . plancomparison_h($t['tagline']) . '" class="pc-input" placeholder="e.g. For Personal Sites"></div>';

                // Badge Presets Dropdown & Input
                echo '    <div class="pc-form-group" style="margin-bottom:10px;">';
                echo '      <label class="pc-form-label">Promotional Pill Badge</label>';
                echo '      <div style="display:flex; gap:6px;">';
                echo '        <input type="text" name="tiers[' . $idx . '][badge]" value="' . plancomparison_h($t['badge']) . '" class="pc-input pc-tier-badge" placeholder="e.g. MOST POPULAR" style="flex:1;">';
                echo '        <select class="pc-input" style="width:130px; font-size:11.5px;" onchange="this.previousElementSibling.value = this.value">';
                echo '          <option value="">Presets...</option>';
                echo '          <option value="MOST POPULAR">MOST POPULAR</option>';
                echo '          <option value="ULTIMATE POWER">ULTIMATE POWER</option>';
                echo '          <option value="BEST VALUE">BEST VALUE</option>';
                echo '          <option value="RECOMMENDED">RECOMMENDED</option>';
                echo '          <option value="ENTERPRISE">ENTERPRISE</option>';
                echo '          <option value="HOT OFFER">HOT OFFER</option>';
                echo '        </select>';
                echo '      </div>';
                echo '    </div>';

                echo '    <div class="pc-form-group" style="margin-bottom:10px;"><label class="pc-form-label">Badge Color Scheme</label>';
                echo '      <select name="tiers[' . $idx . '][badge_color]" class="pc-input">';
                echo '        <option value="blue"' . ($t['badge_color'] === 'blue' ? ' selected' : '') . '>Royal Blue Pill (Dark / White)</option>';
                echo '        <option value="amber"' . ($t['badge_color'] === 'amber' ? ' selected' : '') . '>Amber / Orange Pill (Warm Outline)</option>';
                echo '        <option value="green"' . ($t['badge_color'] === 'green' ? ' selected' : '') . '>Emerald Green Pill</option>';
                echo '      </select>';
                echo '    </div>';

                echo '    <div class="pc-form-group" style="margin-bottom:12px; background:#F8FAFC; padding:8px 10px; border-radius:6px;"><label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="tiers[' . $idx . '][highlight]" value="1" ' . ($isHigh ? 'checked' : '') . ' onchange="this.closest(\'.pc-tier-editor-card\').style.borderColor = this.checked ? \'#2563EB\' : \'#E2E8F0\'"> <strong style="font-size:12.5px; color:#1E293B;">Highlight Column (Featured / Pro)</strong></label></div>';

                echo '    <div class="pc-form-group" style="margin-bottom:10px;"><label class="pc-form-label">Button Text</label><input type="text" name="tiers[' . $idx . '][button_text]" value="' . plancomparison_h($t['button_text']) . '" class="pc-input" placeholder="View Plans"></div>';
                echo '    <div class="pc-form-group" style="margin-bottom:0;"><label class="pc-form-label">Button Target Link (Cart URL)</label><input type="text" name="tiers[' . $idx . '][button_link]" value="' . plancomparison_h($t['button_link']) . '" class="pc-input pc-tier-link" placeholder="cart.php?gid=2"></div>';

                echo '  </div>';
            }
            echo '  </div>';
            echo '</div>';

            // Section 2: Specification Rows Library & Table Editor
            echo '<div style="margin-bottom:32px;">';
            echo '  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">';
            echo '    <div>';
            echo '      <h4 style="margin:0 0 2px 0; font-size:16px; font-weight:800; color:#0F172A;"><i class="fas fa-list-check text-primary" style="margin-right:6px;"></i> Specification Rows & Values</h4>';
            echo '      <span style="font-size:12.5px; color:#64748B;">Select features from the pre-built library or add custom rows. Use quick tags to populate values.</span>';
            echo '    </div>';

            // Dropdown Library Quick-Insert Box
            echo '    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">';
            echo '      <select id="pcSpecLibrarySelect" class="pc-input" style="width:260px; font-size:12.5px; font-weight:600;">';
            echo '        <option value="">⚡ Select Feature from Library...</option>';
            foreach ($specLibrary as $catName => $featList) {
                echo '        <optgroup label="' . plancomparison_h($catName) . '">';
                foreach ($featList as $f) {
                    $jsonPayload = htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8');
                    echo '          <option value="' . $jsonPayload . '">' . plancomparison_h($f['label']) . '</option>';
                }
                echo '        </optgroup>';
            }
            echo '      </select>';
            echo '      <button type="button" class="pc-btn pc-btn-primary" style="font-size:12.5px; padding:8px 14px;" onclick="pcInsertFromLibrary()"><i class="fas fa-plus"></i> + Add from Library</button>';
            echo '      <button type="button" class="pc-btn pc-btn-secondary" style="font-size:12.5px; padding:8px 12px;" onclick="pcAddCustomSpecRow()"><i class="fas fa-plus-circle"></i> Custom Row</button>';
            echo '    </div>';
            echo '  </div>';

            // Interactive Spec Grid Table
            echo '  <div class="pc-table-responsive" style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; overflow:hidden;">';
            echo '    <table class="pc-table-grid" id="pcSpecsTable">';
            echo '      <thead><tr>';
            echo '        <th style="width:40px; text-align:center;">#</th>';
            echo '        <th style="width:250px;">Specification Label & Icon</th>';
            echo '        <th style="width:220px;">Hover Tooltip (ⓘ)</th>';
            foreach ($tiers as $t) {
                echo '        <th class="pc-th-tier-' . plancomparison_h($t['id']) . '" style="text-align:center; min-width:160px;">' . plancomparison_h($t['name']) . '</th>';
            }
            echo '        <th style="width:90px; text-align:center;">Actions</th>';
            echo '      </tr></thead><tbody id="pcSpecsTableBody">';

            foreach ($specs as $sIdx => $s) {
                echo '      <tr class="pc-spec-row-item" data-row-index="' . $sIdx . '">';
                echo '        <td style="text-align:center; color:#94A3B8; font-weight:700;"><span class="pc-row-num">' . ($sIdx + 1) . '</span></td>';
                echo '        <td>';
                echo '          <input type="hidden" name="specs[' . $sIdx . '][id]" value="' . plancomparison_h($s['id']) . '" class="pc-spec-id">';
                echo '          <div style="margin-bottom:6px;"><input type="text" name="specs[' . $sIdx . '][label]" value="' . plancomparison_h($s['label']) . '" class="pc-input" style="font-weight:700;" placeholder="e.g. RAM Speed" required></div>';
                echo '          <div style="display:flex; align-items:center; gap:6px;">';
                echo '            <span style="font-size:11px; color:#64748B;"><i class="fas fa-icons"></i> Icon:</span>';
                echo '            <input type="text" name="specs[' . $sIdx . '][icon]" value="' . plancomparison_h($s['icon']) . '" class="pc-input" style="font-size:11.5px; padding:4px 8px; height:28px;" placeholder="fa-microchip">';
                echo '          </div>';
                echo '        </td>';
                echo '        <td>';
                echo '          <textarea name="specs[' . $sIdx . '][tooltip]" class="pc-input" rows="2" style="font-size:12px; resize:vertical;" placeholder="Optional explanation displayed on hover">' . plancomparison_h($s['tooltip']) . '</textarea>';
                echo '        </td>';

                foreach ($tiers as $t) {
                    $tId = $t['id'];
                    $val = isset($s['values'][$tId]) ? $s['values'][$tId] : '';
                    echo '        <td class="pc-td-tier-' . plancomparison_h($tId) . '" data-tier-id="' . plancomparison_h($tId) . '">';
                    echo '          <div style="display:flex; flex-direction:column; gap:6px;">';
                    echo '            <input type="text" name="specs[' . $sIdx . '][values][' . plancomparison_h($tId) . ']" value="' . plancomparison_h($val) . '" class="pc-input pc-val-input" placeholder="Value...">';
                    echo '            <div style="display:flex; gap:3px; flex-wrap:wrap;">';
                    echo '              <button type="button" class="pc-btn pc-btn-secondary" style="padding:2px 5px; font-size:10px; border-radius:4px;" onclick="pcSetCellVal(this, \'yes\')">✓ Yes</button>';
                    echo '              <button type="button" class="pc-btn pc-btn-secondary" style="padding:2px 5px; font-size:10px; border-radius:4px;" onclick="pcSetCellVal(this, \'no\')">— No</button>';
                    echo '              <button type="button" class="pc-btn pc-btn-secondary" style="padding:2px 5px; font-size:10px; border-radius:4px;" onclick="pcSetCellVal(this, \'Shared\')">Shared</button>';
                    echo '              <button type="button" class="pc-btn pc-btn-secondary" style="padding:2px 5px; font-size:10px; border-radius:4px;" onclick="pcSetCellVal(this, \'Dedicated\')">Dedicated</button>';
                    echo '            </div>';
                    echo '          </div>';
                    echo '        </td>';
                }

                echo '        <td style="text-align:center;">';
                echo '          <div style="display:flex; justify-content:center; gap:4px;">';
                echo '            <button type="button" class="pc-btn pc-btn-secondary" style="padding:4px 6px; font-size:11px;" onclick="pcMoveRow(this, -1)" title="Move Up"><i class="fas fa-chevron-up"></i></button>';
                echo '            <button type="button" class="pc-btn pc-btn-secondary" style="padding:4px 6px; font-size:11px;" onclick="pcMoveRow(this, 1)" title="Move Down"><i class="fas fa-chevron-down"></i></button>';
                echo '            <button type="button" class="pc-btn pc-btn-danger" style="padding:4px 6px; font-size:11px;" onclick="pcDeleteRow(this)" title="Delete Row"><i class="fas fa-trash"></i></button>';
                echo '          </div>';
                echo '        </td>';
                echo '      </tr>';
            }

            echo '      </tbody></table>';
            echo '  </div>';
            echo '</div>';

            // Bottom Submit Button
            echo '<div style="margin-top:24px; padding-top:16px; border-top:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">';
            echo '  <a href="' . $moduleLink . '&action=matrices" class="pc-btn pc-btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>';
            echo '  <button type="submit" class="pc-btn pc-btn-success" style="padding:11px 28px; font-size:14.5px; font-weight:800;"><i class="fas fa-save"></i> 💾 Save All Matrix Specifications</button>';
            echo '</div>';

            echo '</form>';
            echo '</div>';

            // JavaScript Engine for Pro Matrix Editor
            $jsonTiers = json_encode($tiers);
            echo '
            <script>
            var pcCurrentTiers = ' . $jsonTiers . ';
            var pcRowCounter = ' . count($specs) . ';
            var pcTierCounter = ' . count($tiers) . ';

            // 1. Sync Tier from WHMCS Group Dropdown
            function pcSyncTierFromGroup(selectEl, tierIdx) {
                var selectedOpt = selectEl.options[selectEl.selectedIndex];
                if (!selectedOpt || !selectedOpt.value) return;
                var groupName = selectedOpt.getAttribute("data-name");
                var groupLink = selectedOpt.getAttribute("data-link");

                var card = selectEl.closest(".pc-tier-editor-card");
                if (card) {
                    var nameInput = card.querySelector(".pc-tier-name");
                    var linkInput = card.querySelector(".pc-tier-link");
                    if (nameInput && groupName) nameInput.value = groupName.replace(/\s*\/.*$/, "");
                    if (linkInput && groupLink) linkInput.value = groupLink;
                }
            }

            // 2. Set Quick Value Helper
            function pcSetCellVal(btn, val) {
                var container = btn.closest("div").parentElement;
                var input = container.querySelector(".pc-val-input");
                if (input) {
                    input.value = val;
                    input.focus();
                }
            }

            // 3. Move Row Up / Down
            function pcMoveRow(btn, direction) {
                var row = btn.closest("tr");
                if (direction === -1 && row.previousElementSibling) {
                    row.parentNode.insertBefore(row, row.previousElementSibling);
                } else if (direction === 1 && row.nextElementSibling) {
                    row.parentNode.insertBefore(row.nextElementSibling, row);
                }
                pcRenumberRows();
            }

            // 4. Delete Spec Row
            function pcDeleteRow(btn) {
                var row = btn.closest("tr");
                if (confirm("Remove this specification row?")) {
                    row.remove();
                    pcRenumberRows();
                }
            }

            // 5. Renumber Rows
            function pcRenumberRows() {
                var rows = document.querySelectorAll("#pcSpecsTableBody tr");
                rows.forEach(function(r, idx) {
                    var numEl = r.querySelector(".pc-row-num");
                    if (numEl) numEl.innerText = idx + 1;
                });
            }

            // 6. Insert Feature from Library Dropdown
            function pcInsertFromLibrary() {
                var selectEl = document.getElementById("pcSpecLibrarySelect");
                if (!selectEl || !selectEl.value) {
                    alert("Please select a feature from the library dropdown first.");
                    return;
                }

                try {
                    var feat = JSON.parse(selectEl.value);
                    pcRowCounter++;
                    var tbody = document.getElementById("pcSpecsTableBody");
                    var tr = document.createElement("tr");
                    tr.className = "pc-spec-row-item";
                    tr.setAttribute("data-row-index", pcRowCounter);

                    var html = "<td style=\"text-align:center; color:#94A3B8; font-weight:700;\"><span class=\"pc-row-num\">" + (tbody.children.length + 1) + "</span></td>";
                    html += "<td>";
                    html += "  <input type=\"hidden\" name=\"specs[" + pcRowCounter + "][id]\" value=\"spec_" + Date.now() + "\" class=\"pc-spec-id\">";
                    html += "  <div style=\"margin-bottom:6px;\"><input type=\"text\" name=\"specs[" + pcRowCounter + "][label]\" value=\"" + feat.label.replace(/"/g, "&quot;") + "\" class=\"pc-input\" style=\"font-weight:700;\" required></div>";
                    html += "  <div style=\"display:flex; align-items:center; gap:6px;\"><span style=\"font-size:11px; color:#64748B;\"><i class=\"fas fa-icons\"></i> Icon:</span><input type=\"text\" name=\"specs[" + pcRowCounter + "][icon]\" value=\"" + feat.icon + "\" class=\"pc-input\" style=\"font-size:11.5px; padding:4px 8px; height:28px;\"></div>";
                    html += "</td>";
                    html += "<td>";
                    html += "  <textarea name=\"specs[" + pcRowCounter + "][tooltip]\" class=\"pc-input\" rows=\"2\" style=\"font-size:12px; resize:vertical;\">" + feat.tooltip.replace(/"/g, "&quot;") + "</textarea>";
                    html += "</td>";

                    // Split default values if comma/slash separated
                    var defVal = feat.def || "yes";
                    var defParts = defVal.split("/").map(function(s) { return s.trim(); });

                    var tierInputs = document.querySelectorAll(".pc-tier-slug");
                    tierInputs.forEach(function(tSlugInput, tIdx) {
                        var tSlug = tSlugInput.value.trim() || ("tier_" + tIdx);
                        var v = defParts[tIdx] !== undefined ? defParts[tIdx] : (defParts[0] || "");

                        html += "<td class=\"pc-td-tier-" + tSlug + "\" data-tier-id=\"" + tSlug + "\">";
                        html += "  <div style=\"display:flex; flex-direction:column; gap:6px;\">";
                        html += "    <input type=\"text\" name=\"specs[" + pcRowCounter + "][values][" + tSlug + "]\" value=\"" + v.replace(/"/g, "&quot;") + "\" class=\"pc-input pc-val-input\">";
                        html += "    <div style=\"display:flex; gap:3px; flex-wrap:wrap;\">";
                        html += "      <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'yes\')\">✓ Yes</button>";
                        html += "      <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'no\')\">— No</button>";
                        html += "      <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'Shared\')\">Shared</button>";
                        html += "      <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'Dedicated\')\">Dedicated</button>";
                        html += "    </div>";
                        html += "  </div>";
                        html += "</td>";
                    });

                    html += "<td style=\"text-align:center;\">";
                    html += "  <div style=\"display:flex; justify-content:center; gap:4px;\">";
                    html += "    <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:4px 6px; font-size:11px;\" onclick=\"pcMoveRow(this, -1)\"><i class=\"fas fa-chevron-up\"></i></button>";
                    html += "    <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:4px 6px; font-size:11px;\" onclick=\"pcMoveRow(this, 1)\"><i class=\"fas fa-chevron-down\"></i></button>";
                    html += "    <button type=\"button\" class=\"pc-btn pc-btn-danger\" style=\"padding:4px 6px; font-size:11px;\" onclick=\"pcDeleteRow(this)\"><i class=\"fas fa-trash\"></i></button>";
                    html += "  </div>";
                    html += "</td>";

                    tr.innerHTML = html;
                    tbody.appendChild(tr);
                    pcRenumberRows();
                    selectEl.selectedIndex = 0;
                } catch(e) {
                    console.error(e);
                }
            }

            // 7. Add Custom Empty Spec Row
            function pcAddCustomSpecRow() {
                pcRowCounter++;
                var tbody = document.getElementById("pcSpecsTableBody");
                var tr = document.createElement("tr");
                tr.className = "pc-spec-row-item";
                tr.setAttribute("data-row-index", pcRowCounter);

                var html = "<td style=\"text-align:center; color:#94A3B8; font-weight:700;\"><span class=\"pc-row-num\">" + (tbody.children.length + 1) + "</span></td>";
                html += "<td>";
                html += "  <input type=\"hidden\" name=\"specs[" + pcRowCounter + "][id]\" value=\"spec_" + Date.now() + "\" class=\"pc-spec-id\">";
                html += "  <div style=\"margin-bottom:6px;\"><input type=\"text\" name=\"specs[" + pcRowCounter + "][label]\" value=\"\" class=\"pc-input\" style=\"font-weight:700;\" placeholder=\"Feature Name\" required></div>";
                html += "  <div style=\"display:flex; align-items:center; gap:6px;\"><span style=\"font-size:11px; color:#64748B;\"><i class=\"fas fa-icons\"></i> Icon:</span><input type=\"text\" name=\"specs[" + pcRowCounter + "][icon]\" value=\"fa-check\" class=\"pc-input\" style=\"font-size:11.5px; padding:4px 8px; height:28px;\"></div>";
                html += "</td>";
                html += "<td><textarea name=\"specs[" + pcRowCounter + "][tooltip]\" class=\"pc-input\" rows=\"2\" style=\"font-size:12px; resize:vertical;\" placeholder=\"Tooltip explanation\"></textarea></td>";

                var tierInputs = document.querySelectorAll(".pc-tier-slug");
                tierInputs.forEach(function(tSlugInput, tIdx) {
                    var tSlug = tSlugInput.value.trim() || ("tier_" + tIdx);
                    html += "<td class=\"pc-td-tier-" + tSlug + "\" data-tier-id=\"" + tSlug + "\">";
                    html += "  <div style=\"display:flex; flex-direction:column; gap:6px;\">";
                    html += "    <input type=\"text\" name=\"specs[" + pcRowCounter + "][values][" + tSlug + "]\" value=\"\" class=\"pc-input pc-val-input\" placeholder=\"Value\">";
                    html += "    <div style=\"display:flex; gap:3px; flex-wrap:wrap;\">";
                    html += "      <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'yes\')\">✓ Yes</button>";
                    html += "      <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'no\')\">— No</button>";
                    html += "    </div>";
                    html += "  </div>";
                    html += "</td>";
                });

                html += "<td style=\"text-align:center;\">";
                html += "  <div style=\"display:flex; justify-content:center; gap:4px;\">";
                html += "    <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:4px 6px; font-size:11px;\" onclick=\"pcMoveRow(this, -1)\"><i class=\"fas fa-chevron-up\"></i></button>";
                html += "    <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:4px 6px; font-size:11px;\" onclick=\"pcMoveRow(this, 1)\"><i class=\"fas fa-chevron-down\"></i></button>";
                html += "    <button type=\"button\" class=\"pc-btn pc-btn-danger\" style=\"padding:4px 6px; font-size:11px;\" onclick=\"pcDeleteRow(this)\"><i class=\"fas fa-trash\"></i></button>";
                html += "  </div>";
                html += "</td>";

                tr.innerHTML = html;
                tbody.appendChild(tr);
                pcRenumberRows();
            }

            // 8. Add New Tier Column
            function pcAddTierColumn() {
                pcTierCounter++;
                var container = document.getElementById("pcTiersContainer");
                var newSlug = "tier_" + pcTierCounter;
                var card = document.createElement("div");
                card.className = "pc-tier-editor-card";
                card.setAttribute("data-tier-index", pcTierCounter);
                card.style.cssText = "background:#FFFFFF; border:2px solid #E2E8F0; border-radius:14px; padding:18px; position:relative; box-shadow:0 4px 12px rgba(0,0,0,0.03);";

                var cardHtml = "<div style=\"display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #F1F5F9;\">";
                cardHtml += "  <span style=\"font-weight:800; font-size:13px; color:#475569;\"><i class=\"fas fa-layer-group\"></i> Tier #" + (container.children.length + 1) + "</span>";
                cardHtml += "  <button type=\"button\" class=\"pc-btn pc-btn-danger\" style=\"padding:3px 8px; font-size:11px;\" onclick=\"pcRemoveTierColumn(" + pcTierCounter + ")\"><i class=\"fas fa-trash\"></i></button>";
                cardHtml += "</div>";

                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:10px;\"><label class=\"pc-form-label\">Tier Slug / ID</label><input type=\"text\" name=\"tiers[" + pcTierCounter + "][id]\" value=\"" + newSlug + "\" class=\"pc-input pc-tier-slug\" style=\"font-size:12.5px;\" required></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:10px;\"><label class=\"pc-form-label\">Tier Title</label><input type=\"text\" name=\"tiers[" + pcTierCounter + "][name]\" value=\"New Tier\" class=\"pc-input pc-tier-name\" style=\"font-weight:700; font-size:13.5px;\" required></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:10px;\"><label class=\"pc-form-label\">Tagline</label><input type=\"text\" name=\"tiers[" + pcTierCounter + "][tagline]\" value=\"\" class=\"pc-input\" placeholder=\"e.g. For Small Teams\"></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:10px;\"><label class=\"pc-form-label\">Pill Badge</label><input type=\"text\" name=\"tiers[" + pcTierCounter + "][badge]\" value=\"\" class=\"pc-input pc-tier-badge\" placeholder=\"e.g. BEST VALUE\"></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:10px;\"><label class=\"pc-form-label\">Badge Color Scheme</label><select name=\"tiers[" + pcTierCounter + "][badge_color]\" class=\"pc-input\"><option value=\"blue\">Royal Blue Pill</option><option value=\"amber\">Amber / Orange Pill</option><option value=\"green\">Emerald Green Pill</option></select></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:12px; background:#F8FAFC; padding:8px 10px; border-radius:6px;\"><label style=\"display:flex; align-items:center; gap:8px; cursor:pointer;\"><input type=\"checkbox\" name=\"tiers[" + pcTierCounter + "][highlight]\" value=\"1\"> <strong style=\"font-size:12.5px; color:#1E293B;\">Highlight Column</strong></label></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:10px;\"><label class=\"pc-form-label\">Button Text</label><input type=\"text\" name=\"tiers[" + pcTierCounter + "][button_text]\" value=\"View Plans\" class=\"pc-input\"></div>";
                cardHtml += "<div class=\"pc-form-group\" style=\"margin-bottom:0;\"><label class=\"pc-form-label\">Button Target Link</label><input type=\"text\" name=\"tiers[" + pcTierCounter + "][button_link]\" value=\"cart.php\" class=\"pc-input pc-tier-link\"></div>";

                card.innerHTML = cardHtml;
                container.appendChild(card);

                // Add corresponding column header in table
                var tableHeaderTr = document.querySelector("#pcSpecsTable thead tr");
                var actionsTh = tableHeaderTr.lastElementChild;
                var newTh = document.createElement("th");
                newTh.className = "pc-th-tier-" + newSlug;
                newTh.style.cssText = "text-align:center; min-width:160px;";
                newTh.innerText = "New Tier";
                tableHeaderTr.insertBefore(newTh, actionsTh);

                // Add cells to each row
                var rows = document.querySelectorAll("#pcSpecsTableBody tr");
                rows.forEach(function(row) {
                    var rIdx = row.getAttribute("data-row-index");
                    var actionsTd = row.lastElementChild;
                    var newTd = document.createElement("td");
                    newTd.className = "pc-td-tier-" + newSlug;
                    newTd.setAttribute("data-tier-id", newSlug);
                    newTd.innerHTML = "<div style=\"display:flex; flex-direction:column; gap:6px;\">" +
                                      "  <input type=\"text\" name=\"specs[" + rIdx + "][values][" + newSlug + "]\" value=\"\" class=\"pc-input pc-val-input\" placeholder=\"Value...\">" +
                                      "  <div style=\"display:flex; gap:3px; flex-wrap:wrap;\">" +
                                      "    <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'yes\')\">✓ Yes</button>" +
                                      "    <button type=\"button\" class=\"pc-btn pc-btn-secondary\" style=\"padding:2px 5px; font-size:10px; border-radius:4px;\" onclick=\"pcSetCellVal(this, \'no\')\">— No</button>" +
                                      "  </div>" +
                                      "</div>";
                    row.insertBefore(newTd, actionsTd);
                });
            }

            // 9. Remove Tier Column
            function pcRemoveTierColumn(idx) {
                var card = document.querySelector(".pc-tier-editor-card[data-tier-index=\'" + idx + "\']");
                if (!card) return;
                var slugInput = card.querySelector(".pc-tier-slug");
                var slug = slugInput ? slugInput.value.trim() : "";

                if (confirm("Remove this tier column?")) {
                    card.remove();
                    if (slug) {
                        var th = document.querySelector(".pc-th-tier-" + slug);
                        if (th) th.remove();
                        var tds = document.querySelectorAll(".pc-td-tier-" + slug);
                        tds.forEach(function(td) { td.remove(); });
                    }
                }
            }

            // 10. Load Starter Presets (Hosting vs VPS vs Reseller)
            function pcLoadStarterPreset(type) {
                if (!confirm("Load preset? This will refresh tier columns and sample values with the " + type.toUpperCase() + " specification template.")) return;

                if (type === "hosting") {
                    location.href = "' . $moduleLink . '&action=editor&id=0";
                }
            }
            </script>
            ';
        }

        if ($action === 'display_settings') {
            $status = plancomparison_get_setting('status', 'on');
            $placement = plancomparison_get_setting('placement', 'below_features');
            $color = plancomparison_get_setting('highlight_color', '#1E60F8');
            $customCss = plancomparison_get_setting('custom_css', '');

            echo '<div class="pc-card">';
            echo '<form method="post" action="' . $moduleLink . '&action=save_display_settings">';
            echo '  <h3 style="margin:0 0 16px 0; font-size:17px; font-weight:800; color:#0F172A;">Display & Injection Settings</h3>';

            echo '  <div class="pc-form-group">';
            echo '    <label><input type="checkbox" name="status" value="1" ' . ($status === 'on' ? 'checked' : '') . '> <strong>Enable Module Globally on Storefront</strong></label>';
            echo '  </div>';

            echo '  <div class="pc-form-group">';
            echo '    <label class="pc-form-label">Auto-Injection Placement</label>';
            echo '    <select name="placement" class="pc-input" style="max-width:380px;">';
            echo '      <option value="below_features"' . ($placement === 'below_features' ? ' selected' : '') . '>Directly Below Advanced Features (Recommended)</option>';
            echo '      <option value="below_packages"' . ($placement === 'below_packages' ? ' selected' : '') . '>Directly Below Product Packages / Cards</option>';
            echo '    </select>';
            echo '  </div>';

            echo '  <div class="pc-form-group">';
            echo '    <label class="pc-form-label">Highlight Accent Color (Pro Tier)</label>';
            echo '    <input type="color" name="highlight_color" value="' . plancomparison_h($color) . '" style="height:40px; width:70px; padding:2px; border:1px solid #CBD5E1; border-radius:6px; cursor:pointer;">';
            echo '  </div>';

            echo '  <div class="pc-form-group">';
            echo '    <label class="pc-form-label">Custom Scoped CSS</label>';
            echo '    <textarea name="custom_css" class="pc-input" rows="5" style="font-family:monospace; font-size:12px;">' . plancomparison_h($customCss) . '</textarea>';
            echo '  </div>';

            echo '  <button type="submit" class="pc-btn pc-btn-primary"><i class="fas fa-save"></i> Save Display Settings</button>';
            echo '</form>';
            echo '</div>';
        }

        // 4. Integration Guide
        if ($action === 'integration_guide') {
            echo '<div class="pc-card">';
            echo '  <h3 style="margin:0 0 12px 0; font-size:17px; font-weight:800; color:#0F172A;">Integration Guide & Theme Compatibility</h3>';
            echo '  <p style="color:#475569; font-size:13.5px; line-height:1.6;">Bahari Plan & Tier Comparison automatically injects directly into WHMCS Order Forms (Lagom 2, Standard Cart, Supreme, Premium Comparison) using ClientAreaFooterOutput hooks. No template modification is required!</p>';
            echo '</div>';
        }

        // In-Module Custom Delete Confirmation Modal HTML & JS
        echo '
        <div id="pcDeleteModal" class="pc-modal-backdrop" onclick="if(event.target === this) pcCloseDeleteModal();">
            <div class="pc-modal-card">
                <div class="pc-modal-icon-wrap">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <h3 class="pc-modal-title">Delete Comparison Matrix?</h3>
                <p class="pc-modal-desc">
                    Are you sure you want to permanently delete <strong id="pcDeleteMatrixName" style="color:#0F172A;">this matrix</strong>? This action cannot be undone.
                </p>
                <div class="pc-modal-actions">
                    <button type="button" class="pc-btn pc-btn-secondary" onclick="pcCloseDeleteModal()">Cancel</button>
                    <a id="pcConfirmDeleteBtn" href="#" class="pc-btn pc-btn-danger"><i class="fas fa-trash-alt"></i> Yes, Delete Matrix</a>
                </div>
            </div>
        </div>

        <script>
        function pcOpenDeleteModal(deleteUrl, matrixName) {
            var modal = document.getElementById("pcDeleteModal");
            var confirmBtn = document.getElementById("pcConfirmDeleteBtn");
            var nameEl = document.getElementById("pcDeleteMatrixName");
            if (confirmBtn) confirmBtn.href = deleteUrl;
            if (nameEl) nameEl.textContent = matrixName ? "\"" + matrixName + "\"" : "this matrix";
            if (modal) {
                modal.classList.add("pc-modal-open");
                document.body.style.overflow = "hidden";
            }
        }

        function pcCloseDeleteModal() {
            var modal = document.getElementById("pcDeleteModal");
            if (modal) {
                modal.classList.remove("pc-modal-open");
                document.body.style.overflow = "";
            }
        }

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                pcCloseDeleteModal();
            }
        });
        </script>
        ';

    }
}
