<?php
    namespace WPActivityTracker\Admin;

    class LogsView {
        private int $items_per_page = 20;
        private LogsFilter $filters;
        private LogsSorting $sorting;
        private LogsPagination $pagination;

        public function __construct() {
            $this->filters = new LogsFilter();
            $this->sorting = new LogsSorting();
            $this->pagination = new LogsPagination($this->items_per_page);
        }

        public function display(): void {
            global $wpdb;
            $table_name = $wpdb->prefix . 'activity_logs';

            $where_data = $this->filters->get_where_clause();
            $order_clause = $this->sorting->get_order_clause();

            // Count query
            if (empty($where_data['params'])) {
                $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE {$where_data['where']}");
            } else {
                $total_items = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE {$where_data['where']}",
                        $where_data['params']
                    )
                );
            }

            // Main query
            $offset = $this->pagination->get_offset();

            if (empty($where_data['params'])) {
                $query = $wpdb->prepare(
                    "SELECT * FROM $table_name 
                WHERE {$where_data['where']} 
                ORDER BY {$order_clause}
                LIMIT %d OFFSET %d",
                    $this->items_per_page,
                    $offset
                );
            } else {
                $query = $wpdb->prepare(
                    "SELECT * FROM $table_name 
                WHERE {$where_data['where']} 
                ORDER BY {$order_clause}
                LIMIT %d OFFSET %d",
                    array_merge(
                        $where_data['params'],
                        array($this->items_per_page, $offset)
                    )
                );
            }

            $logs = $wpdb->get_results($query);

            require WAT_PLUGIN_DIR . 'templates/admin/logs.php';
        }
        private function render_changes($changes, $level = 0) {
            foreach ($changes as $key => $value) {
                $indent = str_repeat('    ', $level);

                if (is_array($value)) {
                    if (isset($value['old']) && isset($value['new'])) {
                        echo '<div class="wat-change-row">';
                        echo '<div class="wat-change-label">' . esc_html($key) . ':</div>';
                        echo '<div class="wat-change-values">';
                        echo '<div class="wat-old-value"><strong>Old:</strong> ' . $this->format_value($value['old']) . '</div>';
                        echo '<div class="wat-new-value"><strong>New:</strong> ' . $this->format_value($value['new']) . '</div>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="wat-change-section">';
                        echo '<div class="wat-change-section-title">' . esc_html($key) . ':</div>';
                        echo '<div class="wat-change-section-content">';
                        $this->render_changes($value, $level + 1);
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="wat-change-row">';
                    echo '<div class="wat-change-label">' . esc_html($key) . ':</div>';
                    echo '<div class="wat-change-value">' . $this->format_value($value) . '</div>';
                    echo '</div>';
                }
            }
        }

        private function format_value($value) {
            if (is_null($value)) return '<em>null</em>';
            if ($value === '') return '<em>empty</em>';
            if (is_bool($value)) return $value ? 'true' : 'false';

            if (is_string($value) && (
                    substr($value, 0, 1) === '{' ||
                    substr($value, 0, 1) === '['
                )) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return '<pre class="wat-json">' .
                           esc_html(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                           '</pre>';
                }
            }

            return esc_html($value);
        }
    }
