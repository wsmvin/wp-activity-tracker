<?php
    namespace WPActivityTracker\Trackers;
    use WPActivityTracker\Core\Logger;
    use WPActivityTracker\Trackers\AbstractTracker;
    class UsersTracker extends AbstractTracker {
        protected $tracker_name = 'user';

        protected function register_hooks() {
            if ($this->is_action_enabled('login')) {
                add_action('wp_login', array($this, 'track_login'), 10, 2);
            }

            if ($this->is_action_enabled('logout')) {
                add_action('wp_logout', array($this, 'track_logout'));
            }

            if ($this->is_action_enabled('register')) {
                add_action('user_register', array($this, 'track_registration'));
            }

            if ($this->is_action_enabled('profile_update')) {
                add_action('profile_update', array($this, 'track_profile_update'), 10, 2);
            }
        }

        public function track_login($user_login, $user) {
            $this->logger->log(
                $user->ID,
                'login',
                'user',
                $user->ID,
                json_encode(array(
                    'user_login' => $user_login,
                    'user_email' => $user->user_email
                ), JSON_UNESCAPED_UNICODE)
            );
        }

        public function track_logout() {
            $user_id = get_current_user_id();
            if ($user_id) {
                $this->logger->log(
                    $user_id,
                    'logout',
                    'user',
                    $user_id,
                    ''
                );
            }
        }

        public function track_registration($user_id) {
            $user = get_user_by('id', $user_id);
            $this->logger->log(
                $user_id,
                'register',
                'user',
                $user_id,
                json_encode(array(
                    'user_login' => $user->user_login,
                    'user_email' => $user->user_email
                ), JSON_UNESCAPED_UNICODE)
            );
        }
        public function track_profile_update($user_id, $old_user_data) {
            $new_user = get_user_by('id', $user_id);
            $changes = array();

            $fields_to_track = array(
                'user_login' => 'Username',
                'user_email' => 'Email',
                'user_url' => 'Website',
                'display_name' => 'Display Name',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'description' => 'Biographical Info',
                'role' => 'Role'
            );

            foreach ($fields_to_track as $field => $label) {
                if ($field === 'role') {
                    $old_roles = $old_user_data->roles;
                    $new_roles = $new_user->roles;
                    if (array_diff($old_roles, $new_roles) || array_diff($new_roles, $old_roles)) {
                        $changes[$label] = array(
                            'old' => implode(', ', $old_roles),
                            'new' => implode(', ', $new_roles)
                        );
                    }
                } else {
                    $old_value = isset($old_user_data->$field) ? $old_user_data->$field : '';
                    $new_value = isset($new_user->$field) ? $new_user->$field : '';

                    if ($old_value !== $new_value) {
                        // Skip tracking password changes
                        if ($field === 'user_pass') {
                            continue;
                        }

                        $changes[$label] = array(
                            'old' => $old_value,
                            'new' => $new_value
                        );
                    }
                }
            }

            if (!empty($changes)) {
                $this->logger->log(
                    get_current_user_id(), // ID of user making the change
                    'profile_update',
                    'user',
                    $user_id, // ID of user being updated
                    json_encode($changes, JSON_UNESCAPED_UNICODE)
                );
            }
        }
    }