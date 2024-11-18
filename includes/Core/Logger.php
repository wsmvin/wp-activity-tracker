<?php
    namespace WPActivityTracker\Core;

    class Logger {
        private $table_name;

        public function __construct() {
            global $wpdb;
            $this->table_name = $wpdb->prefix . 'activity_logs';
        }

        public function log($user_id, $action_type, $object_type, $object_id, $data = '') {
            global $wpdb;
            error_log("Logging activity: User: $user_id, Action: $action_type, Object: $object_type, ID: $object_id");
            error_log("Data: " . print_r($data, true));

            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'user_id' => $user_id,
                    'action_type' => $action_type,
                    'object_type' => $object_type,
                    'object_id' => $object_id,
                    'old_value' => $data,
                    'action_date' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s', '%s')
            );

            if ($result === false) {
                error_log("DB Error: " . $wpdb->last_error);
            }
        }
    }
