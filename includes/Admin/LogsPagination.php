<?php
    // wp-activity-tracker/includes/Admin/LogsPagination.php
    namespace WPActivityTracker\Admin;

    class LogsPagination {
        private $items_per_page;
        private $current_page;

        public function __construct($items_per_page) {
            $this->items_per_page = $items_per_page;
            $this->current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        }

        public function get_offset() {
            return ($this->current_page - 1) * $this->items_per_page;
        }

        public function display($total_items) {
            $total_pages = ceil($total_items / $this->items_per_page);

            if ($total_pages <= 1) {
                return;
            }

            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $this->current_page
            ));
            echo '</div></div>';
        }
    }