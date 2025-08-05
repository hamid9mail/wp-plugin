/**
 * Psych Complete System - Secure Audio Frontend Handler
 *
 * This script listens for events on the secure audio player
 * and sends progress updates to the backend via AJAX.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function($) {

    // Use event delegation for audio players that might be loaded dynamically
    $(document.body).on('ended', '.psych-secure-audio-player', function() {
        var player = $(this);
        var audioId = player.data('audio-id');

        if (!audioId) {
            console.error('Audio ID not found for tracking.');
            return;
        }

        $.ajax({
            url: secure_audio_data.ajax_url,
            type: 'POST',
            data: {
                action: 'psych_track_audio_progress',
                nonce: secure_audio_data.nonce,
                audio_id: audioId
            },
            success: function(response) {
                if (response.success) {
                    console.log('Audio progress tracked:', response.data.message);
                    // Optional: Display a notification to the user
                    // For example, trigger a mystery box or a simple text notification
                } else {
                    console.error('Failed to track audio progress:', response.data.message);
                }
            },
            error: function() {
                console.error('AJAX error while tracking audio progress.');
            }
        });
    });
});
