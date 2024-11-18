<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>Activity Logs</h1>

    <form id="wat-filters-form" class="wat-filters" method="get">
        <input type="hidden" name="page" value="activity-logs" />
        <?php $this->filters->display(); ?>
        <button type="submit" class="button">Apply Filters</button>
    </form>

    <?php if (empty($logs)): ?>
        <div class="notice notice-info">
            <p>No activity logs found.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped wat-table">
            <thead>
            <tr>
                <?php $this->sorting->display_headers(); ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log):
                $user = get_user_by('id', $log->user_id);
                $username = $user ? $user->user_login : 'Unknown';
                $changes = json_decode($log->old_value, true);
                ?>
                <tr>
                    <td><?php echo esc_html($log->action_date); ?></td>
                    <td><?php echo esc_html($username); ?></td>
                    <td><?php echo esc_html($log->action_type . ' ' . $log->object_type); ?></td>
                    <td>
                        <div class="wat-details-wrapper">
                            <div class="wat-details-toggle">View Changes</div>
                            <div class="wat-details-content">
                                <?php if ($changes): ?>
                                    <div class="wat-changes-grid">
                                        <?php $this->render_changes($changes); ?>
                                    </div>
                                <?php else: ?>
                                    <pre><?php echo esc_html($log->old_value); ?></pre>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php $this->pagination->display($total_items); ?>
    <?php endif; ?>
</div>

