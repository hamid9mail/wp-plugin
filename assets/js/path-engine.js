/**
 * JavaScript for PsychoCourse Path Engine
 */

jQuery(document).ready(function($) {

    // --- Accordion Mode Handler ---
    // This handles the simple case where content is inline and toggled.
    $('body').on('click', '.station-node-timeline .station-header', function() {
        var wrapper = $(this).closest('.psych-path-wrapper');
        var stationNode = $(this).closest('.station-node');

        // If the display mode is modal, the new handler below will take care of it.
        if (wrapper.data('station-display-mode') === 'modal' || stationNode.hasClass('status-locked')) {
            return;
        }

        // Toggle the content for non-modal views
        var content = stationNode.find('.station-content-wrapper');
        content.slideToggle();
    });

    // --- Modal Mode Handler (New Implementation) ---
    var modalOverlay = $('#psych-path-modal-overlay');
    var modalContent = $('#psych-path-modal-content-inner');
    var modalClose = $('.psych-path-modal-close');

    // Delegated click handler for any station element that has a data-station-id
    $('body').on('click', '[data-station-id]', function(e) {
        var stationTrigger = $(this);
        var wrapper = stationTrigger.closest('.psych-path-wrapper');

        // Only proceed if the path is in 'modal' display mode
        if (wrapper.data('station-display-mode') !== 'modal') {
            return;
        }

        e.preventDefault();

        // Check if the station is locked
        if (stationTrigger.hasClass('status-locked')) {
            stationTrigger.addClass('shake');
            setTimeout(function() {
                stationTrigger.removeClass('shake');
            }, 500);
            return;
        }

        var stationData = stationTrigger.data('station-details');

        // Show modal with loading state
        modalContent.html('<div class="psych-loader"></div>');
        modalOverlay.fadeIn();

        // Fetch content via AJAX
        $.ajax({
            url: psych_path_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'psych_path_get_station_content',
                nonce: psych_path_ajax.nonce,
                station_data: JSON.stringify(stationData)
            },
            success: function(response) {
                if (response.success) {
                    modalContent.html(response.data.html);
                } else {
                    modalContent.html('<p>Error loading content.</p>');
                }
            },
            error: function() {
                modalContent.html('<p>An unexpected error occurred.</p>');
            }
        });
    });

    // Close modal logic (remains the same)
    modalClose.on('click', function() {
        modalOverlay.fadeOut();
    });
    modalOverlay.on('click', function(e) {
        if ($(e.target).is(modalOverlay)) {
            modalOverlay.fadeOut();
        }
    });

    // --- Mission Completion Handler (AJAX) ---
    // This now needs to be delegated from the body to work inside the modal
    $('body').on('click', '.mission-complete-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var stationNode = button.closest('[data-station-id]'); // Works for both inline and modal
        var nodeId = stationNode.data('station-id');

        button.prop('disabled', true).text('Processing...');

        $.ajax({
            url: psych_path_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'psych_path_complete_mission',
                nonce: psych_path_ajax.nonce,
                node_id: nodeId
            },
            success: function(response) {
                if (response.success) {
                    // This part needs to be smarter. For now, let's just show an alert
                    // and reload the page to show the updated status.
                    // A more advanced implementation would update the UI without a reload.
                    alert('Mission Completed! The page will now reload to reflect your progress.');
                    location.reload();

                } else {
                    alert('Error: ' + response.data.message);
                    button.prop('disabled', false).text('Complete Mission');
                }
            },
            error: function() {
                alert('An unexpected error occurred. Please try again.');
                button.prop('disabled', false).text('Complete Mission');
            }
        });
    });

});
