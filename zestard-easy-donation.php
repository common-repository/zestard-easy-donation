<?php
/**
 * Plugin Name: Zestard Easy Donation
 * Description: This plugin used to integrate the donation with WooCommerce on cart and checkout page with custom settings.
 * Version: 1.0.4
 * Author: Zestard Technologies
 * Author URI: https://profiles.wordpress.org/zestardtechnologies/
 * Developer: Zestard Technologies
 * Developer E-Mail: info@zestard.com
 * Text Domain: zt-easy-donation
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package Zestard Easy Donation
 * @since 1.0
 */
if ( !defined( 'ZT_ED_VERSION' ) ) {
	define( 'ZT_ED_VERSION', '1.0.4' ); // Version of plugin
}

if ( !defined( 'ZT_ED_FILE' ) ) {
	define( 'ZT_ED_FILE', __FILE__); // Plugin file
}

if ( !defined( 'ZT_ED_DIR' ) ) {
	define( 'ZT_ED_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( !defined( 'ZT_ED_BASENAME' ) ) {
	define( 'ZT_ED_BASENAME', plugin_basename( __FILE__ ) ); // Plugin base name
}

if ( !defined( 'ZT_ED_URL' ) ) {
	define( 'ZT_ED_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( !defined( 'ZT_ED_META_PREFIX' ) ) {
	define( 'ZT_ED_META_PREFIX', 'zted_' ); // Plugin metabox prefix
}

if ( !defined( 'ZT_ED_PREFIX' ) ) {
	define( 'ZT_ED_PREFIX', 'zted' ); // Plugin prefix
}

if ( !defined( 'ZT_ED_TEXTDOMAIN' ) ) {
	define( 'ZT_ED_TEXTDOMAIN', 'zt-easy-donation' ); // Plugin text-domain
}

/**
 * Check if is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    /**
	* Initialize the main class
	**/
	if ( !function_exists( 'ZT_ED' ) ) {

		//Initialize all the things.
		require_once( ZT_ED_DIR . '/inc/class.' . ZT_ED_PREFIX . '.php' );

		/**
		* Frontend view zestard easy donation
		**/
		require_once( ZT_ED_DIR . '/inc/frontend/class.' . ZT_ED_PREFIX . '.frontend.action.php' );

		if ( is_admin() ) {
			require_once( ZT_ED_DIR . '/inc/admin/class.' . ZT_ED_PREFIX . '.admin.php' );
			require_once( ZT_ED_DIR . '/inc/admin/class.' . ZT_ED_PREFIX . '.admin.action.php' );
			require_once( ZT_ED_DIR . '/inc/admin/class.' . ZT_ED_PREFIX . '.admin.ajaxdata.php' );
		}

		global $wpdb;
	    $charset_collate = $wpdb->get_charset_collate();
	    $table_name = $wpdb->prefix . 'zted_donation_config';

	    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		    $sql = "CREATE TABLE $table_name (
		        id int(4) NOT NULL AUTO_INCREMENT,
		        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		        dashboard_info varchar(300) NOT NULL,
		        email_templates varchar(800) NOT NULL,
		        email_content varchar(1500) NOT NULL,
		        UNIQUE KEY id (id)
		    ) $charset_collate;";

		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		    dbDelta( $sql );
		}	
	}

	add_action( 'before_woocommerce_init', function() {

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
	
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', ZT_ED_FILE, false );
	
		}
	
	} );
}else{
	
	add_action('admin_notices', 'zted_donation_admin_notices');

	function zted_donation_admin_notices() {
		/*
        * Unset the $_GET variable which triggers the activation message
        */
		unset($_GET['activate']);

		/*
        * Display the error message on triggers the plugin activation button
        */
		echo '<div class="notice notice-error is-dismissible"><p>Zestard Easy Donation requires active version of <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a></p></div>';
		
		/*
        * Do not allow this plugin to activate
        */
	 	deactivate_plugins('zestard-easy-donation/zestard-easy-donation.php');
	 	
	}
}

?>