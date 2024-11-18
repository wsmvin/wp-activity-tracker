<?php
// wp-activity-tracker/includes/Admin/LogsSorting.php
    namespace WPActivityTracker\Admin;

    class LogsSorting {
        private $orderby;
        private $order;

        public function __construct() {
            $this->orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'action_date';
            $this->order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';

            if (!in_array($this->order, ['ASC', 'DESC'])) {
                $this->order = 'DESC';
            }
        }

        public function get_order_clause() {
            return esc_sql($this->orderby) . ' ' . esc_sql($this->order);
        }

        public function display_headers() {
            $headers = array(
                'action_date' => 'Date',
                'user_id' => 'User',
                'action_type' => 'Action',
                'details' => 'Details'
            );

            foreach ($headers as $column => $title) {
                if ($column === 'details') {
                    echo '<th>' . esc_html($title) . '</th>';
                    continue;
                }

                $current_order = ($this->orderby === $column) ? $this->order : '';
                $order_link = $this->get_sort_link($column);
                $class = $current_order ? ' sorted ' . strtolower($current_order) : ' sortable';

                printf(
                    '<th class="%s"><a href="%s"><span>%s</span><span class="sorting-indicator"></span></a></th>',
                    esc_attr($class),
                    esc_url($order_link),
                    esc_html($title)
                );
            }
        }

        private function get_sort_link($column) {
            $params = $_GET;
            $params['orderby'] = $column;
            $params['order'] = ($this->orderby === $column && $this->order === 'ASC') ? 'DESC' : 'ASC';

            return add_query_arg($params);
        }
    }