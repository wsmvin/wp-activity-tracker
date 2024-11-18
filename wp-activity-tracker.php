<?php
    /*
        Plugin Name: WP Activity Tracker
        Plugin URI: https://github.com/wsmvin/wp-activity-tracker
        Description: Tracks and logs all WordPress activities including posts, pages, media, users and settings changes.
        Version: 1.0.0
        Requires at least: 5.5
        Requires PHP: 7.2
        Author: WiSiM
        Author URI: https://wsm.co.ua
        License: GPL v2
        License URI: https://www.gnu.org/licenses/gpl-2.0.html
        Text Domain: wp-activity-tracker
    */


    if (!defined('ABSPATH')) exit;

    define('WAT_VERSION', '1.2');
    define('WAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('WAT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Core classes
    require_once WAT_PLUGIN_DIR . 'includes/Core/Settings.php';
    require_once WAT_PLUGIN_DIR . 'includes/Core/Logger.php';
    require_once WAT_PLUGIN_DIR . 'includes/Core/Trackers.php';
    require_once WAT_PLUGIN_DIR . 'includes/Core/Plugin.php';
    require_once WAT_PLUGIN_DIR . 'includes/Core/Installer.php';
    require_once WAT_PLUGIN_DIR . 'includes/Core/Admin.php';
    require_once WAT_PLUGIN_DIR . 'includes/Core/DBTracker.php';

// Trackers
    require_once WAT_PLUGIN_DIR . 'includes/Trackers/AbstractTracker.php';
    require_once WAT_PLUGIN_DIR . 'includes/Trackers/PostTracker.php';
    require_once WAT_PLUGIN_DIR . 'includes/Trackers/UsersTracker.php';
    require_once WAT_PLUGIN_DIR . 'includes/Trackers/MediaTracker.php';
    require_once WAT_PLUGIN_DIR . 'includes/Trackers/OptionsTracker.php';

// Admin
    require_once WAT_PLUGIN_DIR . 'includes/Admin/SettingsPage.php';
    require_once WAT_PLUGIN_DIR . 'includes/Admin/LogsView.php';
    require_once WAT_PLUGIN_DIR . 'includes/Admin/LogsFilter.php';
    require_once WAT_PLUGIN_DIR . 'includes/Admin/LogsSorting.php';
    require_once WAT_PLUGIN_DIR . 'includes/Admin/LogsPagination.php';

// Activation
    register_activation_hook(__FILE__, function() {
        $installer = new WPActivityTracker\Core\Installer();
        $installer->install();
    });

// Initialize
    function wat_init() {
        $plugin = new WPActivityTracker\Core\Plugin();
        $plugin->init();
    }
    add_action('plugins_loaded', 'wat_init');


    add_action('plugins_loaded', function() {
        WPActivityTracker\Core\DBTracker::get_instance();
    });