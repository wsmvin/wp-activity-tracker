<?php
    namespace WPActivityTracker\Admin;

    class LogsFilter {
        private $user_id;
        private $action_type;
        private $date_from;
        private $date_to;

        public function __construct() {
            $this->user_id = isset($_GET['user_filter']) ? intval($_GET['user_filter']) : 0;
            $this->action_type = isset($_GET['action_filter']) ? sanitize_text_field($_GET['action_filter']) : '';
            $this->date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
            $this->date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        }

        public function get_where_clause() {
            $where = array('1=1');
            $params = array();

            if ($this->user_id > 0) {
                $where[] = 'user_id = %d';
                $params[] = $this->user_id;
            }

            if (!empty($this->action_type)) {
                $where[] = 'action_type = %s';
                $params[] = $this->action_type;
            }

            if (!empty($this->date_from)) {
                $where[] = 'action_date >= %s';
                $params[] = $this->date_from . ' 00:00:00';
            }

            if (!empty($this->date_to)) {
                $where[] = 'action_date <= %s';
                $params[] = $this->date_to . ' 23:59:59';
            }

            return array(
                'where' => implode(' AND ', $where),
                'params' => $params
            );
        }

        public function display() {
            global $wpdb;

            // User filter
            $users = get_users(['fields' => ['ID', 'user_login']]);
            echo '<select name="user_filter">';
            echo '<option value="0">All Users</option>';
            foreach ($users as $user) {
                printf(
                    '<option value="%d" %s>%s</option>',
                    $user->ID,
                    selected($this->user_id, $user->ID, false),
                    esc_html($user->user_login)
                );
            }
            echo '</select>';

            // Action type filter
            $types = $wpdb->get_col("SELECT DISTINCT action_type FROM {$wpdb->prefix}activity_logs WHERE action_type <> ''");

            echo '<select name="action_filter">';
            echo '<option value="">All Actions</option>';
            foreach ($types as $type) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($type),
                    selected($this->action_type, $type, false),
                    esc_html($type)
                );
            }
            echo '</select>';

            // Date filters
            printf(
                '<input type="text" name="date_from" class="datepicker" placeholder="From Date" value="%s" />',
                esc_attr($this->date_from)
            );
            printf(
                '<input type="text" name="date_to" class="datepicker" placeholder="To Date" value="%s" />',
                esc_attr($this->date_to)
            );
        }
    }