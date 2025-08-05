/**
 * Psych Complete System - Report Card Script
 *
 * This file handles the interactive elements of the report card,
 * including tabs, charts, and AJAX interactions.
 *
 * @version 5.1.0
 */
var PsychReportCard = {
    init: function() {
        // Ensure data is available before initializing
        if (typeof psych_report_card === 'undefined') {
            console.error('Psych Report Card data is not available.');
            return;
        }
        this.initTabs();
        this.initCharts();
        this.initProgressBars();
        this.initNotesAutoSave();
        this.initSendReportButton();
    },

    initTabs: function() {
        var tabsContainer = document.querySelector('.psych-report-card-container');
        if (!tabsContainer) return;

        var tabs = tabsContainer.querySelectorAll('.tab-link');
        var contents = tabsContainer.querySelectorAll('.psych-tab-content');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var targetId = this.dataset.tab;

                tabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                contents.forEach(function(content) {
                    if (content.id === targetId) {
                        content.classList.add('active');
                    } else {
                        content.classList.remove('active');
                    }
                });

                // Re-initialize charts if the analytics tab is opened
                if (targetId === 'psych-tab-analytics') {
                    PsychReportCard.initCharts();
                }
            });
        });

        // Activate the first tab by default if one exists
        if (tabs.length > 0) {
            tabs[0].click();
        }
    },

    initCharts: function() {
        // Progress Chart
        var progressCtx = document.getElementById('psych-progress-chart');
        if (progressCtx && typeof Chart !== 'undefined') {
            // Prevent re-initialization on the same canvas
            if (progressCtx.chart) {
                progressCtx.chart.destroy();
            }
            try {
                var progressData = JSON.parse(progressCtx.dataset.chartData);
                progressCtx.chart = new Chart(progressCtx, {
                    type: 'line',
                    data: {
                        labels: progressData.labels,
                        datasets: [{
                            label: 'پیشرفت امتیازات',
                            data: progressData.points,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            } catch (e) {
                console.error("Failed to parse progress chart data:", e);
            }
        }

        // Badges Chart
        var badgesCtx = document.getElementById('psych-badges-chart');
        if (badgesCtx && typeof Chart !== 'undefined') {
            // Prevent re-initialization
            if (badgesCtx.chart) {
                badgesCtx.chart.destroy();
            }
            try {
                var badgesData = JSON.parse(badgesCtx.dataset.chartData);
                badgesCtx.chart = new Chart(badgesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['کسب‌شده', 'باقیمانده'],
                        datasets: [{
                            data: [badgesData.earned, badgesData.remaining],
                            backgroundColor: ['#2ecc71', '#ecf0f1'],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            position: 'top',
                        },
                    }
                });
            } catch (e) {
                console.error("Failed to parse badges chart data:", e);
            }
        }
    },

    initProgressBars: function() {
        document.querySelectorAll('.psych-progress-fill').forEach(function(bar) {
            // Animate the width on initialization
            var percent = bar.dataset.percent || '0';
            bar.style.width = percent + '%';
        });
    },

    initNotesAutoSave: function() {
        var debounce = function(func, delay) {
            var timer;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function() {
                    func.apply(context, args);
                }, delay);
            };
        };

        var notesTextarea = document.getElementById('psych-user-notes');
        if (notesTextarea) {
            notesTextarea.addEventListener('input', debounce(function() {
                PsychReportCard.saveUserData('notes', this.value, 'psych_save_user_notes');
            }, 1500));
        }

        var goalsTextarea = document.getElementById('psych-user-goals');
        if (goalsTextarea) {
            goalsTextarea.addEventListener('input', debounce(function() {
                PsychReportCard.saveUserData('goals', this.value, 'psych_save_user_goals');
            }, 1500));
        }
    },

    saveUserData: function(type, content, ajaxAction) {
        var statusIndicator = document.createElement('span');
        statusIndicator.className = 'psych-save-status';
        statusIndicator.textContent = ' در حال ذخیره...';

        var targetTextarea = document.getElementById('psych-user-' + type);
        if (targetTextarea && !targetTextarea.nextElementSibling?.classList.contains('psych-save-status')) {
             targetTextarea.parentNode.insertBefore(statusIndicator, targetTextarea.nextSibling);
        }

        fetch(psych_report_card.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: ajaxAction,
                nonce: psych_report_card.nonce,
                content: content,
                user_id: psych_report_card.viewing_context.viewed_user_id
            })
        }).then(response => response.json()).then(data => {
            if (data.success) {
                statusIndicator.textContent = ' ذخیره شد!';
                statusIndicator.style.color = 'green';
            } else {
                statusIndicator.textContent = ' خطا در ذخیره.';
                statusIndicator.style.color = 'red';
            }
            setTimeout(() => statusIndicator.remove(), 3000);
        }).catch(() => {
            statusIndicator.textContent = ' خطای شبکه.';
            statusIndicator.style.color = 'red';
            setTimeout(() => statusIndicator.remove(), 3000);
        });
    },

    initSendReportButton: function() {
        var sendButton = document.getElementById('send-parent-report');
        if (sendButton) {
            sendButton.addEventListener('click', function(e) {
                e.preventDefault();
                var mobileInput = document.getElementById('parent-mobile');
                var mobile = mobileInput.value;
                var alertContainer = document.getElementById('psych-parent-report-alert');

                if (!alertContainer) {
                    alertContainer = document.createElement('div');
                    alertContainer.id = 'psych-parent-report-alert';
                    mobileInput.parentNode.appendChild(alertContainer);
                }

                if (!mobile || !/^[0-9]{10,11}$/.test(mobile)) {
                    alertContainer.className = 'psych-alert error';
                    alertContainer.textContent = 'لطفاً یک شماره موبایل معتبر وارد کنید.';
                    return;
                }

                this.innerHTML = '<span class="psych-loading-spinner"></span> در حال ارسال...';
                this.disabled = true;

                fetch(psych_report_card.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'psych_send_parent_report',
                        nonce: psych_report_card.nonce,
                        mobile: mobile,
                        user_id: psych_report_card.viewing_context.viewed_user_id
                    })
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        alertContainer.className = 'psych-alert success';
                        alertContainer.textContent = data.data.message;
                    } else {
                        alertContainer.className = 'psych-alert error';
                        alertContainer.textContent = data.data.message;
                    }
                }).catch(() => {
                     alertContainer.className = 'psych-alert error';
                     alertContainer.textContent = 'خطای شبکه در ارسال گزارش.';
                }).finally(() => {
                    this.innerHTML = 'ارسال گزارش به والدین';
                    this.disabled = false;
                });
            });
        }
    }
};

// Initialize the script once the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    PsychReportCard.init();
});
