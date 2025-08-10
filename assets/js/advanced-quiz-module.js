/**
 * JavaScript for Advanced Quiz Module
 */

jQuery(document).ready(function($) {

    // Handle the main quiz form submission
    $('body').on('submit', '.psych-quiz-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var quizId = form.data('quiz-id');
        var resultsContainer = $('#psych-quiz-results-container-' + quizId);
        var submitButton = form.find('.submit-quiz-button');

        // Disable button to prevent multiple submissions
        submitButton.prop('disabled', true).text('Submitting...');

        var answers = [];
        form.find('.quiz-question').each(function() {
            var question = $(this);
            var questionId = question.data('question-id');
            var selectedOption = question.find('input[type="radio"]:checked');

            if (selectedOption.length > 0) {
                answers.push({
                    question_id: questionId,
                    value: selectedOption.val(),
                    is_correct: selectedOption.data('correct') // Assuming correct flag is stored in data attribute
                });
            }
        });

        // Basic validation
        var totalQuestions = form.find('.quiz-question').length;
        if (answers.length < totalQuestions) {
            alert('Please answer all questions before submitting.');
            submitButton.prop('disabled', false).text('Submit Quiz');
            return;
        }

        var formData = {
            action: 'psych_submit_quiz',
            nonce: psych_quiz_vars.nonce,
            quiz_id: quizId,
            answers: answers
        };

        // AJAX request
        $.post(psych_quiz_vars.ajax_url, formData, function(response) {
            if (response.success) {
                // Hide the form and show the results
                form.slideUp();
                resultsContainer.html(response.data.html).slideDown();
            } else {
                resultsContainer.html('<div class="error">Error: ' + response.data.message + '</div>').slideDown();
                submitButton.prop('disabled', false).text('Submit Quiz');
            }
        }).fail(function() {
            resultsContainer.html('<div class="error">An unexpected error occurred. Please try again.</div>').slideDown();
            submitButton.prop('disabled', false).text('Submit Quiz');
        });
    });

    // Update progress bar as user answers questions
    $('.psych-quiz-form').each(function() {
        var form = $(this);
        var progressBar = form.find('.quiz-progress-bar-inner');
        var questions = form.find('.quiz-question');
        var totalQuestions = questions.length;

        form.find('input[type="radio"]').on('change', function() {
            var answeredCount = form.find('input[type="radio"]:checked').length;
            var progressPercent = (answeredCount / totalQuestions) * 100;
            progressBar.css('width', progressPercent + '%');
        });
    });

});
