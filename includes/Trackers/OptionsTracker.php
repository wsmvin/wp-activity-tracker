<?php
    namespace WPActivityTracker\Trackers;

    class OptionsTracker extends AbstractTracker {
        protected $tracker_name = 'options';
        private $processed_options = [];

        protected function register_hooks() {
            add_action('updated_option', array($this, 'track_wp_option'), 10, 3);
            add_action('acf/update_value', array($this, 'track_acf_field'), 10, 4);
        }

        private function should_skip_option($option_name) {
            $skip_options = [
                '_transient_',
                '_site_transient_',
                'cron',
                'doing_cron',
                'acf_version',
                'rewrite_rules',
                'recently_edited',
                '_wp_session_',
                'session_tokens'
            ];

            foreach ($skip_options as $skip) {
                if (strpos($option_name, $skip) !== false) {
                    return true;
                }
            }
            return false;
        }

        public function track_wp_option($option_name, $old_value, $new_value) {
            if ($this->should_skip_option($option_name) || $old_value === $new_value) {
                return;
            }

            if (isset($this->processed_options[$option_name])) {
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

        public function track_acf_field($value, $post_id, $field, $original_value) {
            if ($post_id !== 'options' || $value === $original_value) {
                return $value;
            }

            $this->processed_options["options_{$field['name']}"] = true;

            $this->logger->log(
                get_current_user_id(),
                'acf_option_update',
                'acf_option',
                0,
                json_encode([
                    'field_name' => $field['name'],
                    'field_label' => $field['label'],
                    'old' => $this->sanitize_value($original_value),
                    'new' => $this->sanitize_value($value)
                ], JSON_UNESCAPED_UNICODE)
            );

            return $value;
        }

        private function sanitize_value($value) {
            if (empty($value)) return '';
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            return strval($value);
        }
    }