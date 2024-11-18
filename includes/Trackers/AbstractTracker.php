<?php
    namespace WPActivityTracker\Trackers;

    use WPActivityTracker\Core\Settings;
    use WPActivityTracker\Core\Logger;

    namespace WPActivityTracker\Trackers;

    use WPActivityTracker\Core\Settings;
    use WPActivityTracker\Core\Logger;

    abstract class AbstractTracker {
        protected $logger;
        protected $settings;
        protected $tracker_name;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
            $this->settings = Settings::get_instance();
        }

        public function init() {
            if ($this->is_enabled()) {
                $this->register_hooks();
            }
        }

        abstract protected function register_hooks();

        protected function is_enabled() {
            return $this->settings->is_tracker_enabled($this->tracker_name);
        }

        protected function is_action_enabled($action) {
            return $this->settings->is_action_enabled($this->tracker_name, $action);
        }
    }