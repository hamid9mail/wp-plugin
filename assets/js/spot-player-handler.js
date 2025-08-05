/**
 * Psych Complete System - Spot Player Frontend Handler
 *
 * This script initializes the Spot Player, listens to video events,
 * and sends progress updates to the backend via AJAX.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function($) {

    // Find all player containers on the page
    $('.psych-spot-player-container').each(function() {
        var container = $(this);
        var videoId = container.data('video-id');
        var courseId = container.data('course-id');
        var userId = spot_player_data.user_id;

        if (!videoId) {
            container.text('Error: Video ID is missing.');
            return;
        }

        // --- ASSUMPTION: Spot Player Initialization ---
        // I am assuming Spot Player has an initialization function like this.
        // The container ID might be used to target the player.
        container.attr('id', 'spot-player-' + videoId);

        var player = new SpotPlayer({
            element: '#spot-player-' + videoId,
            license: videoId, // Assuming 'video_id' is the license key
            width: '100%',
            height: '100%'
        });

        var ninetyPercentReached = false;

        // --- ASSUMPTION: Event Listeners ---
        // I am assuming the Spot Player API provides event listeners like these.

        // Event for when the video ends
        player.on('ended', function() {
            trackVideoProgress(videoId, 'ended');
        });

        // Event for time updates to track progress percentage
        player.on('timeupdate', function(data) {
            // Assuming 'data' object contains { duration: seconds, currentTime: seconds }
            var percentage = (data.currentTime / data.duration) * 100;

            if (percentage >= 90 && !ninetyPercentReached) {
                ninetyPercentReached = true; // Ensure this only fires once
                trackVideoProgress(videoId, 'ninety_percent');
            }
        });

        // --- AJAX Function ---
        function trackVideoProgress(videoId, eventType) {
            $.ajax({
                url: spot_player_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'psych_track_video_progress',
                    nonce: spot_player_data.nonce,
                    video_id: videoId,
                    event: eventType,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Video progress tracked:', response.data.message);
                        // Optional: Display a small notification to the user
                        // e.g., using a library like Toastr or a custom div
                    } else {
                        console.error('Failed to track video progress:', response.data.message);
                    }
                },
                error: function() {
                    console.error('AJAX error while tracking video progress.');
                }
            });
        }
    });
});
