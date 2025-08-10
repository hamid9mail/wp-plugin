jQuery(document).ready(function($) {
    // This script now handles both the main path engine logic and the conversational GForm logic.

    // --- Conversational GForm Logic ---
    function initializeConversationalForms() {
        var $forms = $(".psych-convo-gform");
        $forms.each(function(){
            var $form = $(this);
            var $pages = $form.find(".gform_page");
            if ($pages.length < 2) return;

            if ($form.find(".psych-gform-progress").length == 0) {
                $form.find(".gform_body").prepend('<div class="psych-gform-pagenum"></div><div class="psych-gform-progress"><div class="psych-gform-progress-bar"></div></div>');
            }

            let idx = 0, total = $pages.length;
            showPage(idx);

            function showPage(i){
                $pages.removeClass("active").eq(i).addClass("active");
                var progress = Math.floor(100 * (i + 1) / total);
                $form.find(".psych-gform-progress-bar").css("width", progress + "%");
                $form.find(".psych-gform-pagenum").text('سوال ' + toPersian(i + 1) + ' از ' + toPersian(total));
                $pages.find(".gform_page_footer").hide();
                if ($form.find(".psych-gform-nav").length == 0) {
                    $form.find(".gform_body").append(`
                        <div class="psych-gform-nav" style="display:flex;gap:8px;justify-content:center;">
                            <button type="button" class="psych-gform-prev" style="display:none">قبلی</button>
                            <button type="button" class="psych-gform-next">بعدی</button>
                            <button type="submit" class="psych-gform-submit" style="display:none">پایان</button>
                        </div>
                    `);
                }
                $form.find(".psych-gform-prev").toggle(i > 0);
                $form.find(".psych-gform-next").toggle(i < total - 1);
                $form.find(".psych-gform-submit").toggle(i == total - 1);
            }

            $form.off('click.psych').on('click.psych', '.psych-gform-next', function(e){
                e.preventDefault();
                idx = Math.min(idx + 1, total - 1);
                showPage(idx);
            });
            $form.on('click.psych', '.psych-gform-prev', function(e){
                e.preventDefault();
                idx = Math.max(idx - 1, 0);
                showPage(idx);
            });
            $form.on('click.psych input.psych', 'input[type=radio],input[type=checkbox]', function(){
                if (idx < total - 1) {
                    setTimeout(() => { $form.find('.psych-gform-next').trigger('click'); }, 170);
                }
            });

            function toPersian(n){
                return n.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
            }
        });
    }

    // --- Path Engine Modal Logic ---
    const modal = $('#psych-station-modal');
    const modalTitle = $('.psych-modal-title');
    const modalContent = $('.psych-modal-content');
    let currentStationDetails = null;
    let currentPathContainer = null;

    function showModal() {
        modal.fadeIn(300);
        $('body').addClass('modal-open').css('overflow', 'hidden');
    }

    function closeModal() {
        modal.fadeOut(300);
        $('body').removeClass('modal-open').css('overflow', '');
    }

    function showRewardsNotification(rewards) {
        let rewardsHtml = '<ul>';
        if (rewards && rewards.points) rewardsHtml += `<li><i class="fas fa-star"></i> شما <strong>${rewards.points}</strong> امتیاز کسب کردید!</li>`;
        if (rewards && rewards.badge) rewardsHtml += `<li><i class="fas fa-medal"></i> نشان <strong>"${rewards.badge}"</strong> را دریافت نمودید!</li>`;
        if (rewards && rewards.next_station_message) rewardsHtml += `<li><i class="fas fa-arrow-right"></i> ${rewards.next_station_message}</li>`;
        if (rewardsHtml === '<ul>') rewardsHtml += '<li><i class="fas fa-check-circle"></i> با موفقیت انجام شد!</li>';
        rewardsHtml += '</ul>';
        const notificationHtml = `<div class="psych-rewards-overlay"><div class="psych-rewards-popup"><div class="psych-rewards-header"><i class="fas fa-gift"></i><h3>عالی بود!</h3></div><div class="psych-rewards-body">${rewardsHtml}</div><button class="psych-rewards-close">ادامه می‌دهم</button></div></div>`;
        $('body').append(notificationHtml);
        if (typeof confetti === 'function') confetti({ particleCount: 150, spread: 90, origin: { y: 0.6 } });
        const $overlay = $('.psych-rewards-overlay').fadeIn(200);
        $overlay.on('click', '.psych-rewards-close, .psych-rewards-overlay', function(e) {
            if (e.target === this || $(e.target).hasClass('psych-rewards-close') || $(e.target).hasClass('psych-rewards-overlay')) {
                $overlay.fadeOut(300, () => $overlay.remove());
            }
        });
    }

    function updateAllUI($pathContainer) {
        if (!$pathContainer || !$pathContainer.length) return;

        const userCompletedStations = {};
        $pathContainer.find('.completed[data-station-node-id]').each(function() {
            userCompletedStations[$(this).data('station-node-id')] = true;
        });

        let previousStationCompleted = true;

        $pathContainer.find('[data-station-node-id]').each(function() {
            const $station = $(this);
            const details = $station.data('station-details') || {};
            const nodeId = details.station_node_id;

            let isUnlocked = details.unlock_trigger === 'independent' || previousStationCompleted;

            if (userCompletedStations[nodeId]) {
                $station.removeClass('open locked').addClass('completed');
                $station.find('.psych-status-badge').removeClass('open locked').addClass('completed').html('<i class="fas fa-check"></i> تکمیل شده');
                $station.find('.psych-timeline-icon i, .psych-accordion-icon i, .psych-card-icon i').attr('class', 'fas fa-check-circle');
                $station.find('.psych-list-number').html('<i class="fas fa-check"></i>');
                $station.find('.psych-station-action-btn, .psych-accordion-action-btn, .psych-card-action-btn, .psych-list-action-btn').text('مشاهده نتیجه').prop('disabled', false);
            } else if (isUnlocked) {
                $station.removeClass('locked completed').addClass('open');
                $station.find('.psych-status-badge').removeClass('locked').addClass('open').html('<i class="fas fa-unlock"></i> باز');
                $station.find('.psych-timeline-icon i, .psych-accordion-icon i, .psych-card-icon i').attr('class', details.icon || 'fas fa-door-open');
                $station.find('.psych-station-action-btn, .psych-accordion-action-btn, .psych-card-action-btn, .psych-list-action-btn').text(details.mission_button_text || 'مشاهده ماموریت').prop('disabled', false);
            } else {
                $station.removeClass('open completed').addClass('locked');
                $station.find('.psych-status-badge').removeClass('open').addClass('locked').html('<i class="fas fa-lock"></i> قفل');
                $station.find('.psych-station-action-btn, .psych-accordion-action-btn, .psych-card-action-btn, .psych-list-action-btn').text('قفل').prop('disabled', true);
            }

            if (details.unlock_trigger === 'sequential') {
                previousStationCompleted = userCompletedStations[nodeId] || false;
            }
        });

        const total = $pathContainer.find('[data-station-node-id]').length;
        const completedCount = Object.keys(userCompletedStations).length;
        const percentage = total > 0 ? Math.round((completedCount / total) * 100) : 0;
        $pathContainer.find('.psych-progress-text').text(`پیشرفت: ${completedCount} از ${total} ایستگاه`);
        $pathContainer.find('.psych-progress-percentage').text(`${percentage}%`);
        $pathContainer.find('.psych-progress-fill').css('width', `${percentage}%`);
    }

    function completeMission(stationDetails, $button, $pathContainer) {
        const originalHtml = $button.html();
        const spinner = '<span class="psych-loading-spinner" style="width:16px; height:16px; border-width:2px; display:inline-block; vertical-align:middle; margin-right:8px; border-style:solid; border-radius:50%; border-color:currentColor; border-top-color:transparent; animation:spin 1s linear infinite;"></span>';
        $button.prop('disabled', true).html(spinner + 'در حال پردازش...');

        $.post(psych_path_ajax.ajax_url, {
            action: 'psych_path_complete_mission',
            nonce: psych_path_ajax.nonce,
            node_id: stationDetails.station_node_id,
            station_data: JSON.stringify(stationDetails)
        })
        .done(function(response) {
            if (response.success) {
                if (modal.is(':visible')) closeModal();
                $pathContainer.find(`[data-station-node-id="${stationDetails.station_node_id}"]`).addClass('completed');
                updateAllUI($pathContainer);
                showRewardsNotification(response.data.rewards);
            } else {
                $button.prop('disabled', false).html(originalHtml);
                alert(response.data.message || 'خطا در تکمیل ماموریت.');
            }
        })
        .fail(function() {
            $button.prop('disabled', false).html(originalHtml);
            alert('خطا در ارتباط با سرور.');
        });
    }

    $('body').on('click', '.psych-station-action-btn, .psych-accordion-action-btn, .psych-card-action-btn, .psych-list-action-btn, .psych-treasure-action-btn', function(e) {
        e.preventDefault();
        const $button = $(this);
        if ($button.is(':disabled')) return;

        currentPathContainer = $button.closest('.psych-path-container');
        currentStationDetails = $button.closest('[data-station-node-id]').data('station-details');

        if (!currentStationDetails) return;

        modalTitle.text(currentStationDetails.title);
        modalContent.html('<div class="psych-loading-spinner"></div>');
        showModal();
        $.post(psych_path_ajax.ajax_url, {
            action: 'psych_path_get_station_content',
            nonce: psych_path_ajax.nonce,
            station_data: JSON.stringify(currentStationDetails)
        }).done(res => {
            modalContent.html(res.success ? res.data.html : `<div class="psych-alert psych-alert-danger">${res.data.message || 'خطا'}</div>`);
            initializeConversationalForms(); // Re-initialize for content loaded via AJAX
        }).fail(() => {
            modalContent.html('<div class="psych-alert psych-alert-danger">خطای سرور.</div>');
        });
    });

    $('body').on('click', '.psych-complete-mission-btn', function(e) {
        e.preventDefault();
        if ($(this).is(':disabled')) return;

        let details;
        let container;

        if (modal.is(':visible')) {
            details = currentStationDetails;
            container = currentPathContainer;
        } else {
            details = $(this).closest('[data-station-node-id]').data('station-details');
            container = $(this).closest('.psych-path-container');
        }

        if (details && container && container.length) {
            completeMission(details, $(this), container);
        }
    });

    $('body').on('click', '.psych-accordion-header', function(e) {
        if ($(e.target).is('button, a')) return;
        $(this).closest('.psych-accordion-item').find('.psych-accordion-content').slideToggle(300);
    });

    $('.psych-modal-close, .psych-modal-overlay').on('click', function(e) {
        if (e.target === this) closeModal();
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && modal.is(':visible')) closeModal();
    });

    // Initial load for any conversational forms on the page
    initializeConversationalForms();
});
