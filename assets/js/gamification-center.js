/**
 * JavaScript for Psych Gamification Center
 */
jQuery(document).ready(function($) {

    // --- Admin Panel JS ---
    if ($('body').hasClass('admin-bar')) {

        // Manual award form handling
        $("#manual-award-form").on("submit", function(e) {
            e.preventDefault();

            var form = $(this);
            var button = form.find('.button-primary');
            var originalText = button.text();
            button.text('Processing...').prop('disabled', true);

            var formData = {
                action: "psych_manual_award",
                nonce: psych_gamification_admin.nonce,
                user_id: $("#award_user_id").val(),
                award_type: $("#award_type").val(),
                award_value: $("#award_value").val(),
                reason: $("#award_reason").val()
            };

            $.post(psych_gamification_admin.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    alert("Success: " + response.data.message);
                    form[0].reset();
                } else {
                    alert("Error: " + response.data.message);
                }
            })
            .fail(function() {
                alert("An unexpected server error occurred.");
            })
            .always(function() {
                button.text(originalText).prop('disabled', false);
            });
        });
    }

    // --- Frontend JS ---
    if (!$('body').hasClass('admin-bar')) {
        var notificationShown = false;

        function checkNotifications() {
            if (notificationShown || typeof psych_gamification === 'undefined') return;

            $.post(psych_gamification.ajax_url, {
                action: "psych_get_pending_notifications",
                nonce: psych_gamification.nonce
            })
            .done(function(response) {
                if (response.success && response.data.notifications.length > 0) {
                    showNotification(response.data.notifications[0]);
                }
            });
        }

        function showNotification(notification) {
            var container = $('#psych-notification-container');
            if (container.length === 0) {
                $('body').append('<div id="psych-notification-container"></div>');
                container = $('#psych-notification-container');
            }

            var content = '<div id="psych-notification-popup">' +
                          '<button id="psych-notification-close">&times;</button>' +
                          '<div id="psych-notification-content">' +
                          '<h4>' + notification.title + '</h4>' +
                          '<p>' + notification.message + '</p>' +
                          '</div></div>';

            container.html(content).fadeIn();
            notificationShown = true;

            setTimeout(function() {
                hideNotification(notification.id);
            }, 5000);
        }

        function hideNotification(notificationId) {
            $('#psych-notification-container').fadeOut(function() {
                if (notificationId) {
                    $.post(psych_gamification.ajax_url, {
                        action: "psych_clear_notification",
                        nonce: psych_gamification.nonce,
                        notification_id: notificationId
                    });
                }
                notificationShown = false;
            });
        }

        $('body').on("click", "#psych-notification-close", function() {
            hideNotification();
        });

        // Check for notifications periodically and after a delay on page load
        setInterval(checkNotifications, 30000);
        setTimeout(checkNotifications, 2000);
    }
});
