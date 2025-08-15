document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('myth-buster-container')) return;

    const myths = myth_buster_data.myths || [];
    const settings = myth_buster_data.settings || {};
    const text = myth_buster_data.text || {};

    let currentMythIndex = 0;
    let countdownDuration = 5;
    let timerInterval;

    const dom = {
        body: document.getElementById('body-container'),
        app: document.getElementById('app-container'),
        mythText: document.getElementById('myth-text'),
        countdownText: document.getElementById('countdown-text'),
        timerCircle: document.getElementById('timer-circle'),
        qSection: document.getElementById('question-section'),
        timerSection: document.getElementById('timer-section'),
        answerSection: document.getElementById('answer-section'),
        answerTitle: document.getElementById('answer-title'),
        answerExplanation: document.getElementById('answer-explanation'),
        buttons: document.getElementById('buttons-container'),
        particles: document.getElementById('particles-container')
    };

    const circleCircumference = 2 * Math.PI * 45;

    const sounds = {
        correct: settings.sound_correct ? new Audio(settings.sound_correct) : null,
        incorrect: settings.sound_incorrect ? new Audio(settings.sound_incorrect) : null,
        tick: settings.sound_tick ? new Audio(settings.sound_tick) : null,
        background: settings.sound_bg ? new Audio(settings.sound_bg) : null
    };

    if (sounds.background) {
        sounds.background.loop = true;
        sounds.background.volume = 0.2;
        // sounds.background.play().catch(e => console.error("BG Sound Playback Error:", e));
    }

    const themeParticleColors = {
        'theme-fire-and-ice': { fact: ['#80ffdb', '#72efdd', '#64dfdf'], myth: ['#ff6f00', '#ff8f40', '#ffaf73'] },
        'theme-nature': { fact: ['#adff2f', '#9acd32', '#6b8e23'], myth: ['#a52a2a', '#d2691e', '#8b4513'] },
        'theme-space': { fact: ['#00ffff', '#7df9ff', '#b6fafb'], myth: ['#ff4500', '#ff6347', '#ff7f50'] }
    };

    function createParticles(isFact) {
        const count = 70;
        const currentTheme = settings.theme || 'theme-fire-and-ice';
        const colors = themeParticleColors[currentTheme] ? themeParticleColors[currentTheme][isFact ? 'fact' : 'myth'] : themeParticleColors['theme-fire-and-ice'][isFact ? 'fact' : 'myth'];

        if (!dom.particles) return;
        dom.particles.innerHTML = ''; // Clear previous particles

        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.classList.add('particle');
            const size = Math.random() * (isFact ? 15 : 10) + 5;
            p.style.width = `${size}px`;
            p.style.height = `${size}px`;
            p.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            const angle = Math.random() * 360;
            const radius = Math.random() * (isFact ? 250 : 500) + 150;
            const x = Math.cos(angle) * radius;
            const y = isFact ? -Math.abs(Math.sin(angle) * radius) - 150 : Math.sin(angle) * radius;
            p.style.setProperty('--x', `${x}px`);
            p.style.setProperty('--y', `${y}px`);
            p.style.left = '50%';
            p.style.top = '60%';
            dom.particles.appendChild(p);
            setTimeout(() => p.remove(), 2000);
        }
    }

    function loadMyth(index) {
        if (!myths || myths.length === 0) {
            dom.mythText.textContent = text.add_beliefs_prompt;
            if(dom.timerSection) dom.timerSection.classList.add('hidden');
            if(dom.buttons) dom.buttons.classList.add('hidden');
            return;
        }
        if(dom.timerSection) dom.timerSection.classList.remove('hidden');
        if(dom.buttons) dom.buttons.classList.remove('hidden');

        const myth = myths[index];
        dom.mythText.textContent = myth.myth;
        countdownDuration = parseInt(myth.duration, 10) || 5;

        // Set background image
        if (myth.image) {
            dom.body.style.backgroundImage = `url(${myth.image})`;
        } else {
            dom.body.style.backgroundImage = 'none';
        }
    }

    function startCountdown() {
        if (!myths || myths.length === 0) return;
        let timeLeft = countdownDuration;
        if (dom.timerCircle) dom.timerCircle.style.transition = 'stroke-dashoffset 1s linear';

        const updateTimer = () => {
            if (dom.countdownText) dom.countdownText.textContent = timeLeft;
            const offset = circleCircumference * (1 - (timeLeft / countdownDuration));
            if (dom.timerCircle) dom.timerCircle.style.strokeDashoffset = offset;

            if (timeLeft > 0) {
                if(sounds.tick) sounds.tick.play().catch(e => {});
            }

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                revealAnswer();
            } else {
                timeLeft--;
            }
        };
        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }

    function revealAnswer() {
        const myth = myths[currentMythIndex];
        if(dom.qSection) dom.qSection.classList.add('hidden');
        if(dom.timerSection) dom.timerSection.classList.add('hidden');
        if(dom.answerSection) dom.answerSection.classList.remove('hidden');

        const isFact = myth.isFact === 'true';
        createParticles(isFact);
        if (isFact) {
            dom.answerTitle.textContent = text.fact;
            dom.answerTitle.className = 'text-3xl font-black mb-4 text-green-400';
            if(sounds.correct) sounds.correct.play().catch(e => {});
        } else {
            dom.answerTitle.textContent = text.myth;
            dom.answerTitle.className = 'text-3xl font-black mb-4 text-red-500';
            if(sounds.incorrect) sounds.incorrect.play().catch(e => {});
        }
        dom.answerExplanation.textContent = myth.explanation;
    }

    window.recordGuess = function(guess) {
        if(dom.buttons) {
            dom.buttons.style.pointerEvents = 'none';
            dom.buttons.style.opacity = '0.5';
        }
    }

    window.resetApp = function() {
        if (!myths || myths.length === 0) return;
        currentMythIndex = (currentMythIndex + 1) % myths.length;
        if(dom.answerSection) dom.answerSection.classList.add('hidden');
        if(dom.qSection) dom.qSection.classList.remove('hidden');
        if(dom.timerSection) dom.timerSection.classList.remove('hidden');

        if(dom.buttons) {
            dom.buttons.style.pointerEvents = 'auto';
            dom.buttons.style.opacity = '1';
        }

        if(dom.timerCircle) {
            dom.timerCircle.style.transition = 'none';
            dom.timerCircle.style.strokeDashoffset = 0;
        }

        loadMyth(currentMythIndex);
        clearInterval(timerInterval);
        setTimeout(startCountdown, 100);
    }

    function initGame() {
        if (dom.body) dom.body.classList.add(settings.theme || 'theme-fire-and-ice');
        if (dom.timerCircle) dom.timerCircle.style.strokeDasharray = circleCircumference;

        loadMyth(currentMythIndex);
        if (myths && myths.length > 0) {
            startCountdown();
        }
    }

    initGame();
});
