/**
 * Custom Page Reorder Admin JavaScript
 *
 * @package Custom_Page_Reorder
 */

(function($) {
	'use strict';

	var CPR = {
		init: function() {
			if ($('#cpr-sortable-pages').length) {
				this.initStandaloneReorder();
			} else if ($('#the-list').length && $('#the-list').hasClass('wp-list-table')) {
				this.initListTableReorder();
			}
		},

		initStandaloneReorder: function() {
			var self = this;
			var $saveButton = $('#cpr-save-order');
			var $message = $('.cpr-message');

			$('.cpr-page-list').sortable({
				items: 'li.cpr-page-item',
				handle: '.cpr-drag-handle',
				placeholder: 'cpr-page-item-placeholder',
				opacity: 0.8,
				cursor: 'grabbing',
				update: function(event, ui) {
					self.updateOrderNumbers();
				}
			});

			$saveButton.on('click', function(e) {
				e.preventDefault();
				self.saveStandaloneOrder();
			});
		},

		initListTableReorder: function() {
			var self = this;
			var $tableBody = $('#the-list');

			if (!$tableBody.length) {
				return;
			}

			try {
				if ($tableBody.data('ui-sortable')) {
					$tableBody.sortable('destroy');
				}
			} catch(e) {}

			try {
				$tableBody.sortable({
					items: 'tr',
					handle: '.cpr-drag-handle',
					axis: 'y',
					placeholder: 'cpr-sortable-placeholder',
					opacity: 0.8,
					helper: function(e, tr) {
						var $original = tr.children();
						var $helper = tr.clone();
						$helper.children().each(function(index) {
							$(this).width($original.eq(index).width());
						});
						return $helper;
					},
					start: function(event, ui) {
						ui.placeholder.height(ui.item.height());
					},
					update: function(event, ui) {
						self.saveListOrder();
					}
				});
			} catch(e) {
				console.log('CPR: Sortable initialization error', e);
			}
		},

		updateOrderNumbers: function() {
			$('.cpr-page-item').each(function(index) {
				$(this).find('.cpr-page-order input').val(index);
			});
		},

		saveListOrder: function() {
			var self = this;
			var order = [];

			$('#the-list > tr').each(function() {
				var pageId = $(this).attr('id');
				if (pageId && pageId.match(/^post-/)) {
					order.push(parseInt(pageId.replace('post-', ''), 10));
				}
			});

			$.ajax({
				url: cprData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cpr_save_page_order',
					nonce: cprData.nonce,
					order: order
				},
				success: function(response) {
					if (response.success) {
						self.updateOrderColumn(order);
					}
				}
			});
		},

		updateOrderColumn: function(order) {
			$.each(order, function(index, pageId) {
				var $row = $('#post-' + pageId);
				var $orderCell = $row.find('.column-cpr_order');
				if ($orderCell.length) {
					$orderCell.find('.cpr-order-display').text(index);
				}
			});
		},

		saveStandaloneOrder: function() {
			var self = this;
			var order = [];
			var $saveButton = $('#cpr-save-order');
			var $message = $('.cpr-message');

			$saveButton.prop('disabled', true).text(cprData.i18n.saving);
			$message.empty();

			$('.cpr-page-item').each(function() {
				order.push($(this).data('page-id'));
			});

			$.ajax({
				url: cprData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cpr_save_page_order',
					nonce: cprData.nonce,
					order: order
				},
				success: function(response) {
					if (response.success) {
						$message
							.removeClass('error')
							.addClass('success')
							.text(cprData.i18n.saved)
							.fadeIn();

						setTimeout(function() {
							$message.fadeOut();
						}, 3000);
					} else {
						$message
							.removeClass('success')
							.addClass('error')
							.text(response.data.message || cprData.i18n.error)
							.fadeIn();
					}
				},
				error: function() {
					$message
						.removeClass('success')
						.addClass('error')
						.text(cprData.i18n.error)
						.fadeIn();
				},
				complete: function() {
					$saveButton.prop('disabled', false).text(cprData.i18n.saved);
				}
			});
		}
	};

	$(document).ready(function() {
		CPR.init();
	});

	$(document).on('postboxes', function() {
		CPR.init();
	});

})(jQuery);
