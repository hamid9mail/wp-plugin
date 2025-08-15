jQuery(document).ready(function($) {
    // Tabs
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active').hide();
        $($(this).attr('href')).addClass('active').show();
    });

    // Make sets sortable
    $('#belief-sets-container').sortable({
        handle: '.handle',
        update: function() {
            updateFieldNames();
        }
    });

    // Add new belief set
    $('#add-set').on('click', function() {
        const setIndex = $('#belief-sets-container .myth-set').length;
        const newSet = `
        <div class="myth-set" data-set-index="${setIndex}">
            <h3 class="handle">Set Name: <input type="text" name="myth_buster_options[belief_sets][${setIndex}][name]" value="New Set"> <button type="button" class="button remove-set">Remove Set</button></h3>
            <div class="myths-container"></div>
            <button type="button" class="button add-myth">Add New Myth</button>
        </div>`;
        $('#belief-sets-container').append(newSet);
        updateFieldNames();
    });

    // Remove belief set
    $('#belief-sets-container').on('click', '.remove-set', function() {
        if (confirm('Are you sure you want to remove this entire set?')) {
            $(this).closest('.myth-set').remove();
            updateFieldNames();
        }
    });

    // Add new myth
    $('#belief-sets-container').on('click', '.add-myth', function() {
        const setContainer = $(this).closest('.myth-set');
        const setIndex = setContainer.data('set-index');
        const mythIndex = setContainer.find('.myth-item').length;
        const newMyth = `
        <div class="myth-item">
            <p><label>Myth/Fact Text:<br><textarea name="myth_buster_options[belief_sets][${setIndex}][myths][${mythIndex}][myth]"></textarea></label></p>
            <p><label><input type="checkbox" name="myth_buster_options[belief_sets][${setIndex}][myths][${mythIndex}][isFact]" value="true"> Is this a fact (true)?</label></p>
            <p><label>Timer (sec): <input type="number" name="myth_buster_options[belief_sets][${setIndex}][myths][${mythIndex}][duration]" value="5"></label></p>
            <p><label>Explanation:<br><textarea name="myth_buster_options[belief_sets][${setIndex}][myths][${mythIndex}][explanation]"></textarea></label></p>
            <p><label>Background Image:<br><input type="text" class="image-url" name="myth_buster_options[belief_sets][${setIndex}][myths][${mythIndex}][image]" value=""><button type="button" class="button upload-image">Upload</button></label></p>
            <button type="button" class="button remove-myth">Remove Myth</button>
        </div>`;
        setContainer.find('.myths-container').append(newMyth);
        updateFieldNames();
    });

    // Remove myth
    $('#belief-sets-container').on('click', '.remove-myth', function() {
        $(this).closest('.myth-item').remove();
        updateFieldNames();
    });

    // Media Uploader
    $('#belief-sets-container').on('click', '.upload-image', function(e) {
        e.preventDefault();
        const button = $(this);
        const frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            button.prev('.image-url').val(attachment.url);
        }).open();
    });

    function updateFieldNames() {
        $('#belief-sets-container .myth-set').each(function(setIdx) {
            $(this).attr('data-set-index', setIdx);
            $(this).find('[name]').each(function() {
                const name = $(this).attr('name');
                const newName = name.replace(/\[belief_sets\]\[\d+\]/, `[belief_sets][${setIdx}]`);
                $(this).attr('name', newName);
            });
            $(this).find('.myth-item').each(function(mythIdx) {
                 $(this).find('[name]').each(function() {
                    const name = $(this).attr('name');
                    const newName = name.replace(/\[myths\]\[\d+\]/, `[myths][${mythIdx}]`);
                    $(this).attr('name', newName);
                });
            });
        });
    }
});
