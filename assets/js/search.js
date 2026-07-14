/**
 * WooPaca – search in the header. Clicking the search icon shows the
 * text field, and while typing, matching products (with image) are
 * fetched via AJAX against WordPress (admin-ajax.php), see
 * woopaca_ajax_product_search() in inc/woocommerce.php.
 */
jQuery(function ($) {
    var $toggle  = $('.header-search-toggle');
    var $panel   = $('.header-search-panel');
    var $input   = $('.header-search-input');
    var $results = $('.header-search-results');
    var $close   = $('.header-search-close');

    if (!$toggle.length || typeof woopacaSearch === 'undefined') {
        return;
    }

    var searchTimer = null;
    var currentRequest = null;

    function openSearch() {
        $panel.prop('hidden', false);
        $toggle.attr('aria-expanded', 'true');
        window.setTimeout(function () {
            $input.trigger('focus');
        }, 10);
    }

    function closeSearch() {
        $panel.prop('hidden', true);
        $results.prop('hidden', true).empty();
        $toggle.attr('aria-expanded', 'false');
    }

    $toggle.on('click', function () {
        if ($panel.prop('hidden')) {
            openSearch();
        } else {
            closeSearch();
        }
    });

    $close.on('click', function () {
        closeSearch();
        $toggle.trigger('focus');
    });

    // Close if clicking outside the search box.
    $(document).on('click', function (e) {
        if (!$panel.prop('hidden') && !$(e.target).closest('.header-search').length) {
            closeSearch();
        }
    });

    // Close with Escape.
    $(document).on('keydown', function (e) {
        if ('Escape' === e.key && !$panel.prop('hidden')) {
            closeSearch();
        }
    });

    function renderResults(items) {
        $results.empty();

        if (!items.length) {
            $results.append('<p class="header-search-empty">Inga produkter hittades.</p>');
            $results.prop('hidden', false);
            return;
        }

        var $list = $('<ul class="header-search-list"></ul>');

        items.forEach(function (item) {
            var $link = $('<a class="header-search-item"></a>').attr('href', item.url);
            var $thumb = $('<span class="header-search-thumb"></span>').html(item.thumbnail);
            var $info = $('<span class="header-search-info"></span>');

            $info.append($('<span class="header-search-title"></span>').text(item.title));
            $info.append($('<span class="header-search-price"></span>').html(item.price));

            $link.append($thumb).append($info);
            $list.append($('<li></li>').append($link));
        });

        $results.append($list);
        $results.prop('hidden', false);
    }

    $input.on('input', function () {
        var term = $(this).val().trim();

        window.clearTimeout(searchTimer);

        if (term.length < 2) {
            $results.prop('hidden', true).empty();
            return;
        }

        searchTimer = window.setTimeout(function () {
            if (currentRequest) {
                currentRequest.abort();
            }

            $results.addClass('is-loading');

            currentRequest = $.ajax({
                url: woopacaSearch.ajaxUrl,
                data: {
                    action: 'woopaca_product_search',
                    nonce: woopacaSearch.nonce,
                    term: term
                },
                dataType: 'json'
            }).done(function (response) {
                if (response && response.success) {
                    renderResults(response.data);
                }
            }).always(function () {
                $results.removeClass('is-loading');
            });
        }, 300);
    });
});
