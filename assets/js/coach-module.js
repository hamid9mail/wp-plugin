/**
 * JavaScript for Coach Module
 */

jQuery(document).ready(function($) {

    // Handle the user search form submission
    $('#coach-user-search-form').on('submit', function(e) {
        e.preventDefault();

        var searchForm = $(this);
        var resultsContainer = $('#coach-search-results');
        var searchButton = searchForm.find('.search-button');

        resultsContainer.html('<p>Searching...</p>');
        searchButton.prop('disabled', true);

        var formData = {
            action: 'psych_coach_search_user',
            nonce: psych_coach_vars.nonce,
            search_query: searchForm.find('input[name="search_query"]').val()
        };

        $.post(psych_coach_vars.ajax_url, formData, function(response) {
            if (response.success) {
                resultsContainer.html(response.data.html);
            } else {
                resultsContainer.html('<p class="error">' + response.data.message + '</p>');
            }
            searchButton.prop('disabled', false);
        }).fail(function() {
            resultsContainer.html('<p class="error">An unexpected error occurred. Please try again.</p>');
            searchButton.prop('disabled', false);
        });
    });

    // Handle clicking the "View as this User" button from search results
    $('body').on('click', '.start-view-as-button', function(e) {
        e.preventDefault();

        var userId = $(this).data('user-id');

        var formData = {
            action: 'psych_coach_start_view_as',
            nonce: psych_coach_vars.nonce,
            user_id: userId
        };

        $.post(psych_coach_vars.ajax_url, formData, function(response) {
            if (response.success) {
                // Reload the page to enter "view as" mode
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('An unexpected error occurred.');
        });
    });

    // Handle exiting "view as" mode
    $('body').on('click', '.exit-view-as-button', function(e) {
        e.preventDefault();

        var formData = {
            action: 'psych_coach_exit_view_as',
            nonce: psych_coach_vars.nonce,
        };

        $.post(psych_coach_vars.ajax_url, formData, function(response) {
            if (response.success) {
                // Reload the page to return to normal coach view
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('An unexpected error occurred.');
        });
    });

});
