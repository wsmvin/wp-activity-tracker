<?php
    namespace WPActivityTracker\Trackers;

    class MediaTracker extends AbstractTracker {
        protected $tracker_name = 'media';

        protected function register_hooks() {
            if ($this->is_action_enabled('upload')) {
                add_action('add_attachment', array($this, 'track_upload'));
            }

            if ($this->is_action_enabled('delete')) {
                add_action('delete_attachment', array($this, 'track_deletion'));
            }
        }

        public function track_upload($attachment_id) {
            $attachment = get_post($attachment_id);
            $file = get_attached_file($attachment_id);
            $file_type = wp_check_filetype(basename($file));
            $file_size = filesize($file);

            $metadata = wp_get_attachment_metadata($attachment_id);

            $this->logger->log(
                get_current_user_id(),
                'upload',
                'media',
                $attachment_id,
                json_encode(array(
                    'title' => $attachment->post_title,
                    'filename' => basename($file),
                    'type' => $file_type['type'],
                    'size' => size_format($file_size),
                    'dimensions' => isset($metadata['width']) ? $metadata['width'] . 'x' . $metadata['height'] : 'N/A'
                ), JSON_UNESCAPED_UNICODE)
            );
        }

        public function track_deletion($attachment_id) {
            $attachment = get_post($attachment_id);
            $file = get_attached_file($attachment_id);

            $this->logger->log(
                get_current_user_id(),
                'delete',
                'media',
                $attachment_id,
                json_encode(array(
                    'title' => $attachment->post_title,
                    'filename' => basename($file)
                ), JSON_UNESCAPED_UNICODE)
            );
        }
    }