<?php
    // wp-activity-tracker/templates/admin/settings.php
    if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1>Activity Tracker Settings</h1>

    <form method="post" action="">
        <?php wp_nonce_field('wat_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">Track Changes</th>
                <td>
                    <label>
                        <input type="checkbox" name="wat_track_posts" value="1"
                            <?php checked(isset($settings['tracking']['posts']) && $settings['tracking']['posts']); ?>>
                        Posts
                    </label><br>

                    <label>
                        <input type="checkbox" name="wat_track_pages" value="1"
                            <?php checked(isset($settings['tracking']['pages']) && $settings['tracking']['pages']); ?>>
                        Pages
                    </label><br>

                    <label>
                        <input type="checkbox" name="wat_track_media" value="1"
                            <?php checked(isset($settings['tracking']['media']) && $settings['tracking']['media']); ?>>
                        Media
                    </label><br>

                    <label>
                        <input type="checkbox" name="wat_track_users" value="1"
                            <?php checked(isset($settings['tracking']['users']) && $settings['tracking']['users']); ?>>
                        Users
                    </label><br>

                    <label>
                        <input type="checkbox" name="wat_track_options" value="1"
                            <?php checked(isset($settings['tracking']['options']) && $settings['tracking']['options']); ?>>
                        WordPress Settings
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">Log Retention</th>
                <td>
                    <input type="number" name="wat_retention_days" min="1" max="365"
                           value="<?php echo isset($settings['retention']['days']) ? intval($settings['retention']['days']) : 30; ?>">
                    days
                    <p class="description">How long to keep activity logs.</p>

                    <br>
                    <label>
                        <input type="checkbox" name="wat_auto_delete" value="1"
                            <?php checked(isset($settings['retention']['delete_old']) && $settings['retention']['delete_old']); ?>>
                        Automatically delete old logs
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="wat_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>
</div>