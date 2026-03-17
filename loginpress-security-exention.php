<?php
/**
 * This is main plugin file.
 *
 * @link             
 * @since            
 * @package           
 *
 * Plugin Name: Loginpres Security Extension
 * Description: The Plugin is used for Extend the Login Press Functionliy Of password Reset module.
 * Version: 0.0.1
 * Author: Syed Ali Raza
 * Author URI: 
 * Text Domain: wp-loginpress-security
 * Domain Path: /languages
 * Requires PHP: 7.4
 * WC requires at least: 5.0.0
 * WC tested up to: 10.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_LOGINPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_LOGINPRESS_URL', plugin_dir_url( __FILE__ ) );

/*
 Load Required Files
*/
require_once WP_LOGINPRESS_PATH . 'includes/class-wp-loginpress-assets.php';
require_once WP_LOGINPRESS_PATH . 'includes/class-wp-prevent-reused-password.php';
require_once WP_LOGINPRESS_PATH . 'includes/class-lp-password-reminder.php';


/**
 * Are used to Check the plugin activate og login press
 *
*/
function wp_loginpress_security_ext_check_dep() {

    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Check if LoginPress plugin is active
    if ( ! is_plugin_active( 'loginpress/loginpress.php' ) ) {

        deactivate_plugins( plugin_basename( __FILE__ ) );

        add_action( 'admin_notices', function () {
            echo '<div class="error"><p><strong>'.esc_html__('My LoginPress Extension requires the LoginPress plugin to be installed and activated.', 'my-loginpress-extension').'</strong></p></div>';
        });

    }
}

// Action to Check the extension
add_action( 'admin_init', 'wp_loginpress_security_ext_check_dep' );

/*
 * Loading the Plugin Multi Lanage text domain
*/
function wp_loginpressext_init() {
    // Initialize
    new WP_LoginPress_Assets();
    new WP_Prevent_Reused_Passwords();
    new LP_Password_Reminder();
   
    load_plugin_textdomain(
        'my-loginpress-extension',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );

}

/* Action to int the main classes*/
add_action( 'plugins_loaded', 'wp_loginpressext_init' );