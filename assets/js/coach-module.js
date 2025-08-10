/**
 * JavaScript for Psych Coach Module
 */
jQuery(document).ready(function($) {

    // Copy to clipboard functionality for product codes
    $('body').on('click', '.copy-code-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetSelector = button.data('target');
        var targetElement = $(targetSelector);

        if (targetElement.length > 0) {
            var textToCopy = targetElement.text().trim();

            navigator.clipboard.writeText(textToCopy).then(function() {
                var originalText = button.text();
                button.text('Copied!');
                button.addClass('copied');
                setTimeout(function() {
                    button.text(originalText);
                    button.removeClass('copied');
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy text.');
            });
        }
    });

});
