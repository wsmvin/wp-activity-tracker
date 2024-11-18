<?php
    namespace WPActivityTracker\Core;

    class Settings {
        const OPTION_NAME = 'wat_settings';
        private static $instance = null;
        private $settings;

        private function __construct() {
            $this->load_settings();
        }

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function load_settings() {
            $defaults = array(
                'tracking' => array(
                    'posts' => true,
                    'pages' => true,
                    'media' => true,
                    'users' => true,
                    'options' => true
                ),
                'retention' => array(
                    'days' => 30,
                    'delete_old' => false
                )
            );

            $this->settings = get_option(self::OPTION_NAME, $defaults);
        }

        public function get_setting($key = null, $default = null) {
            if (null === $key) {
                return $this->settings;
            }
            return isset($this->settings[$key]) ? $this->settings[$key] : $default;
        }

        public function is_tracker_enabled($tracker) {
            $tracking = $this->get_setting('tracking', []);
            return !empty($tracking[$tracker]);
        }

        public function is_action_enabled($tracker, $action) {
            return $this->is_tracker_enabled($tracker);
        }
    }