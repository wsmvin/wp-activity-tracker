<?php
    namespace WPActivityTracker\Admin;

    use WPActivityTracker\Core\Settings;

    class SettingsPage {
        public function render() {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (isset($_POST['wat_save_settings'])) {
                $this->save_settings();
            }

            $this->render_form();
        }

        private function save_settings() {
            check_admin_referer('wat_settings_nonce');

            $settings = [
                'tracking' => [
                    'posts' => !empty($_POST['wat_track_posts']),
                    'pages' => !empty($_POST['wat_track_pages']),
                    'media' => !empty($_POST['wat_track_media']),
                    'users' => !empty($_POST['wat_track_users']),
                    'options' => !empty($_POST['wat_track_options'])
                ],
                'retention' => [
                    'days' => intval($_POST['wat_retention_days']),
                    'delete_old' => !empty($_POST['wat_auto_delete'])
                ]
            ];

            update_option('wat_settings', $settings);
            add_settings_error('wat_settings', 'settings_updated', 'Settings saved.', 'updated');
        }

        private function render_form() {
            $settings = get_option('wat_settings', []);
            require WAT_PLUGIN_DIR . 'templates/admin/settings.php';
        }
    }