<?php

/*
Plugin Name: GCode Add Birthday Fields To Order
Plugin URI: https://jenniferladd.art/
Description: Simply adds birthday date, time and location fields to the WooCommerce order
Version: 1.0.0
Author: Gareth Ladd
Author URI: https://jenniferladd.art/
License: GPLv2 or later
Text Domain: gcode_obf
*/


// TODO: Add product metadata to "Require birthday details"
// TODO: Add code to check for "require all" or >=1 specified products in order
//       items in both front end and admin.

define( 'OBF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OBF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GCBD_VERSION', '1.0.0');

/**
 * The text domain for translations for this plugin
 * @var string
 */
const OBF_TEXT = 'gcode_obf';

/**
 * Load our classes. Code would be neater if this was put in the
 * <code>gcodeobf_setup()</code> function. However, dependent plugins (if any)
 * would reasonably expect classes to be loaded by <code>plugins_loaded</code>
 * action.
 */
if (is_admin())
{
    require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/AdminEditOrder.php';
    require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/AdminSettings.php';
}
else 
{
    require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/FrontEndCheckoutOrder.php';
}

/**
 * Call our setup function only if WooCommerce exists and has loaded. This
 * action is WC_Version >= 3.6.0.
 */
add_action('woocommerce_loaded', 'gcodeobf_setup');

/**
 * Creates classes and adds required actions and filters.
 */
function gcodeobf_setup()
{
    if ( is_admin() )
    {
        $details = new \GCode\BirthdayDetails\AdminEditOrder();
        $details->register();
        
        $settings = new \GCode\BirthdayDetails\AdminSettings();
        $settings->register();
    }
    else 
    {
        $details = new \GCode\BirthdayDetails\FrontEndCheckoutOrder();
        $details->register();
    }
    
    $version = GCBD_VERSION;
    /**
     * Action to signify that the 'GCode Birthday Details' plugin has loaded.
     * Dependent plugins can use this to determine that this plugin is
     * installed and activated and all classes loaded.
     * 
     * @param string $version The version of the 'GCode Birthday Details' plugin as a dotted string, e.g. "1.0.1".
     * @since 1.0.0
     */
    do_action('gcode_birthday_details_loaded', $version);
}

/**
 * Call our function which outputs an admin notice if WooCommerce not
 * installed. This is a NO-OP on the front end.
 */
add_action( 'admin_notices', 'gcodeobf_show_woo_notice_if_required' );

/**
 * Outputs an admin notice stating the the user needs to install WooCommerce
 * for this plugin to work.
 */
function gcodeobf_show_woo_notice_if_required()
{
    $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
    $woo_installed = in_array( 'woocommerce/woocommerce.php', $active_plugins );
    $woo_version_defined = defined( 'WC_VERSION' );
    
    if ( !$woo_installed || !$woo_version_defined || version_compare( WC_VERSION, '3.6', '<' ) )
    {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( '<strong>GCode Add Birthday Fields To Order</strong> plugin requires WooCommerce 3.6.0 or above. Please install WooCommerce to enable birthday details features.', OBF_TEXT ); ?></p>
        </div>
        <?php
    }
}
