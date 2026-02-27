<?php
/**
 * Admin class for Custom Page Reorder
 *
 * @package Custom_Page_Reorder
 */

class CPR_Admin {

	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_page_posts_columns', array( $this, 'add_order_column' ), 100 );
		add_action( 'manage_page_posts_custom_column', array( $this, 'render_order_column' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_reorder_page' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_custom_order' ) );
		add_action( 'load-edit.php', array( $this, 'setup_list_table_assets' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'cpr-admin',
			CPR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CPR_VERSION
		);

		wp_enqueue_script(
			'cpr-admin',
			CPR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery-ui-sortable' ),
			CPR_VERSION,
			true
		);

		wp_localize_script(
			'cpr-admin',
			'cprData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cpr_save_order_nonce' ),
				'i18n'    => array(
					'saved'   => __( 'Order saved successfully.', 'custom-page-reorder' ),
					'error'   => __( 'Error saving order.', 'custom-page-reorder' ),
					'saving'  => __( 'Saving...', 'custom-page-reorder' ),
				),
			)
		);

		add_action( 'admin_print_footer_scripts', array( $this, 'print_inline_script' ), 20 );
	}

	public function print_inline_script() {
		?>
		<script type="text/javascript">
		(function($) {
			$(document).ready(function() {
				var $tableBody = $('#the-list');
				if ($tableBody.length && !$tableBody.data('ui-sortable')) {
					$tableBody.sortable({
						items: 'tr',
						handle: '.cpr-drag-handle',
						axis: 'y',
						opacity: 0.8,
						update: function(event, ui) {
							var order = [];
							$('#the-list > tr').each(function() {
								var pageId = $(this).attr('id');
								if (pageId && pageId.match(/^post-/)) {
									order.push(parseInt(pageId.replace('post-', ''), 10));
								}
							});
							$.post(cprData.ajaxUrl, {
								action: 'cpr_save_page_order',
								nonce: cprData.nonce,
								order: order
							}, function(response) {
								if (response.success) {
									$.each(order, function(index, pageId) {
										$('#post-' + pageId).find('.column-cpr_order .cpr-order-display').text(index);
									});
								}
							});
						}
					});
				}
			});
		})(jQuery);
		</script>
		<?php
	}

	public function setup_list_table_assets() {
		$screen = get_current_screen();
		if ( $screen && 'page' === $screen->post_type ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}

	public function add_order_column( $columns ) {
		$columns['cpr_order'] = __( 'Order', 'custom-page-reorder' );
		return $columns;
	}

	public function render_order_column( $column, $post_id ) {
		if ( 'cpr_order' !== $column ) {
			return;
		}

		$order = get_post_meta( $post_id, '_cpr_menu_order', true );
		if ( empty( $order ) ) {
			$order = get_post_field( 'menu_order', $post_id );
		}

		$order = (int) $order;

		echo '<span class="cpr-drag-handle dashicons dashicons-menu" title="' . esc_attr__( 'Drag to reorder', 'custom-page-reorder' ) . '"></span>';
		echo '<span class="cpr-order-display" data-page-id="' . esc_attr( $post_id ) . '">';
		echo esc_html( $order );
		echo '</span>';
	}

	public function add_reorder_page() {
		add_submenu_page(
			'edit.php?post_type=page',
			__( 'Reorder Pages', 'custom-page-reorder' ),
			__( 'Reorder Pages', 'custom-page-reorder' ),
			'manage_options',
			'cpr-reorder',
			array( $this, 'render_reorder_page' )
		);
	}

	public function render_reorder_page() {
		$pages = get_posts(
			array(
				'post_type'   => 'page',
				'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			)
		);

		foreach ( $pages as $page ) {
			$custom_order = get_post_meta( $page->ID, '_cpr_menu_order', true );
			if ( '' !== $custom_order ) {
				$page->menu_order = (int) $custom_order;
			}
		}

		usort( $pages, function( $a, $b ) {
			return $a->menu_order - $b->menu_order;
		} );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Drag and drop pages to reorder them.', 'custom-page-reorder' ); ?></p>

			<div id="cpr-sortable-pages" class="cpr-sortable">
				<?php if ( empty( $pages ) ) : ?>
					<p><?php esc_html_e( 'No pages found.', 'custom-page-reorder' ); ?></p>
				<?php else : ?>
					<ul class="cpr-page-list">
						<?php foreach ( $pages as $page ) : ?>
							<li class="cpr-page-item" data-page-id="<?php echo esc_attr( $page->ID ); ?>">
								<span class="cpr-drag-handle dashicons dashicons-menu"></span>
								<span class="cpr-page-title">
									<?php
									$title = $page->post_title ? sanitize_text_field( $page->post_title ) : __( '(No title)', 'custom-page-reorder' );
									echo esc_html( $title );
									?>
									<span class="cpr-page-status">
										<?php
										if ( 'draft' === $page->post_status ) {
											echo ' — ' . esc_html__( 'Draft', 'custom-page-reorder' );
										} elseif ( 'pending' === $page->post_status ) {
											echo ' — ' . esc_html__( 'Pending Review', 'custom-page-reorder' );
										} elseif ( 'private' === $page->post_status ) {
											echo ' — ' . esc_html__( 'Private', 'custom-page-reorder' );
										}
										?>
									</span>
								</span>
								<span class="cpr-page-order">
									<input type="number" value="<?php echo esc_attr( (int) $page->menu_order ); ?>" readonly />
								</span>
								<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>" class="cpr-edit-link">
									<span class="dashicons dashicons-edit"></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<p class="cpr-actions">
				<button type="button" id="cpr-save-order" class="button button-primary">
					<?php esc_html_e( 'Save Order', 'custom-page-reorder' ); ?>
				</button>
				<span class="cpr-message"></span>
			</p>
		</div>
		<?php
	}

	public function sort_by_custom_order( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( 'page' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! isset( $_GET['orderby'] ) || empty( $_GET['orderby'] ) ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}
}

new CPR_Admin();
