<?php
/**
 * ZT_ED Class
 *
 * Handles the plugin functionality.
 *
 * @package WordPress
 * @subpackage Zestard Easy Donation
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ZT_ED' ) ) {

	/**
	 * The main ZT_ED class
	 */
	class ZT_ED {

		private static $_instance = null;

		var $admin = null,
		    $front = null;

		public static function instance() {

			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}

		function __construct() {

		}

	}
}

function ZT_ED() {
	return ZT_ED::instance();
}

ZT_ED();