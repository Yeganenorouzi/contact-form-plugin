<?php
/**
 * Plugin Name: Contact Capture Lite
 * Description: A custom contact form plugin with admin panel and REST API
 * Version: 1.0.0
 * Author: Yegane Norouzi
 * Text Domain: Contact Capture Lite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFP_VERSION', '1.0.0');
define('CFP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once CFP_PLUGIN_DIR . 'includes/class-contact-form.php';
require_once CFP_PLUGIN_DIR . 'includes/class-contact-form-admin.php';
require_once CFP_PLUGIN_DIR . 'includes/class-contact-form-api.php';

// Initialize the plugin
function cfp_init()
{
    $contact_form = new Contact_Form();
    $contact_form_admin = new Contact_Form_Admin();
    $contact_form_api = new Contact_Form_API();
}

add_action('plugins_loaded', 'cfp_init');

// Activation hook
register_activation_hook(__FILE__, 'cfp_activate');
function cfp_activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_form_submissions';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        subject varchar(200) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'cfp_deactivate');
function cfp_deactivate()
{
    // Cleanup if needed
}