<?php
/**
 * Plugin Name:       Custom Page Reorder
 * Plugin URI:        https://example.com/custom-page-reorder
 * Description:       Reorder pages in the admin area with drag and drop functionality.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-page-reorder
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CPR_VERSION', '1.0.0' );
define( 'CPR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class Custom_Page_Reorder {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once CPR_PLUGIN_DIR . 'includes/class-cpr-admin.php';
		require_once CPR_PLUGIN_DIR . 'includes/class-cpr-ajax.php';
	}

	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_ajax_cpr_save_page_order', array( 'CPR_AJAX', 'save_page_order' ) );
		add_action( 'wp_ajax_nopriv_cpr_save_page_order', '__return_false' );
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'custom-page-reorder',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cpr_page_order';
		$table_name = sanitize_key( $table_name );

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			page_id bigint(20) NOT NULL,
			menu_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY page_id (page_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function deactivate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cpr_page_order';
		$table_name = sanitize_key( $table_name );
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}

function cpr() {
	return Custom_Page_Reorder::get_instance();
}

register_activation_hook( __FILE__, array( 'Custom_Page_Reorder', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Custom_Page_Reorder', 'deactivate' ) );

cpr();
