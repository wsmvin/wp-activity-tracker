<?php
    namespace WPActivityTracker\Admin;

    class LogsView {
        private $items_per_page = 20;
        private $filters;
        private $sorting;
        private $pagination;

        public function __construct() {
            $this->filters = new LogsFilter();
            $this->sorting = new LogsSorting();
            $this->pagination = new LogsPagination($this->items_per_page);
        }
        private function get_change_summary($log) {
            $changes = json_decode($log->old_value, true);

            if (!$changes) return '';

            if ($log->action_type === 'option_update') {
                if (isset($changes['option_name']) && isset($changes['old']) && isset($changes['new'])) {
                    $oldData = is_string($changes['old']) ? json_decode($changes['old'], true) : $changes['old'];
                    $newData = is_string($changes['new']) ? json_decode($changes['new'], true) : $changes['new'];

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $diff = $this->find_deep_diff($oldData, $newData);
                        if (!empty($diff)) {
                            return esc_html($changes['option_name'] . ': ' . implode(', ', $diff));
                        }
                    }
                }
            }

            if ($log->action_type === 'acf_option_update') {
                if (isset($changes['field_label']) && isset($changes['old']) && isset($changes['new'])) {
                    $summary = $changes['field_label'] . ': ';

                    if (is_array($changes['old']) && is_array($changes['new'])) {
                        $diff = $this->get_array_diff($changes['old'], $changes['new']);
                        if (!empty($diff)) {
                            $summary .= $this->format_diff_summary($diff);
                        }
                    } else {
                        $summary .= sprintf(
                            '%s → %s',
                            substr(strip_tags($changes['old']), 0, 100),
                            substr(strip_tags($changes['new']), 0, 100)
                        );
                    }
                    return esc_html($summary);
                }
            }

            if (in_array($log->action_type, ['update', 'insert', 'delete']) && !empty($changes)) {
                $summary = [];
                foreach ($changes as $key => $values) {
                    if (isset($values['old']) && isset($values['new']) && $values['old'] !== $values['new']) {
                        $old_value = substr(strip_tags($values['old']), 0, 100);
                        $new_value = substr(strip_tags($values['new']), 0, 100);
                        $summary[] = sprintf('%s: %s → %s', $key, $old_value, $new_value);
                    }
                }
                return esc_html(implode('; ', $summary));
            }

            return '';
        }
        private function find_deep_diff($old, $new, $path = '') {
            $differences = [];

            if (!is_array($old) || !is_array($new)) {
                if ($old !== $new) {
                    return [$path . ": {$old} → {$new}"];
                }
                return [];
            }

            foreach ($new as $key => $value) {
                $currentPath = $path ? $path . '.' . $key : $key;

                if (!isset($old[$key])) {
                    $differences[] = $currentPath . " added: " . $this->format_value_brief($value);
                    continue;
                }

                if (is_array($value) && is_array($old[$key])) {
                    $childDiffs = $this->find_deep_diff($old[$key], $value, $currentPath);
                    $differences = array_merge($differences, $childDiffs);
                } elseif ($value !== $old[$key]) {
                    $differences[] = $currentPath . ": " . $this->format_value_brief($old[$key]) . " → " . $this->format_value_brief($value);
                }
            }

            foreach ($old as $key => $value) {
                if (!isset($new[$key])) {
                    $currentPath = $path ? $path . '.' . $key : $key;
                    $differences[] = $currentPath . " removed";
                }
            }

            return $differences;
        }
        private function format_value_brief($value) {
            if (is_array($value)) {
                return '[Array]';
            }
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_null($value)) {
                return 'null';
            }
            return (string)$value;
        }
        private function get_array_diff($old, $new) {
            $diff = [];
            foreach ($new as $key => $value) {
                if (!isset($old[$key]) || $old[$key] !== $value) {
                    $diff[$key] = [
                        'old' => isset($old[$key]) ? $old[$key] : null,
                        'new' => $value
                    ];
                }
            }
            return $diff;
        }

        private function format_diff_summary($diff) {
            $summary = [];
            foreach ($diff as $key => $values) {
                $old = is_array($values['old']) ? '[Array]' : substr(strip_tags((string)$values['old']), 0, 100);
                $new = is_array($values['new']) ? '[Array]' : substr(strip_tags((string)$values['new']), 0, 100);
                $summary[] = sprintf('%s: %s → %s', $key, $old, $new);
            }
            return implode('; ', $summary);
        }

        private function format_value($value) {
            if (is_null($value)) return '<em>null</em>';
            if ($value === '') return '<em>empty</em>';
            if (is_bool($value)) return $value ? 'true' : 'false';

            if (is_string($value) && ($this->is_json($value) || $this->is_serialized($value))) {
                $decoded = $this->is_json($value) ? json_decode($value, true) : unserialize($value);
                if ($decoded !== false) {
                    return '<pre class="wat-json">' .
                           esc_html(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                           '</pre>';
                }
            }

            return esc_html($value);
        }

        private function is_serialized($value) {
            if (!is_string($value)) return false;
            $data = @unserialize($value);
            return $value === 'b:0;' || $data !== false;
        }


        private function format_change_value($value) {
            if (is_array($value)) {
                return '[Array]';
            }
            if (is_object($value)) {
                return '[Object]';
            }
            if ($this->is_json($value)) {
                $decoded = json_decode($value, true);
                return '[' . $this->summarize_json($decoded) . ']';
            }
            return substr(strip_tags($value), 0, 50);
        }

        private function is_json($string) {
            if (!is_string($string)) return false;
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        }

        private function summarize_json($data) {
            if (is_array($data)) {
                $keys = array_keys($data);
                return implode(', ', array_slice($keys, 0, 3));
            }
            return 'JSON';
        }

        private function format_post_changes($changes) {
            $summary = [];
            foreach ($changes as $key => $value) {
                if (isset($value['old']) && isset($value['new'])) {
                    $summary[] = $key;
                }
            }
            return implode(', ', $summary);
        }
        public function display() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'activity_logs';

            $where_data = $this->filters->get_where_clause();
            $order_clause = $this->sorting->get_order_clause();

            $total_items = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE {$where_data['where']}",
                    $where_data['params']
                )
            );

            $offset = $this->pagination->get_offset();
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


    }
