jQuery(document).ready(function($) {
    // Admin JS
    if (typeof psych_gamification_admin !== 'undefined') {
        // Manual award form handling
        $("#manual-award-form").on("submit", function(e) {
            e.preventDefault();

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
                    alert("✅ " + response.data.message);
                    $("#manual-award-form")[0].reset();
                } else {
                    alert("❌ " + response.data.message);
                }
            })
            .fail(function() {
                alert("❌ خطا در ارتباط با سرور");
            });
        });

        // Live search for users
        $("#award_user_search").on("input", function() {
            var query = $(this).val();
            if (query.length < 2) return;

            // This is a placeholder for user search functionality
        });
    }

    // Frontend JS
    if (typeof psych_gamification !== 'undefined') {
        var notificationShown = false;

        // Check for pending notifications
        function checkNotifications() {
            if (notificationShown) return;

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

        // Show notification popup
        function showNotification(notification) {
            var content = "<h4>" + notification.title + "</h4><p>" + notification.message + "</p>";
            $("#psych-notification-content").html(content);
            $("#psych-notification-container").fadeIn();
            notificationShown = true;

            // Auto-hide after 5 seconds
            setTimeout(function() {
                hideNotification(notification.id);
            }, 5000);
        }

        // Hide notification
        function hideNotification(notificationId) {
            $("#psych-notification-container").fadeOut(function() {
                // Clear the notification from database
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

        // Close notification manually
        $("#psych-notification-close").on("click", function() {
            hideNotification();
        });

        // Check for notifications every 30 seconds
        setInterval(checkNotifications, 30000);

        // Initial check
        setTimeout(checkNotifications, 2000);

        // Handle mission completion integration
        $(document).on("psych_mission_completed", function(e, missionId, buttonElement) {
            setTimeout(checkNotifications, 1000);
        });
    }
});
