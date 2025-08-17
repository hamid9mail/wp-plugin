jQuery(document).ready(function($) {
    let searchTimeout;
    let currentUserId = null; // Variable to store the currently selected user's ID

    const searchInput = $('#psych-user-search-input');
    const searchResults = $('#psych-user-search-results');
    const dataContainer = $('#psych-user-data-container');

    // --- User Search ---
    searchInput.on('keyup', function() {
        const searchTerm = $(this).val();
        clearTimeout(searchTimeout);

        if (searchTerm.length < 3) {
            searchResults.empty().hide();
            return;
        }

        searchTimeout = setTimeout(function() {
            searchResults.html('<span class="spinner is-active" style="float:none;"></span>').show();
            $.post(psychAdminDashboard.ajax_url, {
                action: 'psych_search_users',
                nonce: psychAdminDashboard.nonce,
                search_term: searchTerm
            }, function(response) {
                searchResults.empty();
                if (response.success && response.data.length) {
                    const userList = $('<ul>').addClass('psych-user-list');
                    response.data.forEach(function(user) {
                        const listItem = $('<li>')
                            .addClass('psych-user-list-item')
                            .text(`${user.display_name} (${user.email})`)
                            .data('user-id', user.id)
                            .on('click', function() {
                                selectUser($(this).data('user-id'));
                            });
                        userList.append(listItem);
                    });
                    searchResults.append(userList);
                } else {
                    searchResults.html('<p>هیچ کاربری یافت نشد.</p>');
                }
            });
        }, 500);
    });

    function selectUser(userId) {
        currentUserId = userId;

        searchInput.val('');
        searchResults.empty().hide();

        dataContainer.show();
        $('#psych-selected-user-name').text('در حال بارگذاری اطلاعات کاربر...');
        $('.psych-tab-content').html('<span class="spinner is-active"></span>');

        $.post(psychAdminDashboard.ajax_url, {
            action: 'psych_get_user_dashboard_data',
            nonce: psychAdminDashboard.nonce,
            user_id: userId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                $('#psych-selected-user-name').text(data.userName);

                // Populate tabs
                $('#tab-path-progress').html(data.tabs.path_progress);
                $('#tab-gamification').html(data.tabs.gamification);
                $('#tab-details').html(data.tabs.details);

                // Set the first tab as active
                $('.nav-tab').removeClass('nav-tab-active').first().addClass('nav-tab-active');
                $('.psych-tab-content').removeClass('active').first().addClass('active');

            } else {
                $('#psych-selected-user-name').text('خطا');
                dataContainer.find('.psych-tab-content').html('<p>خطا در بارگذاری اطلاعات: ' + response.data.message + '</p>');
            }
        });
    }

    // --- Tab Switching ---
    dataContainer.on('click', '.nav-tab', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.psych-tab-content').removeClass('active');
        const target = $(this).attr('href');
        $(target.replace('#', '#tab-')).addClass('active');
    });

    // --- Path Progress Actions ---
    dataContainer.on('click', '.psych-mark-incomplete', function() {
        const nodeId = $(this).data('node-id');
        if (currentUserId && confirm(`آیا مطمئن هستید که می‌خواهید ایستگاه ${nodeId} را برای این کاربر به حالت "تکمیل نشده" برگردانید؟`)) {
            updateStationStatus('psych_mark_station_incomplete', currentUserId, nodeId);
        }
    });

    dataContainer.on('click', '#psych-mark-complete-manual', function() {
        const nodeId = $('#psych-manual-node-id').val();
        if (nodeId && currentUserId) {
            updateStationStatus('psych_mark_station_complete', currentUserId, nodeId);
        } else {
            alert('لطفا شناسه ایستگاه را وارد کنید.');
        }
    });

    function updateStationStatus(action, userId, nodeId) {
        $('#tab-path-progress').html('<span class="spinner is-active"></span>');
        $.post(psychAdminDashboard.ajax_url, {
            action: action,
            nonce: psychAdminDashboard.nonce,
            user_id: userId,
            node_id: nodeId
        }, function(response) {
            if (response.success) {
                selectUser(userId); // Refresh data
            } else {
                alert('خطا: ' + (response.data ? response.data.message : 'Unknown error'));
                selectUser(userId);
            }
        });
    }

    // --- Gamification Actions ---
    dataContainer.on('click', '#psych-update-points-btn', function() {
        const points = parseInt($('#psych-points-change').val(), 10);
        const reason = $('#psych-points-reason').val();

        if (isNaN(points) || points === 0) {
            alert('لطفاً یک عدد معتبر برای امتیاز وارد کنید.');
            return;
        }

        if (currentUserId) {
            updateUserPoints(currentUserId, points, reason);
        }
    });

    dataContainer.on('click', '.psych-toggle-badge', function() {
        const badgeSlug = $(this).data('badge-slug');
        if (currentUserId) {
            toggleUserBadge(currentUserId, badgeSlug);
        }
    });

    function updateUserPoints(userId, points, reason) {
        $('#tab-gamification').html('<span class="spinner is-active"></span>');
        $.post(psychAdminDashboard.ajax_url, {
            action: 'psych_admin_update_points',
            nonce: psychAdminDashboard.nonce,
            user_id: userId,
            points: points,
            reason: reason
        }, function(response) {
            if (response.success) {
                selectUser(userId);
            } else {
                alert('خطا: ' + (response.data ? response.data.message : 'Unknown error'));
                selectUser(userId);
            }
        });
    }

    function toggleUserBadge(userId, badgeSlug) {
        $('#tab-gamification').html('<span class="spinner is-active"></span>');
        $.post(psychAdminDashboard.ajax_url, {
            action: 'psych_admin_toggle_badge',
            nonce: psychAdminDashboard.nonce,
            user_id: userId,
            badge_slug: badgeSlug
        }, function(response) {
            if (response.success) {
                selectUser(userId);
            } else {
                alert('خطا: ' + (response.data ? response.data.message : 'Unknown error'));
                selectUser(userId);
            }
        });
    }

    // --- User Details Actions ---
    dataContainer.on('click', '#psych-save-notebook-btn', function() {
        const button = $(this);
        const content = $('#psych-user-notebook-content').val();

        if (currentUserId) {
            button.text('در حال ذخیره...').prop('disabled', true);
            $.post(psychAdminDashboard.ajax_url, {
                action: 'psych_admin_save_note',
                nonce: psychAdminDashboard.nonce,
                user_id: currentUserId,
                content: content
            }, function(response) {
                button.text('ذخیره یادداشت').prop('disabled', false);
                if (!response.success) {
                    alert('خطا در ذخیره یادداشت.');
                }
            });
        }
    });

    // --- Initial Styling ---
    const styles = `
        .psych-admin-dashboard .psych-admin-card { margin-bottom: 20px; }
        #psych-user-search-results { border: 1px solid #ddd; background: #fff; margin-top: 5px; max-width: 400px; }
        .psych-user-list { margin: 0; padding: 0; list-style: none; }
        .psych-user-list-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
        .psych-user-list-item:last-child { border-bottom: none; }
        .psych-user-list-item:hover { background: #f0f0f0; }
        .psych-tab-content { display: none; padding: 20px; border: 1px solid #ccc; border-top: none; background: #fff; }
        .psych-tab-content.active { display: block; }
        .psych-station-list, .psych-admin-dashboard ul { list-style: disc; padding-right: 20px; }
        .psych-station-list li, .psych-admin-dashboard ul li { margin-bottom: 10px; }
        .nav-tab-wrapper { margin-bottom: 0 !important; }
        .psych-admin-form-inline, .psych-badge-item { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .psych-badges-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .psych-badge-item { background: #f9f9f9; padding: 10px; border-radius: 4px; border-right: 3px solid; }
    `;
    $('head').append('<style>' + styles + '</style>');
});
