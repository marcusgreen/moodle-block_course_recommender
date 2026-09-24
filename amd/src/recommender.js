/**
 * Course recommender AMD module.
 *
 * @module     block_course_recommender/recommender
 * @copyright  2025 Sadik Mert
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/modal'],
    function($, Ajax, Notification, Str, Modal) {

    /**
     * Initialize the module.
     */
    function init() {
        var form = $('#courserecommender-form');
        var resultsContainer = $('.courserecommender-results');
        var expandButton = $('.courserecommender-expand');

        if (!form.length || !resultsContainer.length) {
            return;
        }

        expandButton.on('click', function() {
            showExpandedResults(form);
        });

        // Tag-Limitierung.
        var tagsContainer = form.find('.courserecommender-tags-container');
        var badges = tagsContainer.find('.courserecommender-badge');
        var maxtags = parseInt(tagsContainer.data('maxtags'), 10);
        var searchInput = form.find('.courserecommender-tag-search');
        var searchLimit = maxtags > 0 ? maxtags : 20;

        if (maxtags > 0 && badges.length > maxtags) {
            badges.each(function(idx) {
                if (idx >= maxtags) {
                    $(this).hide();
                }
            });
        }

        searchInput.on('input', function() {
            filterTags($(this).val(), badges, maxtags, searchLimit);
        });

        // Badge-Auswahl-Logik
        form.on('click', '.courserecommender-badge', function(e) {
            e.preventDefault();
            $(this).toggleClass('selected');
            updateSelectedTags(form);
            clearTimeout(window.changeTimeout);
            window.changeTimeout = setTimeout(function() {
                form.submit();
            }, 300);
        });

        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            updateResults(form, resultsContainer);
        });

        updateResults(form, resultsContainer);
    }

    /**
     * Filter displayed tags by the search query.
     *
     * @param {String} query The search query
     * @param {jQuery} badges The tag badges
     * @param {Number} maxtags Initial number of popular tags to show
     * @param {Number} searchLimit Maximum matching tags shown while searching
     */
    function filterTags(query, badges, maxtags, searchLimit) {
        var normalizedQuery = $.trim(query).toLowerCase();
        var visibleMatches = 0;

        if (!normalizedQuery) {
            badges.each(function(idx) {
                $(this).toggle(maxtags <= 0 || idx < maxtags || $(this).hasClass('selected'));
            });
            return;
        }

        badges.each(function() {
            var badge = $(this);
            var tag = String(badge.data('tag') || '').toLowerCase();
            var matches = tag.indexOf(normalizedQuery) !== -1;
            var shouldShow = badge.hasClass('selected') || (matches && visibleMatches < searchLimit);

            badge.toggle(shouldShow);
            if (matches && visibleMatches < searchLimit) {
                visibleMatches++;
            }
        });
    }

    /**
     * Update the hidden input with selected tags.
     * @param {jQuery} form The form element
     */
    function updateSelectedTags(form) {
        var selected = [];
        form.find('.courserecommender-badge.selected').each(function() {
            selected.push($(this).data('tag'));
        });
        form.find('#courserecommender-selected-tags').val(selected.join(','));
    }

    /**
     * Read the currently selected interest tags from the form.
     *
     * @param {jQuery} form The form element
     * @return {Array} Selected tag names
     */
    function getSelectedInterests(form) {
        var interests = form.find('#courserecommender-selected-tags').val();
        return interests ? interests.split(',') : [];
    }

    /**
     * Fetch rendered course results HTML for the given interests.
     *
     * @param {Array} interests Selected tag names
     * @return {Promise}
     */
    function fetchResultsHtml(interests) {
        var request = {
            methodname: 'block_course_recommender_get_courses',
            args: {
                interests: interests,
                sesskey: M.cfg.sesskey
            }
        };
        return Ajax.call([request])[0];
    }

    /**
     * Update results using AJAX.
     *
     * @param {jQuery} form The form element
     * @param {jQuery} resultsContainer The results container element
     */
    function updateResults(form, resultsContainer) {
        // Show loading indicator
        resultsContainer.html('<div class="text-center"><span class="spinner-border"></span></div>');

        fetchResultsHtml(getSelectedInterests(form))
            .done(function(response) {
                resultsContainer.fadeOut(200, function() {
                    $(this).html(response.html).fadeIn(200);
                });
            })
            .fail(function(error) {
                Notification.exception(error);
                handleAjaxError(resultsContainer);
            });
    }

    /**
     * Open the current results in a large modal for a wider view.
     *
     * @param {jQuery} form The form element
     */
    function showExpandedResults(form) {
        var interests = getSelectedInterests(form);

        Str.get_string('expandresults', 'block_course_recommender')
            .then(function(title) {
                return Modal.create({
                    title: title,
                    body: '<div class="text-center"><span class="spinner-border"></span></div>',
                    large: true,
                    show: true,
                    removeOnClose: true
                });
            })
            .then(function(modal) {
                modal.getRoot().addClass('courserecommender-modal');
                fetchResultsHtml(interests)
                    .done(function(response) {
                        modal.setBody(response.html);
                    })
                    .fail(function(error) {
                        Notification.exception(error);
                    });
                return modal;
            })
            .catch(Notification.exception);
    }

    /**
     * Handles AJAX error and displays a localized error message.
     * @param {jQuery} resultsContainer The results container element
     */
    function handleAjaxError(resultsContainer) {
        Str.get_string('error', 'block_course_recommender')
            .then(function(errorStr) {
                resultsContainer.html('<div class="alert alert-danger">' + errorStr + '</div>');
                return null;
            })
            .catch(function() {
                resultsContainer.html('<div class="alert alert-danger">Error</div>');
                return null;
            });
    }

    return {
        init: init
    };
});
