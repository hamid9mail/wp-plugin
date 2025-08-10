/**
 * JavaScript for Psych Dashboard & Reports Module
 */
jQuery(document).ready(function($) {

    // --- Tab Functionality ---
    $('.psych-report-card-tabs .tab-link').on('click', function(e) {
        e.preventDefault();
        var targetTab = $(this).data('tab');
        var container = $(this).closest('.psych-report-card-container');

        // Update active tab link
        container.find('.tab-link').removeClass('active');
        $(this).addClass('active');

        // Show/hide tab content
        container.find('.psych-tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');

        // If the activated tab contains a chart, we might need to re-render it.
        if ($('#' + targetTab).find('canvas').length > 0) {
            // This is a placeholder for a chart rendering function
            // e.g., renderCharts();
        }
    });

    // Activate the first tab by default
    $('.psych-report-card-container').each(function() {
        $(this).find('.psych-report-card-tabs .tab-link').first().click();
    });


    // --- AJAX for saving user notes/goals ---
    function saveUserData(element, action) {
        var content = element.val();
        var container = element.closest('.psych-section');
        var feedback = container.find('.save-feedback');
        if (feedback.length === 0) {
            container.append('<span class="save-feedback" style="margin-left: 10px; color: green;"></span>');
            feedback = container.find('.save-feedback');
        }

        feedback.text('Saving...').fadeIn();

        $.post(psych_dashboard_ajax.ajax_url, {
            action: action,
            nonce: psych_dashboard_ajax.nonce,
            content: content,
            user_id: psych_dashboard_ajax.viewed_user_id
        }).done(function(response) {
            if (response.success) {
                feedback.text('Saved!').delay(2000).fadeOut();
            } else {
                feedback.text('Error!').addClass('error').delay(2000).fadeOut();
            }
        }).fail(function() {
            feedback.text('Request Failed.').addClass('error').delay(2000).fadeOut();
        });
    }

    var notesTimeout;
    $('#psych-user-notes').on('input', function() {
        clearTimeout(notesTimeout);
        var element = $(this);
        notesTimeout = setTimeout(function() {
            saveUserData(element, 'psych_save_user_notes');
        }, 1500); // Auto-save after 1.5 seconds of inactivity
    });

    var goalsTimeout;
    $('#psych-user-goals').on('input', function() {
        clearTimeout(goalsTimeout);
        var element = $(this);
        goalsTimeout = setTimeout(function() {
            saveUserData(element, 'psych_save_user_goals');
        }, 1500);
    });

});
