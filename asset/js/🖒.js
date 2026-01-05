(function($) {
    'use strict';

    /**
     * Initialize like/dislike functionality.
     */
    function initLike() {
        $(document).on('click', '.like-button', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $container = $button.closest('.like-container');

            // Check if login is required.
            if ($button.data('require-login')) {
                var loginMessage = $container.data('message-login') || Omeka.jsTranslate('You must be logged in to 🖒 resources.');
                CommonDialog.dialogAlert(loginMessage);
                return;
            }

            // Check if vote is locked (user cannot change vote).
            if ($container.data('vote-locked') === true || $container.data('vote-locked') === 'true') {
                var lockedMessage = $container.data('message-locked') || 'You cannot change your vote.';
                CommonDialog.dialogAlert(lockedMessage);
                return;
            }

            // Prevent double-clicks.
            if ($container.hasClass('loading')) {
                return;
            }

            var resourceId = $container.data('resource-id');
            var toggleUrl = $container.data('url-toggle');
            var liked = $button.data('liked');

            // If already active, send null to remove the vote.
            if ($button.hasClass('active')) {
                liked = 'null';
            }

            $container.addClass('loading');

            $.ajax({
                url: toggleUrl,
                method: 'POST',
                data: {
                    resource_id: resourceId,
                    liked: liked
                },
                dataType: 'json'
            })
            .done(function(response) {
                if (response.status === 'success') {
                    updateLikeUI($container, response.data);
                } else if (response.status === 'fail') {
                    if (response.data && response.data.requireLogin) {
                        var loginMessage = $container.data('message-login') || Omeka.jsTranslate('You must be logged in to 🖒 resources.');
                        CommonDialog.dialogAlert(response.message || loginMessage);
                    } else if (response.data && response.data.action === 'denied') {
                        // Vote change was denied - lock the container.
                        $container.data('vote-locked', true);
                        $container.addClass('vote-locked');
                        $container.find('.like-button').prop('disabled', true);
                        var lockedMessage = $container.data('message-locked') || 'You cannot change your vote.';
                        CommonDialog.dialogAlert(response.message || lockedMessage);
                    } else {
                        CommonDialog.dialogAlert(response.message || Omeka.jsTranslate('An error occurred.'));
                    }
                } else {
                    CommonDialog.dialogAlert(response.message || Omeka.jsTranslate('An error occurred.'));
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Like toggle failed:', error);
                CommonDialog.dialogAlert(Omeka.jsTranslate('An error occurred while processing your request.'));
            })
            .always(function() {
                $container.removeClass('loading');
            });
        });
    }

    /**
     * Update the UI after a successful like/dislike action.
     */
    function updateLikeUI($container, data) {
        var $likeBtn = $container.find('.like-action');
        var $dislikeBtn = $container.find('.dislike-action');

        // Remove active state from all buttons.
        $likeBtn.removeClass('active');
        $dislikeBtn.removeClass('active');

        // Set active state based on response.
        if (data.liked === true) {
            $likeBtn.addClass('active');
        } else if (data.liked === false) {
            $dislikeBtn.addClass('active');
        }

        // Update counts.
        $container.find('.like-count').text(data.likes);
        $container.find('.dislike-count').text(data.dislikes);
    }

    /**
     * Refresh like status for all containers on the page.
     * Useful after AJAX content loads.
     */
    function refreshLikeStatus() {
        $('.like-container').each(function() {
            var $container = $(this);
            var resourceId = $container.data('resource-id');
            var toggleUrl = $container.data('url-toggle');

            if (!resourceId || !toggleUrl) {
                return;
            }

            // Use status endpoint instead of toggle.
            var statusUrl = toggleUrl.replace('/toggle', '/status');

            $.ajax({
                url: statusUrl,
                method: 'GET',
                data: { resource_id: resourceId },
                dataType: 'json'
            })
            .done(function(response) {
                if (response.status === 'success') {
                    updateLikeUI($container, response.data);

                    // Update login requirement state.
                    if (!response.data.isLoggedIn) {
                        $container.find('.like-button').attr('data-require-login', 'true');
                    } else {
                        $container.find('.like-button').removeAttr('data-require-login');
                    }
                }
            });
        });
    }

    // Initialize on document ready.
    $(document).ready(function() {
        initLike();
    });

    // Expose refresh function globally.
    // FIXME Use of emoji "window.OmekaModule🖒" does not work in js.
    window.OmekaModuleLike = {
        refresh: refreshLikeStatus,
        updateUI: updateLikeUI
    };

})(jQuery);
