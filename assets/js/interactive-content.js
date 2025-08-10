/**
 * JavaScript for Psych Interactive Content Module
 */
jQuery(document).ready(function($) {
    const modal = $('#psych-modal-overlay');
    const modalContent = $('#psych-modal-content');

    function showModal(content, title) {
        if (modal.length === 0) {
            // Create modal if it doesn't exist from another module
            $('body').append('<div class="psych-modal-overlay" id="psych-modal-overlay" style="display: none;"><div class="psych-modal-dialog"><button class="psych-modal-close">&times;</button><div id="psych-modal-content"></div></div></div>');
        }
        modalContent.html('<h2>' + (title || 'Info') + '</h2>' + content);
        modal.fadeIn();
    }

    function closeModal() {
        modal.fadeOut();
    }

    // Event handler for psych_button
    $(document).on('click', '.psych-button', function(e) {
        const button = $(this);
        const action = button.data('action');
        const targetSelector = button.data('target');

        if (action === 'mission') {
            // This will be handled by the path-engine.js, so we do nothing here.
            return;
        }

        e.preventDefault();

        switch (action) {
            case 'modal':
                const targetElement = $(targetSelector);
                if (targetElement.length) {
                    showModal(targetElement.html(), targetElement.data('title'));
                }
                break;
            case 'toggle':
                $(targetSelector).slideToggle(300);
                break;
        }
    });

    // Modal close handlers
    $('body').on('click', '.psych-modal-close', closeModal);
    $('body').on('click', '.psych-modal-overlay', function(e) {
        if ($(e.target).is('.psych-modal-overlay')) {
            closeModal();
        }
    });
    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && modal.is(':visible')) {
            closeModal();
        }
    });
});
