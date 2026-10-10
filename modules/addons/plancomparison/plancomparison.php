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

            $name = $matrix ? $matrix->name : 'New Comparison Matrix';
            $title = $matrix ? $matrix->title : '⚡ KEY SPECS';
            $subtitle = $matrix ? $matrix->subtitle : 'Compare hardware capabilities across all tiers.';
            $enabled = $matrix ? $matrix->enabled : 1;
            $selectedGroups = $matrix ? json_decode($matrix->groups, true) : [2, 3, 20];
            if (!is_array($selectedGroups)) $selectedGroups = [];

            $tiers = $matrix ? json_decode($matrix->tiers_data, true) : [
                ['id' => 'budget', 'name' => 'Budget', 'tagline' => 'For Personal Sites', 'badge' => '', 'badge_color' => 'default', 'highlight' => 0, 'button_text' => 'View Plans', 'button_link' => 'cart.php?gid=2'],
                ['id' => 'pro', 'name' => 'Pro', 'tagline' => 'For Growing Business', 'badge' => 'MOST POPULAR', 'badge_color' => 'blue', 'highlight' => 1, 'button_text' => 'View Plans', 'button_link' => 'cart.php?gid=3'],
                ['id' => 'turbo', 'name' => 'Turbo', 'tagline' => 'For High Traffic', 'badge' => 'ULTIMATE POWER', 'badge_color' => 'amber', 'highlight' => 0, 'button_text' => 'View Plans', 'button_link' => 'cart.php?gid=20'],
            ];

            $specs = $matrix ? json_decode($matrix->specs_data, true) : [
                ['id' => 'spec_cpu', 'label' => 'Processor', 'icon' => 'fa-microchip', 'tooltip' => 'Server CPU clock & architecture', 'values' => ['budget' => 'Intel XEON', 'pro' => 'Ryzen 9 (4.5Ghz)', 'turbo' => 'Ryzen 9 (5.7Ghz)']],
                ['id' => 'spec_ram', 'label' => 'RAM Speed', 'icon' => 'fa-heartbeat', 'tooltip' => 'Memory bus frequency', 'values' => ['budget' => 'DDR4 2400Mhz', 'pro' => 'DDR4 3200Mhz', 'turbo' => 'DDR5 6000Mhz']],
                ['id' => 'spec_storage', 'label' => 'Storage Tech', 'icon' => 'fa-hdd', 'tooltip' => 'PCIe NVMe SSD generation', 'values' => ['budget' => 'NVMe Gen3', 'pro' => 'NVMe Gen4', 'turbo' => 'NVMe Gen5']],
                ['id' => 'spec_resources', 'label' => 'Resources', 'icon' => 'fa-server', 'tooltip' => 'CPU/RAM isolation model', 'values' => ['budget' => 'Shared CPU/RAM', 'pro' => 'Shared CPU/RAM', 'turbo' => 'Dedicated Resources']],
                ['id' => 'spec_lb', 'label' => 'Load Balancer', 'icon' => 'fa-shield-alt', 'tooltip' => 'Traffic load balancer', 'values' => ['budget' => 'no', 'pro' => 'no', 'turbo' => 'yes']],
                ['id' => 'spec_net', 'label' => 'Network Speed', 'icon' => 'fa-network-wired', 'tooltip' => 'Port uplink bandwidth', 'values' => ['budget' => '500 Mbps', 'pro' => '1 Gbps', 'turbo' => '10 Gbps']],
            ];

            $allGroups = Capsule::table('tblproductgroups')->select('id', 'name')->orderBy('order', 'asc')->get();

            echo '<div class="pc-card">';
            echo '<form method="post" action="' . $moduleLink . '&action=save_matrix">';
            echo '<input type="hidden" name="matrix_id" value="' . $matrixId . '">';

            echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">';
            echo '  <h3 style="margin:0; font-size:17px; font-weight:800; color:#0F172A;"><i class="fas fa-edit text-primary"></i> Matrix Editor</h3>';
            echo '  <button type="submit" class="pc-btn pc-btn-success" style="padding:10px 22px; font-size:14px;"><i class="fas fa-save"></i> 💾 Save Matrix</button>';
            echo '</div>';

            echo '<div class="pc-editor-top-grid">';
            echo '  <div>';
            echo '    <div class="pc-form-group">';
            echo '      <label class="pc-form-label">Internal Matrix Name</label>';
            echo '      <input type="text" name="name" class="pc-input" value="' . plancomparison_h($name) . '" required>';
            echo '    </div>';
            echo '    <div class="pc-editor-meta-grid">';
            echo '      <div class="pc-form-group">';
            echo '        <label class="pc-form-label">Section Title</label>';
            echo '        <input type="text" name="title" class="pc-input" value="' . plancomparison_h($title) . '">';
            echo '      </div>';
            echo '      <div class="pc-form-group">';
            echo '        <label class="pc-form-label">Section Subtitle</label>';
            echo '        <input type="text" name="subtitle" class="pc-input" value="' . plancomparison_h($subtitle) . '">';
            echo '      </div>';
            echo '    </div>';
            echo '  </div>';

            echo '  <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px;">';
            echo '    <label class="pc-form-label">Target Product Groups</label>';
            echo '    <select name="groups[]" multiple size="6" class="pc-input" style="height:120px;">';
            foreach ($allGroups as $g) {
                $sel = in_array($g->id, $selectedGroups) ? 'selected' : '';
                echo '<option value="' . (int)$g->id . '" ' . $sel . '>' . plancomparison_h($g->name) . '</option>';
            }
            echo '    </select>';
            echo '    <div style="margin-top:10px;"><label><input type="checkbox" name="enabled" value="1" ' . ($enabled ? 'checked' : '') . '> <strong>Enable this Matrix</strong></label></div>';
            echo '  </div>';
            echo '</div>';

            // Columns / Tiers Management
            echo '<div style="margin-bottom:24px;">';
            echo '  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
            echo '    <h4 style="margin:0; font-size:15px; font-weight:700; color:#1E293B;"><i class="fas fa-layer-group text-primary"></i> Plan Tiers (Columns)</h4>';
            echo '  </div>';

            echo '  <div class="pc-tiers-grid" id="pcTiersGrid">';
            foreach ($tiers as $idx => $t) {
                echo '  <div style="background:#FFFFFF; border:2px solid ' . (!empty($t['highlight']) ? '#2563EB' : '#E2E8F0') . '; border-radius:12px; padding:16px;">';
                echo '    <div style="font-weight:700; font-size:12px; color:#64748B; margin-bottom:8px;">Tier #' . ($idx + 1) . '</div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">ID (slug)</label><input type="text" name="tiers[' . $idx . '][id]" value="' . plancomparison_h($t['id']) . '" class="pc-input"></div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">Tier Name</label><input type="text" name="tiers[' . $idx . '][name]" value="' . plancomparison_h($t['name']) . '" class="pc-input" style="font-weight:700;"></div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">Tagline</label><input type="text" name="tiers[' . $idx . '][tagline]" value="' . plancomparison_h($t['tagline']) . '" class="pc-input"></div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">Pill Badge</label><input type="text" name="tiers[' . $idx . '][badge]" value="' . plancomparison_h($t['badge']) . '" class="pc-input" placeholder="e.g. MOST POPULAR"></div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">Badge Color</label>';
                echo '      <select name="tiers[' . $idx . '][badge_color]" class="pc-input">';
                echo '        <option value="blue"' . ($t['badge_color'] === 'blue' ? ' selected' : '') . '>Royal Blue</option>';
                echo '        <option value="amber"' . ($t['badge_color'] === 'amber' ? ' selected' : '') . '>Amber / Orange</option>';
                echo '        <option value="green"' . ($t['badge_color'] === 'green' ? ' selected' : '') . '>Green</option>';
                echo '      </select>';
                echo '    </div>';
                echo '    <div class="pc-form-group"><label><input type="checkbox" name="tiers[' . $idx . '][highlight]" value="1" ' . (!empty($t['highlight']) ? 'checked' : '') . '> <strong>Highlight (Featured Card)</strong></label></div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">Button Text</label><input type="text" name="tiers[' . $idx . '][button_text]" value="' . plancomparison_h($t['button_text']) . '" class="pc-input"></div>';
                echo '    <div class="pc-form-group"><label class="pc-form-label">Button Link</label><input type="text" name="tiers[' . $idx . '][button_link]" value="' . plancomparison_h($t['button_link']) . '" class="pc-input"></div>';
                echo '  </div>';
            }
            echo '  </div>';
            echo '</div>';

            // Rows / Specs Management
            echo '<div style="margin-bottom:24px;">';
            echo '  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
            echo '    <h4 style="margin:0; font-size:15px; font-weight:700; color:#1E293B;"><i class="fas fa-list-ul text-primary"></i> Specification Rows & Values</h4>';
            echo '  </div>';

            echo '  <div class="pc-table-responsive"><table class="pc-table-grid">';
            echo '    <thead><tr>';
            echo '      <th style="width:260px;">Feature Label & Icon</th>';
            echo '      <th>Tooltip Description</th>';
            foreach ($tiers as $t) {
                echo '      <th style="text-align:center;">' . plancomparison_h($t['name']) . '</th>';
            }
            echo '    </tr></thead><tbody>';

            foreach ($specs as $sIdx => $s) {
                echo '    <tr>';
                echo '      <td>';
                echo '        <input type="hidden" name="specs[' . $sIdx . '][id]" value="' . plancomparison_h($s['id']) . '">';
                echo '        <div style="margin-bottom:6px;"><input type="text" name="specs[' . $sIdx . '][label]" value="' . plancomparison_h($s['label']) . '" class="pc-input" style="font-weight:700;"></div>';
                echo '        <div style="display:flex; align-items:center; gap:6px;"><span style="font-size:11px; color:#64748B;">Icon:</span><input type="text" name="specs[' . $sIdx . '][icon]" value="' . plancomparison_h($s['icon']) . '" class="pc-input" style="font-size:11.5px; height:28px;"></div>';
                echo '      </td>';
                echo '      <td>';
                echo '        <textarea name="specs[' . $sIdx . '][tooltip]" class="pc-input" rows="2" style="font-size:12px;">' . plancomparison_h($s['tooltip']) . '</textarea>';
                echo '      </td>';
                foreach ($tiers as $t) {
                    $tId = $t['id'];
                    $val = isset($s['values'][$tId]) ? $s['values'][$tId] : '';
                    echo '      <td>';
                    echo '        <input type="text" name="specs[' . $sIdx . '][values][' . $tId . ']" value="' . plancomparison_h($val) . '" class="pc-input" placeholder="e.g. Yes, No, DDR4...">';
                    echo '      </td>';
                }
                echo '    </tr>';
            }

            echo '    </tbody></table></div>';
            echo '</div>';

            echo '<div style="margin-top:20px; text-align:right;">';
            echo '  <button type="submit" class="pc-btn pc-btn-success" style="padding:10px 24px; font-size:14px;"><i class="fas fa-save"></i> 💾 Save Matrix Specifications</button>';
            echo '</div>';

            echo '</form>';
            echo '</div>';
        }

        // 3. Display Settings
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
