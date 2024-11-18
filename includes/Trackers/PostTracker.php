<?php
    namespace WPActivityTracker\Trackers;

    use WPActivityTracker\Core\Logger;
    use WPActivityTracker\Trackers\AbstractTracker;

    class PostTracker extends AbstractTracker {
        protected $tracker_name = 'posts';
        private $old_post_data = array();

        protected function register_hooks() {
            error_log('PostTracker register_hooks called');

            // Зберігаємо старі дані перед оновленням
            add_filter('wp_insert_post_data', array($this, 'before_post_update'), 10, 2);

            // Відстежуємо зміни після оновлення
            add_action('post_updated', array($this, 'after_post_update'), 10, 3);

            // Видалення
            add_action('before_delete_post', array($this, 'track_deletion'));

            // Зміна статусу
            add_action('transition_post_status', array($this, 'track_status'), 10, 3);

            error_log('PostTracker hooks registered with filters');
        }

        public function before_post_update($data, $postarr) {
            error_log('before_post_update triggered');

            if (isset($postarr['ID'])) {
                $this->old_post_data[$postarr['ID']] = get_post($postarr['ID']);
                error_log('Stored old data for post ' . $postarr['ID']);
            }

            return $data;
        }

        public function after_post_update($post_id, $post_after, $post_before) {
            error_log('after_post_update triggered for post ' . $post_id);

            if (!isset($this->old_post_data[$post_id])) {
                error_log('No old data found for post ' . $post_id);
                return;
            }

            $changes = array();
            $fields_to_track = array(
                'post_title' => 'Title',
                'post_content' => 'Content',
                'post_excerpt' => 'Excerpt'
            );

            foreach ($fields_to_track as $field => $label) {
                if ($this->old_post_data[$post_id]->$field !== $post_after->$field) {
                    $changes[$label] = array(
                        'old' => $this->old_post_data[$post_id]->$field,
                        'new' => $post_after->$field
                    );
                    error_log("Change detected in $label");
                }
            }

            if (!empty($changes)) {
                error_log('Logging changes: ' . print_r($changes, true));
                $this->logger->log(
                    get_current_user_id(),
                    'update',
                    $post_after->post_type,
                    $post_id,
                    json_encode($changes, JSON_UNESCAPED_UNICODE)
                );
            }

            // Очищаємо старі дані
            unset($this->old_post_data[$post_id]);
        }

        public function track_deletion($post_id) {
            error_log('track_deletion called for post ' . $post_id);
            $post = get_post($post_id);

            if (!$post) {
                return;
            }

            $this->logger->log(
                get_current_user_id(),
                'delete',
                $post->post_type,
                $post_id,
                json_encode(array(
                    'title' => $post->post_title
                ), JSON_UNESCAPED_UNICODE)
            );
        }

        public function track_status($new_status, $old_status, $post) {
            error_log("track_status called: $old_status -> $new_status");

            if ($new_status === $old_status) {
                return;
            }

            $this->logger->log(
                get_current_user_id(),
                'status_change',
                $post->post_type,
                $post->ID,
                json_encode(array(
                    'old_status' => $old_status,
                    'new_status' => $new_status,
                    'title' => $post->post_title
                ), JSON_UNESCAPED_UNICODE)
            );
        }
    }