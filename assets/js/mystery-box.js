/**
 * Psych Complete System - Mystery Box Component
 *
 * This script handles the display and animation of the
 * "Mystery Box" variable reward.
 *
 * @version 1.0.0
 */
var PsychMysteryBox = {

    init: function(reward) {
        // reward object should contain { rarity: 'common'/'uncommon'/'rare', points: 25, badge: 'badge_slug' (optional) }
        this.createModal(reward);
    },

    createModal: function(reward) {
        // Create modal overlay
        var overlay = document.createElement('div');
        overlay.className = 'psych-mystery-box-overlay';

        // Create modal content
        var content = document.createElement('div');
        content.className = 'psych-mystery-box-content';

        // Add treasure chest
        var chest = document.createElement('div');
        chest.className = 'psych-mystery-chest';
        content.appendChild(chest);

        // Add result text area (initially hidden)
        var resultText = document.createElement('div');
        resultText.className = 'psych-mystery-result-text';
        resultText.style.display = 'none';
        content.appendChild(resultText);

        overlay.appendChild(content);
        document.body.appendChild(overlay);

        // Start the animation
        setTimeout(function() {
            chest.classList.add('open');
            // After animation, show the reward
            setTimeout(function() {
                PsychMysteryBox.showReward(resultText, reward);
            }, 1000); // Corresponds to animation duration
        }, 500);

        // Close modal on click
        overlay.addEventListener('click', function() {
            overlay.remove();
        });
    },

    showReward: function(element, reward) {
        var rarityClass = 'rarity-' + reward.rarity;
        var html = '<h3 class="' + rarityClass + '">شما برنده شدید!</h3>';
        html += '<p class="points-won">+' + reward.points + ' امتیاز</p>';

        if (reward.badge) {
            html += '<p class="badge-won">و نشان کمیاب: ' + reward.badge_name + '!</p>';
        }

        element.innerHTML = html;
        element.style.display = 'block';
    }
};
