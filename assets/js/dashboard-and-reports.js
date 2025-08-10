/**
 * JavaScript for Dashboard & Reports Module
 */

jQuery(document).ready(function($) {

    // Find all report card containers on the page
    $('.psych-report-card-container').each(function() {
        var container = $(this);
        var tabs = container.find('.tab-link');
        var panes = container.find('.tab-pane');

        // Click event for tab links
        tabs.on('click', function(e) {
            e.preventDefault();

            var target = $(this).data('tab');

            // Update active class for tabs
            tabs.removeClass('active');
            $(this).addClass('active');

            // Show/hide tab panes
            panes.removeClass('active');
            container.find('#' + target).addClass('active');
        });

        // Show the default tab on page load
        var defaultTab = container.find('.report-card-tabs').data('default-tab');
        if (defaultTab) {
            container.find('.tab-link[data-tab="' + defaultTab + '"]').click();
        } else {
             // If no default is set, click the first tab
            tabs.first().click();
        }
    });

});
