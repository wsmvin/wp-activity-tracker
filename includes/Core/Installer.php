<?php
    namespace WPActivityTracker\Core;

    class Installer {
        public function install() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'activity_logs';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20),
            old_value longtext,
            action_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY action_date (action_date)
        ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            error_log("Activity Logs table created/updated");
        }
    }