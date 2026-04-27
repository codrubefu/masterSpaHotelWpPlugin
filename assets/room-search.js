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
    const $resetBtn = $('.reset-btn');
    const $formFields = $form.find('input, select, button:not(.reset-btn)');
    let hasAutoSubmittedFromQuery = false;

        // Inject CSS for combo breakdown to match the radio area
        $('head').append(`<style>
        .combo-total-breakdown{
            border:1px solid #e6e6e6;
            padding:10px 12px;
            margin-bottom:8px;
            background:#fff;
            border-radius:4px;
            box-shadow:0 1px 0 rgba(0,0,0,0.02);
        }
        .combo-total-breakdown .combo-room-line{
            font-size:0.95em;
            padding:4px 0;
            justify-content:space-between;
            gap:12px;
            align-items:center;
        }
        .combo-total-breakdown .combo-room-line strong{font-weight:600}
        .combo-total-breakdown .combo-room-unitprice,
        .combo-total-breakdown .combo-room-subtotal{
            font-weight:600;
            color:#222;
            white-space:nowrap;
        }
        .combo-total-breakdown .combo-total-line{
            margin-top:8px;
            text-align:left;
        }
        @media (max-width:600px){
            .combo-total-breakdown .combo-room-line{flex-direction:column;align-items:flex-start}
            .combo-total-breakdown .combo-total-line{text-align:left}
        }
        </style>`);

    // --- Form State Management ---
    function disableForm() {
        $formFields.prop('disabled', true);
        $searchBtn.hide();
        $resetBtn.show();
    }

    function enableForm() {
        $formFields.prop('disabled', false);
        $searchBtn.show();
        $resetBtn.hide();
        $searchResults.empty();
        $searchLoading.hide();
        state.currentPage = 1;
        state.lastPage = 1;
        state.lastSearchParams = {};
        state.isLoadingNextPage = false;
    }

    // --- Reset Button Handler ---
    $resetBtn.on('click', function() {
        enableForm();
    });

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

    function applyQueryParamsToSearchForm() {
        const params = new URLSearchParams(window.location.search);
        const startDate = params.get('start_date');
        const endDate = params.get('end_date');
        const adults = params.get('adults');
        const kids = params.get('kids');
        const numberOfRooms = params.get('number_of_rooms');

        if (!startDate || !endDate || !adults || !numberOfRooms) {
            return false;
        }

        $startDate.val(startDate).trigger('change');
        $endDate.val(endDate);
        $('#adults').val(adults);

        if (kids !== null) {
            $('#kids').val(kids);
        }

        $('#number_of_rooms').val(numberOfRooms);

        return true;
    }

    function initReservationFilter() {
        const $reservationForm = $('.masterhotel-reservation-filter .cs-form-wrap');
        const labels = window.hotelRoomSearchVars.reservation_labels || {};

        if (!$reservationForm.length) {
            return;
        }

        function getLabel(labelKey, value) {
            const labelSet = labels[labelKey] || {};
            return value === 1 ? (labelSet.single || '') : (labelSet.plural || '');
        }

        function setHiddenValue($form, name, value) {
            let $input = $form.find(`input[type="hidden"][name="${name}"]`);

            if (!$input.length) {
                $input = $('<input>', { type: 'hidden', name }).appendTo($form);
            }

            $input.val(value);
        }

        function syncReservationSummary($form) {
            const roomCount = parseInt($form.find('input[name="room-quantity"]').val(), 10) || 1;
            const adultCount = parseInt($form.find('input[name="adult-quantity"]').val(), 10) || 1;
            const childCount = parseInt($form.find('input[name="child-quantity"]').val(), 10) || 0;
            const guestParts = [`${adultCount} ${getLabel('adult', adultCount)}`];

            if (childCount > 0) {
                guestParts.push(`${childCount} ${getLabel('child', childCount)}`);
            }

            $form.find('.cs-rooms .field-input-wrap input[name="rooms"]').val(`${roomCount} ${getLabel('room', roomCount)}`);
            $form.find('.cs-guests .field-input-wrap input[name="guests"]').val(guestParts.join(', '));

            setHiddenValue($form, 'room_quantity_label', `${roomCount} ${getLabel('room', roomCount)}`);
            setHiddenValue($form, 'adult_quantity_label', `${adultCount} ${getLabel('adult', adultCount)}`);
            setHiddenValue($form, 'child_quantity_label', `${childCount} ${getLabel('child', childCount)}`);
            setHiddenValue($form, 'adults', adultCount);
            setHiddenValue($form, 'children', childCount);
        }

        function updateQuantity($button, delta) {
            const $quantity = $button.closest('.cs-quantity');
            const $input = $quantity.find('input').first();
            const min = parseInt($input.data('min'), 10);
            const max = parseInt($input.data('max'), 10);
            const current = parseInt($input.val(), 10) || 0;
            let nextValue = current + delta;

            if (!Number.isNaN(min)) {
                nextValue = Math.max(min, nextValue);
            }

            if (!Number.isNaN(max)) {
                nextValue = Math.min(max, nextValue);
            }

            $input.val(nextValue);
            syncReservationSummary($button.closest('form'));
        }

        syncReservationSummary($reservationForm);

        $(document).on('click', function (event) {
            if (!$(event.target).closest('.masterhotel-reservation-filter').length) {
                $('.masterhotel-reservation-filter .csf-dropdown').removeClass('is-open');
            }
        });

        $reservationForm
            .on('click', '.has-dropdown', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const $dropdown = $(this).siblings('.csf-dropdown');
                $('.masterhotel-reservation-filter .csf-dropdown').not($dropdown).removeClass('is-open');
                $dropdown.toggleClass('is-open');
            })
            .on('click', '.minus', function (event) {
                event.preventDefault();
                updateQuantity($(this), -1);
            })
            .on('click', '.plus', function (event) {
                event.preventDefault();
                updateQuantity($(this), 1);
            })
            .on('submit', function () {
                syncReservationSummary($(this));
            });

        if ($.fn.daterangepicker) {
            const dateFormat = $reservationForm.data('date-format') || 'YYYY-MM-DD';
            const $checkinDate = $reservationForm.find('.checkin-date input[name="checkin"]');
            const $checkoutDate = $reservationForm.find('.checkout-date input[name="checkout"]');
            const $dateRangePicker = $reservationForm.find('.date-range-picker');

            if ($dateRangePicker.length && $checkinDate.length && $checkoutDate.length) {
                $dateRangePicker.daterangepicker({
                    minDate: moment().format(dateFormat),
                    startDate: $checkinDate.val(),
                    endDate: $checkoutDate.val(),
                    locale: { format: dateFormat },
                    autoApply: true,
                }).on('apply.daterangepicker', function (event, picker) {
                    const startDate = picker.startDate.format(dateFormat);
                    const endDate = picker.endDate.format(dateFormat);

                    $(this).val(`${startDate} - ${endDate}`);
                    $checkinDate.val(startDate);
                    $checkoutDate.val(endDate);
                });

                $reservationForm.find('.checkin-date, .checkout-date').on('click', function (event) {
                    event.preventDefault();
                    const picker = $dateRangePicker.data('daterangepicker');

                    if (!picker) {
                        return;
                    }

                    picker.setStartDate($checkinDate.val());
                    picker.setEndDate($checkoutDate.val());
                    picker.show();
                });
            }
        }
    }

    initReservationFilter();

    function getCurrentNights() {
        let nights = 1;
        try {
            const sd = new Date($startDate.val());
            const ed = new Date($endDate.val());
            if ($startDate.val() && $endDate.val()) {
                const diff = Math.ceil((ed - sd) / (1000 * 60 * 60 * 24));
                nights = diff > 0 ? diff : 1;
            }
        } catch (e) { nights = 1; }
        return nights;
    }

    function parseMonthDayToDate(md, baseYear) {
        if (!md || typeof md !== 'string') return null;
        const parts = md.split('-');
        if (parts.length !== 2) return null;
        const month = parseInt(parts[0], 10);
        const day = parseInt(parts[1], 10);
        if (!month || !day) return null;
        return new Date(baseYear, month - 1, day);
    }

    function getVariationPlanForStay(variations, bookingStart, bookingEnd) {
        const totalNights = Math.max(1, Math.ceil((bookingEnd - bookingStart) / (1000 * 60 * 60 * 24)));
        const nightlyPlan = [];
        const currentYear = (new Date()).getFullYear();

        const normalized = (variations || []).map(v => {
            const start = parseMonthDayToDate(v.data_start, currentYear);
            let end = parseMonthDayToDate(v.data_end, currentYear);
            if (start && end && end <= start) {
                end = new Date(currentYear + 1, end.getMonth(), end.getDate());
            }
            return Object.assign({}, v, { _start: start, _end: end, _price: parseFloat(v.price) || 0 });
        }).sort((a, b) => {
            if (!a._start && !b._start) return a._price - b._price;
            if (!a._start) return 1;
            if (!b._start) return -1;
            return a._start - b._start;
        });

        const fallback = normalized.length ? normalized.slice().sort((a, b) => a._price - b._price)[0] : null;
        for (let i = 0; i < totalNights; i++) {
            const currentDate = new Date(bookingStart.getTime() + (i * 24 * 60 * 60 * 1000));
            const candidates = normalized.filter(v => v._start && v._end && currentDate >= v._start && currentDate < v._end);
            const selected = candidates.length ? candidates.slice().sort((a, b) => a._price - b._price)[0] : fallback;
            if (selected) {
                nightlyPlan.push(selected);
            }
        }

        const pricedSegments = [];
        let totalPrice = 0;
        nightlyPlan.forEach((variation) => {
            totalPrice += variation._price;
            const last = pricedSegments.length ? pricedSegments[pricedSegments.length - 1] : null;
            if (last && last.variation_id === variation.variation_id) {
                last.nights += 1;
            } else {
                pricedSegments.push({ variation_id: variation.variation_id, nights: 1, price: variation._price });
            }
        });

        const primaryVariationId = pricedSegments.length ? pricedSegments[0].variation_id : null;
        return { totalPrice, segments: pricedSegments, totalNights, primaryVariationId };
    }

    // --- Room Variation Price Update ---
    // Event listener for when a different room variation is selected.
    $(document).on('change', '.room-variation-radio', function() {
        const $input = $(this);
        $input.closest('.room-info').find('.room-price').text(`Preț/noapte: ${$input.data('price')} lei`);
        recalcAllComboBreakdowns($input.closest('.combo-option'));
    });

    // Recalculate breakdowns by reading unit price from rendered `.room-price` elements
    function recalcAllComboBreakdowns(root) {
        root = root || document;
        $(root).find('.combo-option').each(function(){
            const $combo = $(this);
            const nights = getCurrentNights();
            const bookingStart = new Date($startDate.val());
            const bookingEnd = new Date($endDate.val());
            let totalAll = 0;
            $combo.find('.room-details').each(function(idx, el){
                const $room = $(el);
                let nightly = 0;
                let roomTotal = 0;
                let segments = [];
                const encodedVariations = $room.attr('data-room-variations');
                const hasDates = $startDate.val() && $endDate.val() && !isNaN(bookingStart.getTime()) && !isNaN(bookingEnd.getTime());

                if (encodedVariations && hasDates) {
                    try {
                        const variations = JSON.parse(decodeURIComponent(encodedVariations));
                        const plan = getVariationPlanForStay(variations, bookingStart, bookingEnd);
                        roomTotal = plan.totalPrice;
                        nightly = nights > 0 ? (roomTotal / nights) : 0;
                        segments = plan.segments;
                        $room.attr('data-selected-plan', encodeURIComponent(JSON.stringify(segments)));
                        const segmentNightsMap = {};
                        segments.forEach((segment) => {
                            const key = String(segment.variation_id);
                            segmentNightsMap[key] = (segmentNightsMap[key] || 0) + (parseInt(segment.nights, 10) || 0);
                        });
                        $room.find('.room-variation-radio').each(function() {
                            const $option = $(this);
                            const variationId = String($option.val());
                            const selectedNights = segmentNightsMap[variationId] || 0;
                            $option.prop('checked', selectedNights > 0);
                            $option.attr('data-plan-nights', selectedNights);
                        });
                        if (!segments.length && plan.primaryVariationId) {
                            $room.find('.room-variation-radio[value="' + plan.primaryVariationId + '"]').prop('checked', true);
                        }
                    } catch (e) {
                        segments = [];
                    }
                }

                if (!roomTotal) {
                    const $selected = $room.find('.room-variation-radio:checked');
                    if ($selected.length) {
                        nightly = 0;
                        $selected.each(function() {
                            nightly += parseFloat($(this).data('price')) || 0;
                        });
                        nightly = nightly / $selected.length;
                    }
                    roomTotal = nightly * nights;
                    if ($selected.length) {
                        const splitNights = Math.max(1, Math.floor(nights / $selected.length));
                        let allocated = 0;
                        $selected.each(function(selIdx) {
                            const $option = $(this);
                            const optionNights = (selIdx === $selected.length - 1) ? (nights - allocated) : splitNights;
                            allocated += optionNights;
                            segments.push({
                                variation_id: parseInt($option.val(), 10),
                                nights: optionNights,
                                price: parseFloat($option.data('price')) || 0
                            });
                            $option.attr('data-plan-nights', optionNights);
                        });
                        $room.attr('data-selected-plan', encodeURIComponent(JSON.stringify(segments)));
                    }
                }

                totalAll += roomTotal;
                $room.find('.room-price').text(`Preț total sejur: ${roomTotal.toFixed(2)} lei`);
                $combo.find('.combo-room-unitprice[data-room-index="' + idx + '"]').text(nightly.toFixed(2));
                $combo.find('.combo-room-subtotal[data-room-index="' + idx + '"]').text(roomTotal.toFixed(2));
            });
            $combo.find('.combo-total-price').text(totalAll.toFixed(2));
        });
    }

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
                // Disable form and show reset button after successful search
                disableForm();
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

    if ($form.length && applyQueryParamsToSearchForm() && !hasAutoSubmittedFromQuery) {
        hasAutoSubmittedFromQuery = true;
        $form.trigger('submit');
    }

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
        const bookingStart = new Date($startDate.val());
        const bookingEnd = new Date($endDate.val());
        const hasBookingDates = $startDate.val() && $endDate.val() && !isNaN(bookingStart.getTime()) && !isNaN(bookingEnd.getTime());
        let comboCounter = 0;

        // Loop through the combinations and build the HTML.
        $.each(data.combinations, function (roomType, typeData) {
            if (!typeData.combo || typeData.combo.length === 0) {
                return; // Skip if no combinations for this room type.
            }

            const firstCombo = typeData.combo[0]; // Show only the first option.
            const comboId = comboCounter++;
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
                const productPrices = []; // collect price for each room in the combo
                const productNames = []; // collect room names for breakdown
                let comboHtml = `<div class="combo-option" data-room-type="${type}">`;
            
            $.each(firstCombo, function (roomIndex, room) {
                const stars = (room.hotel == 1) ? 4 : 3;
                let sortedVariations = [];
                let visibleVariations = [];
                if (room.variations && Array.isArray(room.variations) && room.variations.length > 0) {
                    sortedVariations = room.variations.slice().sort((a, b) => {
                        const priceA = parseFloat(a.price) || 0;
                        const priceB = parseFloat(b.price) || 0;
                        return priceB - priceA;
                    });
                    visibleVariations = sortedVariations.filter((variation) => {
                        const attrs = Object.values(variation.attributes || {}).join(', ');
                        const isSingle = attrs.toLowerCase().includes('single') || (variation.title && variation.title.toLowerCase().includes('single'));
                        return !(hideSingle && isSingle);
                    });
                }

                const roomDetailsAttr = visibleVariations.length
                    ? ` data-room-variations="${encodeURIComponent(JSON.stringify(visibleVariations))}"`
                    : '';
                let initialPlan = null;
                if (visibleVariations.length && hasBookingDates) {
                    initialPlan = getVariationPlanForStay(visibleVariations, bookingStart, bookingEnd);
                }
                const targetPrimaryVariationId = (initialPlan && initialPlan.primaryVariationId) ? parseInt(initialPlan.primaryVariationId, 10) : null;
                const nightsByVariationId = {};
                if (initialPlan && Array.isArray(initialPlan.segments)) {
                    initialPlan.segments.forEach((segment) => {
                        const key = String(segment.variation_id);
                        nightsByVariationId[key] = (nightsByVariationId[key] || 0) + (parseInt(segment.nights, 10) || 0);
                    });
                }
                const initialSelectedPlanAttr = (initialPlan && initialPlan.segments && initialPlan.segments.length)
                    ? ` data-selected-plan="${encodeURIComponent(JSON.stringify(initialPlan.segments))}"`
                    : '';
                comboHtml += `
                    <div class="room-details"${roomDetailsAttr}${initialSelectedPlanAttr}>
                        <div class="room-info">
                            <div class="room-image"><img src="${room.product_image || ''}" alt="${room.typeName}"></div>
                            <div class="room-details-info">
                                <h1><a href="${room.product_url}">Camera: ${room.typeName} (${stars} stele)</a></h1>
                                <p>${room.description || ''}</p>
                `;

                // If room has variations, show radio buttons.
                if (visibleVariations.length > 0) {
                    comboHtml += `
                        <label>Alegeți o variantă:</label>
                        <div class="room-variation-radio-group" data-room-index="${roomIndex}"><ul>
                    `;
                    let firstVisible = true;

                    $.each(visibleVariations, function(vi, variation) {
                        const attrs = Object.values(variation.attributes).join(', ');
                        const isSingle = attrs.toLowerCase().includes('single') || (variation.title && variation.title.toLowerCase().includes('single'));
                        const selectedNightsForVariation = nightsByVariationId[String(variation.variation_id)] || 0;
                        const shouldCheckByPlan = selectedNightsForVariation > 0;
                        const checkedAttr = (shouldCheckByPlan || (targetPrimaryVariationId === null && firstVisible)) ? 'checked="checked"' : '';
                        if (checkedAttr && targetPrimaryVariationId === null) {
                            firstVisible = false;
                        }
                        
                        const adultMax = isSingle ? 1 : room.adultMax;
                        const childMax = isSingle ? 0 : room.kidMax;
                        
                        comboHtml += `
                            <li>
                                <label style="margin-right:10px;">
                                    <input type="checkbox" name="room-variation-${comboId}-${roomIndex}[]" class="room-variation-radio room-variation-select" 
                                    value="${variation.variation_id}" data-price="${variation.price}" data-image="${variation.image || ''}" 
                                    data-adultMax="${adultMax}" data-childMax="${childMax}" data-plan-nights="${selectedNightsForVariation}" ${checkedAttr}>
                                    ${attrs} - ${variation.price} lei${variation.in_stock ? '' : ' (Stoc epuizat)'}${selectedNightsForVariation > 0 ? ` <strong>(selectată ${selectedNightsForVariation} nopți)</strong>` : ''}
                                </label>
                                ${variation.description ? `<div class="room-variation-description">${variation.description}</div>` : ''}
                            </li>
                        `;
                    });
                    comboHtml += '</ul></div>';
                    comboHtml += `<span class="room-price">Preț/noapte: ${visibleVariations[0].price} lei</span>`;
                } else if (room.product_price) {
                    comboHtml += `<span class="room-price">Preț/noapte: ${room.product_price} lei</span>`;
                }
                comboHtml += `
                            </div>
                        </div>
                    </div>
                `;
                if (room.product_id) {
                    productIds.push(room.product_id);
                }
                // collect display name for this room (from typeName)
                productNames.push(room.typeName || ('Camera ' + (roomIndex+1)));

                // determine a representative price for this room (use first variation price or product_price)
                var priceVal = 0;
                if (room.variations && Array.isArray(room.variations) && room.variations.length > 0) {
                    priceVal = parseFloat(room.variations[0].price) || 0;
                } else if (room.product_price) {
                    priceVal = parseFloat(room.product_price) || 0;
                }
                productPrices.push(priceVal);
            });

            // calculate nights from selected dates
            var nights = 1;
            try {
                var sd = new Date($startDate.val());
                var ed = new Date($endDate.val());
                if ($startDate.val() && $endDate.val()) {
                    var diff = Math.ceil((ed - sd) / (1000 * 60 * 60 * 24));
                    nights = diff > 0 ? diff : 1;
                }
            } catch (e) {
                nights = 1;
            }

            // build per-room breakdown and total (nights * price per room)
                if (productPrices.length > 0) {
                var breakdownHtml = '<div class="combo-total-breakdown">';
                var totalAll = 0;
                for (var pi = 0; pi < productPrices.length; pi++) {
                    var p = Number(productPrices[pi]) || 0;
                    var name = productNames[pi] || ('Camera ' + (pi+1));
                    var subtotal = p * nights;
                    totalAll += subtotal;
                    breakdownHtml += '<div class="combo-room-line">' + '<span class="combo-room-name">' + name + ': ' + nights + ' nopti × </span>' + '<span class="combo-room-unitprice" data-room-index="' + pi + '">' + p + '</span>' + ' lei = ' + '<span class="combo-room-subtotal" data-room-index="' + pi + '">' + subtotal + '</span>' + ' lei</div>';
                }
                breakdownHtml += '<div class="combo-total-line"><strong>Total: <span class="combo-total-price">' + totalAll + '</span> lei</strong></div>';
                breakdownHtml += '</div>';
                comboHtml += breakdownHtml;
            }

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
            recalcAllComboBreakdowns($searchResults[0]);
        } else {
            $searchResults.html(html);
            recalcAllComboBreakdowns($searchResults[0]);
        }
    }

    // --- Show More Button Logic ---
    function renderShowMoreButton() {
        // Remove any existing button
        $('#show-more-results').remove();
        if (state.currentPage < state.lastPage) {
            $searchResults.append('<div id="show-more-results" style="text-align:center; margin:20px 0;"><button class="show-more-btn">Arată mai multe</button></div>');
        }
    }

    // Add click handler for show more
    $(document).on('click', '.show-more-btn', function() {
        if (state.isLoadingNextPage || state.currentPage >= state.lastPage) return;
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
                displayResults(pageData, true); // Append results
                renderShowMoreButton();
            }
            state.isLoadingNextPage = false;
        }).fail(function () {
            $searchLoading.hide();
            state.isLoadingNextPage = false;
        });
    });

    // --- Patch displayResults to show/hide the button ---
    const originalDisplayResults = displayResults;
    displayResults = function(data, append) {
        originalDisplayResults(data, append);
        renderShowMoreButton();
    };

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
            let adultMax = 0;
            let childMax = 0;
            $variationSelect.each(function() {
                adultMax = Math.max(adultMax, parseInt($(this).attr('data-adultMax')) || 0);
                childMax = Math.max(childMax, parseInt($(this).attr('data-childMax')) || 0);
            });
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
            const selectedPlanEncoded = $roomDetails.attr('data-selected-plan');
            let addedFromPlan = false;
            if (selectedPlanEncoded) {
                try {
                    const selectedPlan = JSON.parse(decodeURIComponent(selectedPlanEncoded));
                    if (Array.isArray(selectedPlan) && selectedPlan.length) {
                        selectedPlan.forEach(segment => {
                            if (segment.variation_id && segment.nights > 0) {
                                items.push({
                                    product_id: productId,
                                    variation_id: segment.variation_id,
                                    quantity: parseInt(segment.nights, 10)
                                });
                                addedFromPlan = true;
                            }
                        });
                    }
                } catch (e) {}
            }
            if (!addedFromPlan) {
                if ($variationSelect.length > 0) {
                    let allocatedNights = 0;
                    $variationSelect.each(function(selIdx) {
                        const $option = $(this);
                        let optionNights = parseInt($option.attr('data-plan-nights'), 10) || 0;
                        if (optionNights <= 0) {
                            const stayNights = getCurrentNights();
                            const splitNights = Math.max(1, Math.floor(stayNights / $variationSelect.length));
                            optionNights = (selIdx === $variationSelect.length - 1) ? (stayNights - allocatedNights) : splitNights;
                        }
                        allocatedNights += optionNights;
                        items.push({
                            product_id: productId,
                            variation_id: parseInt($option.val(), 10),
                            quantity: optionNights
                        });
                    });
                } else {
                    items.push({
                        product_id: productId,
                        variation_id: null,
                        quantity: getCurrentNights()
                    });
                }
            }
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

    // Helper to re-apply hotel filter after loading more results
    function applyCurrentHotelFilter() {
        const $active = $('.hotel-filter.active');
        if ($active.length) {
            const selectedHotel = $active.data('hotel');
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
        }
    }

    // Patch displayResults to re-apply hotel filter after rendering
    const originalDisplayResults2 = displayResults;
    displayResults = function(data, append) {
        originalDisplayResults2(data, append);
        renderShowMoreButton();
        applyCurrentHotelFilter();
    };
});
