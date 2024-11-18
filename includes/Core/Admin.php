<?php
    namespace WPActivityTracker\Core;

    use WPActivityTracker\Admin\SettingsPage;
    use WPActivityTracker\Admin\LogsView;

    class Admin {
        private $settings_page;
        private $logs_view;

        public function __construct() {
            $this->settings_page = new SettingsPage();
            $this->logs_view = new LogsView();
        }

        public function init() {
            add_action('admin_menu', array($this, 'add_menus'));
            add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        }

        public function register_assets($hook) {
            if (!strpos($hook, 'activity-log')) {
                return;
            }

            wp_enqueue_style('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-datepicker');

            wp_enqueue_style(
                'wat-admin',
                WAT_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WAT_VERSION
            );

            wp_enqueue_script(
                'wat-admin',
                WAT_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-datepicker'),
                WAT_VERSION,
                true
            );
        }

        public function add_menus() {
            add_menu_page(
                'Activity Logs',
                'Activity Logs',
                'manage_options',
                'activity-logs',
                array($this->logs_view, 'display'),
                'dashicons-list-view'
            );

            add_submenu_page(
                'activity-logs',
                'Settings',
                'Settings',
                'manage_options',
                'activity-tracker-settings',
                array($this->settings_page, 'render')
            );
        }
    }