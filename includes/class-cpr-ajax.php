<?php
/**
 * AJAX handler for Custom Page Reorder
 *
 * @package Custom_Page_Reorder
 */

class CPR_AJAX {

	public static function save_page_order() {
		check_ajax_referer( 'cpr_save_order_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission denied.', 'custom-page-reorder' ),
				),
				403
			);
		}

		$order = isset( $_POST['order'] ) ? array_map( 'absint', $_POST['order'] ) : array();

		if ( empty( $order ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No order data received.', 'custom-page-reorder' ),
				),
				400
			);
		}

		global $wpdb;

		foreach ( $order as $index => $page_id ) {
			$menu_order = $index;

			update_post_meta( $page_id, '_cpr_menu_order', $menu_order );

			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => $menu_order ),
				array( 'ID' => $page_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Page order saved successfully.', 'custom-page-reorder' ),
			)
		);
	}
}
