(function( $ ) {
    'use strict';

    $(document).ready(function() {
        // Handle adding new question rows
        $('#add-question').on('click', function() {
            var questionRepeater = $('#question-repeater');
            var newIndex = questionRepeater.find('.question-row').length;

            var newRow = '<div class="question-row">' +
                '<label>Question Text:</label>' +
                '<input type="text" name="eca_questions[' + newIndex + '][text]" value="" class="widefat" />' +
                '<label>Holland Code:</label>' +
                '<select name="eca_questions[' + newIndex + '][code]">' +
                '<option value="R">R</option>' +
                '<option value="I">I</option>' +
                '<option value="A">A</option>' +
                '<option value="S">S</option>' +
                '<option value="E">E</option>' +
                '<option value="C">C</option>' +
                '</select>' +
                '<button type="button" class="button remove-question">Remove</button>' +
                '</div>';

            questionRepeater.append(newRow);
        });

        // Handle removing question rows
        $('#question-repeater').on('click', '.remove-question', function() {
            $(this).closest('.question-row').remove();
            // Note: This doesn't re-index the array keys, but PHP handles non-sequential keys just fine.
        });
    });

})( jQuery );
