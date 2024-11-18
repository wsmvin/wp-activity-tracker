<?php
    namespace WPActivityTracker\Core;

    class DBTracker {
        private $logger;
        private $original_values = [];
        private static $instance = null;

        private function __construct() {
            $this->logger = new Logger();
            $this->init();
        }

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function init() {
            // Відстеження всіх запитів до БД
            add_filter('query', array($this, 'track_query'), 999);

            // Додаткове відстеження для специфічних дій
            add_action('wp_initialize_site', array($this, 'clear_cache'));
            add_action('wp_delete_site', array($this, 'clear_cache'));
        }

        public function track_query($query) {
            global $wpdb;

            // Пропускаємо SELECT запити
            if (stripos(trim($query), 'SELECT') === 0) {
                return $query;
            }

            // Аналізуємо запит
            $parsed = $this->parse_query($query);
            if (!$parsed) {
                return $query;
            }

            // Зберігаємо оригінальні дані перед зміною
            if ($parsed['action'] !== 'INSERT') {
                $this->store_original_values($parsed);
            }

            // Логуємо після виконання запиту
            add_action('shutdown', function() use ($parsed, $query) {
                $this->log_changes($parsed, $query);
            }, 999);

            return $query;
        }

        private function parse_query($query) {
            // INSERT запити
            if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?\s+/i', $query, $matches)) {
                return [
                    'action' => 'INSERT',
                    'table' => $matches[1],
                    'query' => $query
                ];
            }

            // UPDATE запити
            if (preg_match('/UPDATE\s+`?(\w+)`?\s+SET\s+(.*?)\s+WHERE\s+(.*)/i', $query, $matches)) {
                return [
                    'action' => 'UPDATE',
                    'table' => $matches[1],
                    'set' => $matches[2],
                    'where' => $matches[3],
                    'query' => $query
                ];
            }

            // DELETE запити
            if (preg_match('/DELETE\s+FROM\s+`?(\w+)`?\s+WHERE\s+(.*)/i', $query, $matches)) {
                return [
                    'action' => 'DELETE',
                    'table' => $matches[1],
                    'where' => $matches[2],
                    'query' => $query
                ];
            }

            return null;
        }

        private function store_original_values($parsed) {
            global $wpdb;

            if ($parsed['action'] === 'UPDATE' || $parsed['action'] === 'DELETE') {
                $table = $parsed['table'];
                $where = $parsed['where'];

                // Отримуємо оригінальні дані
                $select_query = "SELECT * FROM `$table` WHERE $where";
                $original_data = $wpdb->get_results($select_query, ARRAY_A);

                if ($original_data) {
                    $this->original_values[$table] = $original_data;
                }
            }
        }

        private function log_changes($parsed, $original_query) {
            global $wpdb;
            $table = $parsed['table'];
            $prefix = $wpdb->prefix;

            // Пропускаємо системні та тимчасові таблиці
            if ($this->should_skip_table($table)) {
                return;
            }

            switch ($parsed['action']) {
                case 'INSERT':
                    $this->log_insert($table, $original_query);
                    break;

                case 'UPDATE':
                    $this->log_update($table, $parsed, $original_query);
                    break;

                case 'DELETE':
                    $this->log_delete($table, $parsed);
                    break;
            }
        }

        private function should_skip_table($table) {
            global $wpdb;
            $skip_tables = [
                $wpdb->prefix . 'activity_logs',
                $wpdb->prefix . 'sessions',
                $wpdb->prefix . 'actionscheduler_actions',
                $wpdb->prefix . 'actionscheduler_logs',
            ];

            return in_array($table, $skip_tables);
        }

        private function log_insert($table, $query) {
            global $wpdb;
            $insert_id = $wpdb->insert_id;

            if ($insert_id) {
                $new_data = $wpdb->get_row("SELECT * FROM `$table` WHERE ID = $insert_id", ARRAY_A);

                $this->logger->log(
                    get_current_user_id(),
                    'insert',
                    $this->get_table_type($table),
                    $insert_id,
                    json_encode([
                        'table' => $table,
                        'data' => $new_data
                    ])
                );
            }
        }

        private function log_update($table, $parsed, $query) {
            if (isset($this->original_values[$table])) {
                foreach ($this->original_values[$table] as $original) {
                    $id = isset($original['ID']) ? $original['ID'] : 0;
                    $new_data = $wpdb->get_row("SELECT * FROM `$table` WHERE ID = $id", ARRAY_A);

                    if ($this->has_real_changes($original, $new_data)) {
                        $this->logger->log(
                            get_current_user_id(),
                            'update',
                            $this->get_table_type($table),
                            $id,
                            json_encode([
                                'table' => $table,
                                'old' => $original,
                                'new' => $new_data
                            ])
                        );
                    }
                }
            }
        }

        private function log_delete($table, $parsed) {
            if (isset($this->original_values[$table])) {
                foreach ($this->original_values[$table] as $original) {
                    $id = isset($original['ID']) ? $original['ID'] : 0;

                    $this->logger->log(
                        get_current_user_id(),
                        'delete',
                        $this->get_table_type($table),
                        $id,
                        json_encode([
                            'table' => $table,
                            'data' => $original
                        ])
                    );
                }
            }
        }

        private function has_real_changes($old_data, $new_data) {
            if (!is_array($old_data) || !is_array($new_data)) {
                return false;
            }

            $skip_fields = ['modified', 'modified_gmt', 'post_modified', 'post_modified_gmt'];

            foreach ($old_data as $key => $value) {
                if (in_array($key, $skip_fields)) continue;

                if (!isset($new_data[$key]) || $new_data[$key] !== $value) {
                    return true;
                }
            }

            return false;
        }

        private function get_table_type($table) {
            global $wpdb;
            $prefix = $wpdb->prefix;

            $table_map = [
                $prefix . 'posts' => 'post',
                $prefix . 'postmeta' => 'postmeta',
                $prefix . 'options' => 'option',
                $prefix . 'users' => 'user',
                $prefix . 'usermeta' => 'usermeta',
                $prefix . 'terms' => 'term',
                $prefix . 'termmeta' => 'termmeta'
            ];

            return isset($table_map[$table]) ? $table_map[$table] : 'other';
        }

        public function clear_cache() {
            $this->original_values = [];
        }
    }