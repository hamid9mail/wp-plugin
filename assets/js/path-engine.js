/**
 * JavaScript for PsychoCourse Path Engine
 */

jQuery(document).ready(function($) {

    // --- Accordion Mode Handler ---
    $('.psych-path-container.display-mode-accordion .station-header').on('click', function() {
        var header = $(this);
        var stationNode = header.closest('.station-node');

        if (stationNode.hasClass('status-locked')) {
            // Optional: Add a shake animation or a tooltip for locked stations
            stationNode.addClass('shake');
            setTimeout(function() {
                stationNode.removeClass('shake');
            }, 500);
            return;
        }

        // Toggle the content
        var content = stationNode.find('.station-content-wrapper');
        content.slideToggle();
    });

    // --- Modal Mode Handler ---
    var modalOverlay = $('#psych-path-modal-overlay');
    var modalContent = $('#psych-path-modal-content-inner');
    var modalClose = $('.psych-path-modal-close');

    // Open modal
    $('body').on('click', '.station-node.display-mode-modal .station-header', function() {
        var stationNode = $(this).closest('.station-node');
        if (stationNode.hasClass('status-locked')) {
            return;
        }
        var contentHtml = stationNode.find('.station-content-wrapper').html();
        modalContent.html(contentHtml);
        modalOverlay.fadeIn();
    });

    // Close modal
    modalClose.on('click', function() {
        modalOverlay.fadeOut();
    });
    modalOverlay.on('click', function(e) {
        if ($(e.target).is(modalOverlay)) {
            modalOverlay.fadeOut();
        }
    });

    // --- Mission Completion Handler (AJAX) ---
    $('body').on('click', '.mission-complete-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var stationNode = button.closest('.station-node');
        var nodeId = stationNode.data('node-id');

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
                    // Show result content
                    var resultWrapper = stationNode.find('.result-content-wrapper');
                    resultWrapper.html(response.data.result_html).slideDown();

                    // Optionally hide the mission content
                    stationNode.find('.mission-content-wrapper').slideUp();

                    // Update station status
                    stationNode.removeClass('status-unlocked').addClass('status-completed');
                    stationNode.find('.station-status-icon').removeClass('fa-lock-open').addClass('fa-check-circle');

                    // Trigger confetti!
                    if (typeof confetti === 'function' && response.data.confetti) {
                        confetti({
                            particleCount: 100,
                            spread: 70,
                            origin: { y: 0.6 }
                        });
                    }

                    // Unlock next stations if applicable
                    if (response.data.unlocked_nodes && response.data.unlocked_nodes.length > 0) {
                        response.data.unlocked_nodes.forEach(function(unlockedNodeId) {
                            var nextStation = $('.station-node[data-node-id="' + unlockedNodeId + '"]');
                            if (nextStation.length) {
                                nextStation.removeClass('status-locked').addClass('status-unlocked');
                                nextStation.find('.station-status-icon').removeClass('fa-lock').addClass('fa-lock-open');
                            }
                        });
                    }

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
