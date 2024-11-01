<?php
/**
 * ZT_ED_Admin Class
 *
 * Zestard Easy Donation the Admin functionality.
 *
 * @package WordPress
 * @subpackage Zestard Easy Donation Admin
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ZT_ED_Admin' ) ) {

	/**
	 * The ZT_ED_Admin Class
	 */
	class ZT_ED_Admin {

		var $action = null,
		    $filter = null;

		public $options;

		function __construct() {

			//action to include scripts
			add_action( 'admin_enqueue_scripts',  array( $this, 'zted_action_init' ) );
		}
			/*
			   ###     ######  ######## ####  #######  ##    ##  ######
			  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
			 ##   ##  ##          ##     ##  ##     ## ####  ## ##
			##     ## ##          ##     ##  ##     ## ## ## ##  ######
			######### ##          ##     ##  ##     ## ##  ####       ##
			##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
			##     ##  ######     ##    ####  #######  ##    ##  ######
			*/

			//create settings
			function zted_action_init() {
				global $pagenow;

				/* Admin styles */
				wp_register_style( ZT_ED_PREFIX . '_admin_style', ZT_ED_URL.'assets/css/zted_style.css', array(), ZT_ED_VERSION, false);
	            wp_enqueue_style( ZT_ED_PREFIX . '_admin_style' );
	            wp_register_style( ZT_ED_PREFIX . '_jquery_ui_style', ZT_ED_URL.'assets/css/zted-jquery-ui.css', array(), ZT_ED_VERSION, true);
	            wp_enqueue_style( ZT_ED_PREFIX . '_jquery_ui_style' );

	            /* admin js */
	            wp_enqueue_script( 'jquery' );

	          	if($pagenow === 'admin.php'){

	          		if(isset($_GET['page'])){
	          			
	          			if($_GET['page'] === 'zted_track_donation'){

	          				wp_register_script( ZT_ED_PREFIX . '_donationcharts_js', ZT_ED_URL.'assets/js/Chart.js', array('jquery'), ZT_ED_VERSION, true);
				            wp_enqueue_script( ZT_ED_PREFIX . '_donationcharts_js' );
	          			}
	          		}
	          	}
	             

	            wp_register_script( ZT_ED_PREFIX . '_admin_js', ZT_ED_URL.'assets/js/zted_admin.js', array('jquery'), ZT_ED_VERSION, true);
	            wp_enqueue_script( ZT_ED_PREFIX . '_admin_js' );

	   			wp_localize_script( ZT_ED_PREFIX . '_admin_js', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			}
		
	}

	add_action( 'plugins_loaded', function() {
		ZT_ED()->admin = new ZT_ED_Admin;
	} );
}