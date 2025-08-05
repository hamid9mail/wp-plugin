jQuery(document).ready(function($) {
    $('.psych-quiz-container').each(function() {
        var container = $(this);
        var quizData = container.data('quiz-data');
        var contentDiv = container.find('.psych-quiz-content');
        var nextButton = container.find('.psych-quiz-next');
        var resultsDiv = container.find('.psych-quiz-results');
        var currentQuestionIndex = 0;
        var userResponses = {};

        function renderCurrentQuestion() {
            if (currentQuestionIndex >= quizData.questions.length) {
                showResults();
                return;
            }
            var question = quizData.questions[currentQuestionIndex];
            var qHtml = '<div class="psych-question" data-type="' + question.type + '" data-id="' + question.id + '">';
            qHtml += '<p class="psych-question-title">' + question.q + '</p>';
            qHtml += '<div class="psych-question-options">';

            switch(question.type) {
                case 'mcq': /* ... existing mcq logic ... */ break;
                case 'ordering':
                    qHtml += '<ul class="ordering-list">';
                    $.each(question.options, function(i, opt) { qHtml += '<li class="ordering-list-item">' + opt + '</li>'; });
                    qHtml += '</ul>';
                    break;
                case 'hotspot':
                    qHtml += '<div class="hotspot-container"><img src="' + question.image_url + '" /></div>';
                    break;
                case 'fill_in_the_blanks':
                    var textWithBlanks = question.text.replace(/\[blank\]/g, '<input type="text" class="fill-blank" />');
                    qHtml += '<div>' + textWithBlanks + '</div>';
                    break;
                case 'nps':
                    qHtml += '<div class="nps-scale">';
                    for(var i=0; i<=10; i++) { qHtml += '<span data-value="' + i + '">' + i + '</span>'; }
                    qHtml += '</div>';
                    break;
                // ... other cases ...
            }
            qHtml += '</div></div>';
            contentDiv.html(qHtml);

            if (question.type === 'ordering') $('.ordering-list').sortable();
            if (question.type === 'hotspot') {
                $('.hotspot-container').on('click', function(e) {
                    var offset = $(this).offset();
                    var x = e.pageX - offset.left;
                    var y = e.pageY - offset.top;
                    $(this).append('<div class="hotspot-point" style="left:'+x+'px; top:'+y+'px;"></div>');
                });
            }
             $('.nps-scale span').on('click', function() {
                $(this).addClass('selected').siblings().removeClass('selected');
            });
        }

        function saveCurrentResponse() {
            var questionDiv = contentDiv.find('.psych-question');
            var type = questionDiv.data('type');
            var id = questionDiv.data('id');
            var response;

             switch(type) {
                case 'mcq': /* ... */ break;
                case 'ordering':
                    response = $('.ordering-list-item', questionDiv).map(function() { return $(this).text(); }).get();
                    break;
                case 'hotspot':
                    response = $('.hotspot-point', questionDiv).map(function() { return { x: $(this).css('left'), y: $(this).css('top') }; }).get();
                    break;
                case 'fill_in_the_blanks':
                    response = $('.fill-blank', questionDiv).map(function() { return $(this).val(); }).get();
                    break;
                case 'nps':
                    response = $('.nps-scale span.selected', questionDiv).data('value');
                    break;
                // ... other cases ...
            }
            userResponses[id] = response;
        }

        function showResults() {
            contentDiv.hide();
            nextButton.hide();
            resultsDiv.html('<h3>Quiz Complete!</h3><p>Thank you for your responses.</p>');
            // AJAX call to save results
            $.post(ajaxurl, { action: 'psych_save_quiz_results', responses: userResponses });
        }

        nextButton.on('click', function() {
            saveCurrentResponse();
            currentQuestionIndex++;
            renderCurrentQuestion();
        });

        renderCurrentQuestion();
    });
});
