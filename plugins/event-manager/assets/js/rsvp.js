/**
 * Event Manager — RSVP AJAX Handler.
 *
 * Handles confirm/cancel attendance clicks on the single event
 * template via jQuery AJAX. Updates the DOM with new RSVP status,
 * attendee count, and button state without a page reload.
 *
 * @package    Event_Manager
 * @subpackage Event_Manager/Assets/JS
 * @since      1.0.0
 * @author     Developer
 */
(function ($) {
	'use strict';

	$(document).on('click', '.em-rsvp-btn', function (e) {
		e.preventDefault();

		var $btn       = $(this);
		var $container = $('#em-rsvp-container');
		var eventId    = $container.data('event-id');
		var action     = $btn.data('action'); // 'confirm' or 'cancel'

		// Disable button during request.
		$btn.prop('disabled', true).addClass('em-loading');

		$.ajax({
			url:  emRSVP.ajax_url,
			type: 'POST',
			data: {
				action:      'event_manager_rsvp',
				nonce:       emRSVP.nonce,
				event_id:    eventId,
				rsvp_action: action // 'confirm' or 'cancel'
			},
			success: function (response) {
				if (response.success) {
					var data  = response.data;
					var count = parseInt(data.rsvp_count, 10);

					// Update attendee count.
					var countText = count === 1
						? emRSVP.strings.count_single.replace('%d', count)
						: emRSVP.strings.count_plural.replace('%d', count);
					$container.closest('.em-rsvp-section').find('.em-rsvp-count').text(countText);

					// Update button and status.
					if (data.rsvp_status === 'confirmed') {
						$container.html(
							'<p class="em-rsvp-status em-rsvp-confirmed">' +
								'<span class="dashicons dashicons-yes-alt"></span> ' +
								emRSVP.strings.attending +
							'</p>' +
							'<button type="button" class="em-rsvp-btn em-rsvp-cancel" data-action="cancel">' +
								emRSVP.strings.cancel +
							'</button>'
						);
					} else {
						$container.html(
							'<button type="button" class="em-rsvp-btn em-rsvp-confirm" data-action="confirm">' +
								emRSVP.strings.confirm +
							'</button>'
						);
					}
				} else {
					alert(response.data && response.data.message ? response.data.message : emRSVP.strings.error);
					$btn.prop('disabled', false).removeClass('em-loading');
				}
			},
			error: function () {
				alert(emRSVP.strings.error);
				$btn.prop('disabled', false).removeClass('em-loading');
			}
		});
	});
})(jQuery);
