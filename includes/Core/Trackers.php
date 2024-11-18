<?php
// wp-activity-tracker/includes/Core/Trackers.php
    namespace WPActivityTracker\Core;

    use WPActivityTracker\Trackers\PostTracker;
    use WPActivityTracker\Trackers\MediaTracker;
    use WPActivityTracker\Trackers\UsersTracker;
    use WPActivityTracker\Trackers\OptionsTracker;

    class Trackers {
        private $trackers = [];
        private $logger;
        private $settings;

        public function __construct() {
            error_log('Trackers constructor called');
            $this->logger = new Logger();
            $this->settings = Settings::get_instance();
            $this->init_trackers();
        }

        private function init_trackers() {
            $settings = $this->settings->get_setting('tracking', []);
            error_log('Initializing trackers with settings: ' . print_r($settings, true));

            if (!empty($settings['posts'])) {
                error_log('Adding PostTracker');
                $this->trackers['posts'] = new PostTracker($this->logger);
            }

            if (!empty($settings['users'])) {
                error_log('Adding UserTracker');
                $this->trackers['users'] = new UsersTracker($this->logger);
            }

            if (!empty($settings['media'])) {
                error_log('Adding MediaTracker');
                $this->trackers['media'] = new MediaTracker($this->logger);
            }

            if (!empty($settings['options'])) {
                error_log('Adding OptionsTracker');
                $this->trackers['options'] = new OptionsTracker($this->logger);
            }
        }

        public function init() {
            foreach ($this->trackers as $tracker) {
                $tracker->init();
            }
        }
    }