<?php
    namespace WPActivityTracker\Core;

    class Plugin {
        private $tracker;
        private $admin;
        private $settings;

        public function __construct() {
            error_log("Plugin constructor called");
            $this->settings = Settings::get_instance();
            $this->tracker = new Trackers();
            $this->admin = new Admin();
        }

        public function init() {
            error_log("Plugin init called");
            error_log("Current settings: " . print_r($this->settings->get_setting(), true));
            $this->tracker->init();
            $this->admin->init();
        }
    }



