/**
 * JavaScript for Psych Advanced Quiz Module
 */
jQuery(document).ready(function($) {

    $('.quiz-container').each(function() {
        const container = $(this);
        const quizId = container.data('quiz-id');
        const questions = container.data('questions') || [];
        const aiEnabled = container.data('ai') === true;

        let currentQuestionIndex = 0;
        let score = 0;
        let responses = {};

        const questionEl = container.find('.question');
        const optionsEl = container.find('.options');
        const feedbackEl = container.find('.feedback');
        const resultEl = container.find('.result');
        const startButton = container.find('.start-quiz-button');
        const loadingEl = container.find('.loading-result');

        startButton.on('click', function() {
            $(this).hide();
            showQuestion();
        });

        function showQuestion() {
            if (currentQuestionIndex >= questions.length) {
                showFinalResult();
                return;
            }

            feedbackEl.hide();
            const question = questions[currentQuestionIndex];
            questionEl.html(question.question);
            optionsEl.empty().show();

            if (question.type === 'mcq') {
                const shuffledOptions = shuffleArray(question.options);
                shuffledOptions.forEach(opt => {
                    const btn = $('<button>').html(opt.text).data('value', opt.value);
                    btn.on('click', () => selectAnswer(opt, question));
                    optionsEl.append(btn);
                });
            }
            // Add placeholders for other question types here
            // else if (question.type === 'dragdrop') { ... }
        }

        function selectAnswer(selectedOption, question) {
            const isCorrect = selectedOption.value === question.correct_answer;
            if (isCorrect) {
                score++;
                feedbackEl.text('Correct!').removeClass('incorrect').addClass('correct').show();
            } else {
                feedbackEl.text('Incorrect.').removeClass('correct').addClass('incorrect').show();
            }

            responses[question.id] = {
                'value': selectedOption.value,
                'is_correct': isCorrect
            };

            currentQuestionIndex++;
            setTimeout(showQuestion, 1200); // Wait a moment before showing the next question
        }

        function showFinalResult() {
            questionEl.hide();
            optionsEl.hide();
            feedbackEl.hide();
            loadingEl.show();

            $.ajax({
                url: psych_quiz_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'save_quiz_results',
                    nonce: psych_quiz_ajax.nonce,
                    quiz_id: quizId,
                    score: score,
                    responses: JSON.stringify(responses),
                    ai: aiEnabled
                },
                success: function(response) {
                    loadingEl.hide();
                    if (response.success) {
                        let resultHtml = '<h3>Quiz Complete!</h3>';
                        resultHtml += '<p>Your score: ' + score + ' / ' + questions.length + '</p>';
                        if (aiEnabled && response.data.ai_analysis) {
                            resultHtml += '<h4>AI Analysis:</h4><p>' + response.data.ai_analysis + '</p>';
                        }
                        resultEl.html(resultHtml).show();
                    } else {
                        resultEl.html('<p class="error">Error saving results.</p>').show();
                    }
                },
                error: function() {
                    loadingEl.hide();
                    resultEl.html('<p class="error">Server error occurred.</p>').show();
                }
            });
        }

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }
    });
});
