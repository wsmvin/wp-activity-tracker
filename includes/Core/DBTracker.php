<?php
    namespace WPActivityTracker\Core;

    class DBTracker {
        private static $instance = null;
        private $logger;
        private $ignored_options;

        private function __construct() {
            $this->logger = new Logger();
            $this->init_ignored_options();
        }

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function init() {
            add_filter('query', array($this, 'track_query'), 999);
            add_action('wp_initialize_site', array($this, 'clear_cache'));
            add_action('wp_delete_site', array($this, 'clear_cache'));
        }

        private function init_ignored_options() {
            $this->ignored_options = [
                'cron',
                'doing_cron',
                '_transient_',
                '_site_transient_',
                'actionscheduler_',
                'template_lock',
                'recently_edited',
                'wp_session_',
                'session_tokens',
                'active_plugins',
                'uninstall_plugins',
                'fileupload_',
                'theme_switched',
                'auto_updater',
                'core_updater',
                'can_compress_scripts',
                'rewrite_rules'
            ];
        }

        private function should_track_option($option_name, $old_value, $new_value) {
            // Пропускаємо ігноровані опції
            foreach ($this->ignored_options as $ignored) {
                if (strpos($option_name, $ignored) !== false) {
                    return false;
                }
            }

            // Перевіряємо чи дійсно змінилося значення
            if ($old_value === $new_value) {
                return false;
            }

            // Якщо це серіалізовані дані
            if (is_serialized($old_value) && is_serialized($new_value)) {
                $old = unserialize($old_value);
                $new = unserialize($new_value);
                return $this->has_real_changes($old, $new);
            }

            // Для JSON даних
            if ($this->is_json($old_value) && $this->is_json($new_value)) {
                $old = json_decode($old_value, true);
                $new = json_decode($new_value, true);
                return $this->has_real_changes($old, $new);
            }

            return true;
        }

        private function is_json($string) {
            if (!is_string($string)) return false;
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        }

        private function has_real_changes($old, $new) {
            if (!is_array($old) || !is_array($new)) {
                return $old !== $new;
            }

            // Ігноруємо технічні поля
            $skip_fields = ['modified', 'modified_gmt', 'post_modified', 'post_modified_gmt'];

            foreach ($old as $key => $value) {
                if (in_array($key, $skip_fields)) continue;

                if (!array_key_exists($key, $new)) {
                    return true;
                }

                if (is_array($value)) {
                    if ($this->has_real_changes($value, $new[$key])) {
                        return true;
                    }
                } else if ($value !== $new[$key]) {
                    return true;
                }
            }

            foreach ($new as $key => $value) {
                if (!array_key_exists($key, $old)) {
                    return true;
                }
            }

            return false;
        }

        public function track_wp_option($option_name, $old_value, $new_value) {
            if (!$this->should_track_option($option_name, $old_value, $new_value)) {
                return;
            }

            $this->logger->log(
                get_current_user_id(),
                'option_update',
                'wp_option',
                0,
                json_encode([
                    'option_name' => $option_name,
                    'old' => $this->sanitize_value($old_value),
                    'new' => $this->sanitize_value($new_value)
                ], JSON_UNESCAPED_UNICODE)
            );
        }
    }