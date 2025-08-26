jQuery(document).ready(function ($) {
    // --- State Management and Constants ---
    // Using a single object to manage state to avoid global variable conflicts.
    const state = {
        currentPage: 1,
        lastPage: 1,
        isLoadingNextPage: false,
        lastSearchParams: {}
    };

    // Constants for all user-facing messages.
    const MESSAGES = {
        SEARCHING: 'Se caută...',
        SEARCH_BUTTON: 'Caută camere disponibile',
        ADDING: 'Se adaugă...',
        ADDED: 'Adăugat!',
        ERROR_CART: 'Eroare la adăugarea în coș',
        NO_RESULTS: 'Nicio cameră disponibilă pentru datele și criteriile selectate.',
        CAPACITY_ERROR: 'Numărul total de adulți sau copii pentru camerele selectate depășește selecția dvs. de căutare. Vă rugăm să ajustați selecția.'
    };

    // --- Cached Selectors ---
    const $startDate = $('#start_date');
    const $endDate = $('#end_date');
    const $form = $('#hotel-availability-form');
    const $searchResults = $('#search-results');
    const $searchLoading = $('#search-loading');
    const $searchBtn = $('.search-btn');

    // --- Date Picker Logic ---
    // Set minimum date to today for both start and end dates.
    const today = new Date().toISOString().split('T')[0];
    $startDate.attr('min', today);
    $endDate.attr('min', today);

    // Update minimum end date when start date changes to ensure valid date ranges.
    $startDate.on('change', function () {
        const startDate = new Date($startDate.val());
        startDate.setDate(startDate.getDate() + 1);
        const minEndDate = startDate.toISOString().split('T')[0];
        $endDate.attr('min', minEndDate);

        // If the current end date is before the new minimum, update it.
        if ($endDate.val() && $endDate.val() <= $startDate.val()) {
            $endDate.val(minEndDate);
        }
    });

    // --- Room Variation Price Update ---
    // Event listener for when a different room variation is selected.
    $(document).on('change', '.room-variation-radio', function() {
        const $input = $(this);
        $input.closest('.room-info').find('.room-price').text(`Preț: ${$input.data('price')} lei`);
    });

    // --- Search Form Submission ---
    $form.on('submit', function (e) {
        e.preventDefault();

        // Gather form data for the AJAX request.
        const formData = {
            action: 'search_hotel_rooms',
            adults: parseInt($('#adults').val()),
            kids: parseInt($('#kids').val()),
            number_of_rooms: $('#number_of_rooms').val(),
            start_date: $startDate.val(),
            end_date: $endDate.val(),
            nonce: window.hotelRoomSearchVars.nonce
        };

        // Store search parameters for infinite scroll.
        state.lastSearchParams = formData;
        state.currentPage = 1; // Reset to page 1 for a new search.
        state.lastPage = 1; // Reset last page.

        // Show loading state.
        $searchLoading.show();
        $searchResults.empty();
        $searchBtn.prop('disabled', true).text(MESSAGES.SEARCHING);

        // Make the AJAX call to the server.
        $.post(window.hotelRoomSearchVars.ajax_url, formData, function (response) {
            // Hide loading state.
            $searchLoading.hide();
            $searchBtn.prop('disabled', false).text(MESSAGES.SEARCH_BUTTON);

            // Parse response if it's a string.
            const resp = (typeof response === 'string') ? JSON.parse(response) : response;

            if (resp.success) {
                // Update pagination state and display results.
                if (resp.data.pagination) {
                    state.lastPage = resp.data.pagination.last_page;
                    state.currentPage = resp.data.pagination.current_page;
                }
                displayResults(resp.data);
            } else {
                $searchResults.html(`<div class="error-message">${resp.data || 'Nu există date'}</div>`);
            }
        }).fail(function () {
            // Handle AJAX failure.
            $searchLoading.hide();
            $searchBtn.prop('disabled', false).text(MESSAGES.SEARCH_BUTTON);
            $searchResults.html('<div class="error-message">Căutarea a eșuat. Vă rugăm să încercați din nou.</div>');
        });
    });

    // --- Display Search Results ---
    function displayResults(data, append = false) {
        if (!data.combinations || Object.keys(data.combinations).length === 0) {
            $searchResults.html(`<div class="no-results">${MESSAGES.NO_RESULTS}</div>`);
            return;
        }

        let html = '';
        if (!append) {
            // Only add headers and filters on the first page load.
            html += '<h3>Combinații de camere disponibile</h3>';
            html += '<div class="select-holtes"><a href="#all" class="hotel-filter active" data-hotel="all">Toate Hotelurile</a><a href="#1" class="hotel-filter" data-hotel="1"> Hotel Noblesse</a><a href="#2" class="hotel-filter" data-hotel="2"> Hotel Royal</a></div>';
        }

        // Get search criteria from form for validation.
        const totalAdults = parseInt($('#adults').val()) || 0;
        const totalKids = parseInt($('#kids').val()) || 0;
        const totalRooms = parseInt($('#number_of_rooms').val()) || 1;
        const hideSingle = (totalAdults > 0 && totalRooms > 0 && totalAdults % totalRooms === 0);
        let x = 0; // Counter for radio button names.

        // Loop through the combinations and build the HTML.
        $.each(data.combinations, function (roomType, typeData) {
            if (!typeData.combo || typeData.combo.length === 0) {
                return; // Skip if no combinations for this room type.
            }

            const firstCombo = typeData.combo[0]; // Show only the first option.
            const hotels = String(typeData.hotels);
            let type;

            if (/^1+$/.test(hotels)) {
                type = 1;
            } else if (/^2+$/.test(hotels)) {
                type = 2;
            } else {
                type = 3; // For combinations like '12', '21', etc.
            }

            const productIds = [];
            let comboHtml = `<div class="combo-option" data-room-type="${type}">`;
            
            $.each(firstCombo, function (roomIndex, room) {
                const stars = (room.hotel == 1) ? 4 : 3;
                comboHtml += `
                    <div class="room-details">
                        <div class="room-info">
                            <div class="room-image"><img src="${room.product_image || ''}" alt="${room.typeName}"></div>
                            <div class="room-details-info">
                                <h1><a href="${room.product_url}">Camera: ${room.typeName} (${stars} stele)</a></h1>
                                <p>${room.description || ''}</p>
                `;

                // If room has variations, show radio buttons.
                if (room.variations && Array.isArray(room.variations) && room.variations.length > 0) {
                    comboHtml += `
                        <label>Alegeți o variantă:</label>
                        <div class="room-variation-radio-group" data-room-index="${roomIndex}"><ul>
                    `;
                    let firstVisible = true;
                    $.each(room.variations, function(vi, variation) {
                        const attrs = Object.values(variation.attributes).join(', ');
                        const isSingle = attrs.toLowerCase().includes('single') || (variation.title && variation.title.toLowerCase().includes('single'));
                        
                        if (hideSingle && isSingle) {
                            return; // Skip rendering 'single' variation if hideSingle is true.
                        }
                        
                        const checkedAttr = firstVisible ? 'checked="checked"' : '';
                        firstVisible = false;
                        
                        const adultMax = isSingle ? 1 : room.adultMax;
                        const childMax = isSingle ? 0 : room.kidMax;
                        
                        comboHtml += `
                            <li>
                                <label style="margin-right:10px;">
                                    <input type="radio" name="room-variation-${x}" class="room-variation-radio room-variation-select" 
                                    value="${variation.variation_id}" data-price="${variation.price}" data-image="${variation.image || ''}" 
                                    data-adultMax="${adultMax}" data-childMax="${childMax}" ${checkedAttr}>
                                    ${attrs} - ${variation.price} lei${variation.in_stock ? '' : ' (Stoc epuizat)'}
                                </label>
                            </li>
                        `;
                        x++;
                    });
                    comboHtml += '</ul></div>';
                    comboHtml += `<span class="room-price">Preț: ${room.variations[0].price} lei</span>`;
                } else if (room.product_price) {
                    comboHtml += `<span class="room-price">Preț: ${room.product_price} lei</span>`;
                }
                comboHtml += `
                            </div>
                        </div>
                    </div>
                `;
                if (room.product_id) {
                    productIds.push(room.product_id);
                }
            });

            if (productIds.length > 0) {
                comboHtml += `
                    <div class="room-actions" style="margin-top:10px;">
                        <button class="add-combo-to-cart-btn" data-product-ids="${productIds.join(',')}"
                        >Adaugă totul în coș</button>
                    </div>
                `;
            }
            comboHtml += '</div>';
            html += comboHtml;
        });

        if (append) {
            $searchResults.append(html);
        } else {
            $searchResults.html(html);
        }
    }

    // --- Hotel Filtering Logic ---
    $(document).on('click', '.hotel-filter', function(e) {
        e.preventDefault();
        
        $('.select-holtes a').removeClass('active');
        const selectedHotel = $(this).data('hotel');
        $(this).addClass('active');

        if (selectedHotel === 'all' || selectedHotel == 3) {
            $('.combo-option').show();
        } else {
            $('.combo-option').each(function() {
                const roomType = $(this).data('room-type');
                if (roomType == selectedHotel) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // --- Infinite Scroll Logic ---
    $(window).off('scroll.infinite').on('scroll.infinite', function () {
        // Use state object to manage the flow.
        if (state.isLoadingNextPage || state.currentPage >= state.lastPage) {
            return;
        }

        const scrollTop = $(window).scrollTop();
        const windowHeight = $(window).height();
        const docHeight = $(document).height();

        // Trigger when the user is near the bottom of the page.
        if (scrollTop + windowHeight >= docHeight - 200) {
            state.isLoadingNextPage = true;
            const nextPage = state.currentPage + 1;
            const params = Object.assign({}, state.lastSearchParams, { page: nextPage });
            $searchLoading.show();
            
            $.post(window.hotelRoomSearchVars.ajax_url, params, function (response) {
                $searchLoading.hide();
                const resp = (typeof response === 'string') ? JSON.parse(response) : response;
                
                if (resp.success) {
                    const pageData = resp.data || resp;
                    state.currentPage = (pageData.pagination && pageData.pagination.current_page) ? pageData.pagination.current_page : nextPage;
                    state.lastPage = (pageData.pagination && pageData.pagination.last_page) ? pageData.pagination.last_page : state.lastPage;
                    displayResults(pageData, true); // Append results to the list.
                }
                state.isLoadingNextPage = false;
            }).fail(function () {
                $searchLoading.hide();
                state.isLoadingNextPage = false;
            });
        }
    });

    // --- Add to Cart Logic ---
    $(document).on('click', '.add-combo-to-cart-btn', function (e) {
        e.preventDefault();
        const btn = $(this);
        const $comboOption = btn.closest('.combo-option, .single-combo-option');
        const productIds = btn.data('product-ids').toString().split(',');
        if (!productIds.length) return;

        const maxAdults = parseInt($('#adults').val()) || 0;
        const maxKids = parseInt($('#kids').val()) || 0;

        let totalAdults = 0;
        let totalKids = 0;
        $comboOption.find('.room-details').each(function(idx, el) {
            const $roomDetails = $(el);
            const $variationSelect = $roomDetails.find('.room-variation-radio:checked');
            const adultMax = $variationSelect.length ? parseInt($variationSelect.attr('data-adultMax')) || 0 : 0;
            const childMax = $variationSelect.length ? parseInt($variationSelect.attr('data-childMax')) || 0 : 0;
            totalAdults += adultMax;
            totalKids += childMax;
        });

        $comboOption.find('.combo-error-message').remove();
        if (totalAdults < maxAdults || totalKids < maxKids) {
            const errorMsg = $(`<div class="combo-error-message" style="color:red; margin-bottom:8px;">${MESSAGES.CAPACITY_ERROR}</div>`);
            btn.closest('.room-actions').before(errorMsg);
            return;
        }

        btn.prop('disabled', true).text(MESSAGES.ADDING);

        const items = [];
        $comboOption.find('.room-details').each(function(idx, el) {
            const $roomDetails = $(el);
            const $variationSelect = $roomDetails.find('.room-variation-radio:checked');
            const productId = productIds[idx];
            const variationId = $variationSelect.length ? $variationSelect.val() : null;
            items.push({
                product_id: productId,
                variation_id: variationId,
                quantity: 1
            });
        });

        const searchParams = {
            adults: maxAdults,
            kids: maxKids,
            number_of_rooms: $('#number_of_rooms').val(),
            start_date: $startDate.val(),
            end_date: $endDate.val()
        };
        
        $.ajax({
            url: window.hotelRoomSearchVars.ajax_url,
            method: 'POST',
            data: Object.assign({
                action: 'masterhotel_add_multiple_to_cart',
                items: JSON.stringify(items),
                nonce: window.hotelRoomSearchVars.nonce
            }, searchParams),
            success: function(res) {
                const isSuccess = res.success || (typeof res.added !== 'undefined' && res.added > 0);
                const cartUrl = (res.data && res.data.cart_url) ? res.data.cart_url : res.cart_url || '';
                
                if (isSuccess) {
                    btn.text(MESSAGES.ADDED).prop('disabled', false);
                    if (cartUrl) {
                        window.location.href = cartUrl;
                    }
                } else {
                    btn.text(MESSAGES.ERROR_CART).prop('disabled', false);
                }
            },
            error: function() {
                btn.text(MESSAGES.ERROR_CART).prop('disabled', false);
            }
        });
    });
});
