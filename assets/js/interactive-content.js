/**
 * JavaScript for Interactive Content Module
 */

jQuery(document).ready(function($) {

    // Handle revealing hidden content blocks
    // This looks for any element with a 'data-reveals-id' attribute
    // and makes it reveal the element specified in the attribute.
    $('[data-reveals-id]').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).data('reveals-id');
        $('#' + targetId).slideDown();
        $(this).hide(); // Optionally hide the trigger after click
    });

    // A more specific implementation for the [psych_hidden_content] shortcode
    // which uses a trigger_id attribute on the hidden content itself.
    $('.psych-hidden-content').each(function() {
        var hiddenContent = $(this);
        var triggerId = hiddenContent.data('trigger-id');

        if (triggerId) {
            $('#' + triggerId).on('click', function(e) {
                e.preventDefault();
                hiddenContent.slideDown();
                // You might want to add more complex behavior here,
                // like changing the trigger button's text or hiding it.
            });
        }
    });

});
