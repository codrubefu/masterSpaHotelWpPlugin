jQuery(document).ready(function ($) {
    // Constants
    const MESSAGES = {
        SEARCHING: 'Se caută...',
        SEARCH_BUTTON: 'Caută camere disponibile',
        ADDING: 'Se adaugă...',
        ADDED: 'Adăugat!',
        ERROR_CART: 'Eroare la adăugarea în coș',
        NO_RESULTS: 'Nicio cameră disponibilă pentru datele și criteriile selectate.',
        CAPACITY_ERROR: 'Numărul total de adulți sau copii pentru camerele selectate depășește selecția dvs. de căutare. Vă rugăm să ajustați selecția.'
    };

    // Handle variation radio change to update price
    $(document).on('change', '.room-variation-radio', function() {
        const $input = $(this);
        const $roomDetails = $input.closest('.room-info');
        $roomDetails.find('.room-price').text(`Preț: ${$input.data('price')} lei`);
    });

    // Set minimum date to today
    var today = new Date().toISOString().split('T')[0];
    $('#start_date, #end_date').attr('min', today);

    // Update minimum end date when start date changes
    $('#start_date').on('change', function () {
        var startDate = new Date($(this).val());
        startDate.setDate(startDate.getDate() + 1);
        var minEndDate = startDate.toISOString().split('T')[0];
        $('#end_date').attr('min', minEndDate);

        // If end date is before new minimum, update it
        if ($('#end_date').val() && $('#end_date').val() <= $(this).val()) {
            $('#end_date').val(minEndDate);
        }
    });

    $('#hotel-availability-form').on('submit', function (e) {
        e.preventDefault();

        var formData = {
            action: 'search_hotel_rooms',
            adults: parseInt($('#adults').val()),
            kids: parseInt($('#kids').val()),
            number_of_rooms: $('#number_of_rooms').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            nonce: window.hotelRoomSearchVars.nonce
        };

        $('#search-loading').show();
        $('#search-results').empty();
        $('.search-btn').prop('disabled', true).text('Se caută...');

    $.post(window.hotelRoomSearchVars.ajax_url, formData, function (response) {
        $('#search-loading').hide();
        $('.search-btn').prop('disabled', false).text('Caută camere disponibile');
        // Fix: parse response if it's a string
        var resp = response;
        if (typeof response === 'string') {
            try {
                resp = JSON.parse(response);
            } catch (e) {
                resp = {};
            }
        }
        console.log('Răspuns căutare:', resp.success);
        if (resp.success) {
            displayResults(resp.data);
        } else {
            $('#search-results').html('<div class="error-message">' + (resp.data || 'Nu există date') + '</div>');
        }
    }).fail(function () {
        $('#search-loading').hide();
        $('.search-btn').prop('disabled', false).text('Caută camere disponibile');
        $('#search-results').html('<div class="error-message">Căutarea a eșuat. Vă rugăm să încercați din nou.</div>');
    });
    });

    function displayResults(data) {
        console.log('Rezultatele căutării:', data.combinations);
        if (!data.combinations || Object.keys(data.combinations).length === 0) {
            $('#search-results').html('<div class="no-results">Nicio cameră disponibilă pentru datele și criteriile selectate.</div>');
            return;
        }

    var html = '<h3>Combinații de camere disponibile</h3>';
    html += '<div class="select-holtes"><a href="#all" class="hotel-filter active" data-hotel="all">Toate Hotelurile</a><a href="#1" class="hotel-filter" data-hotel="1"> Hotel Noblesse</a><a href="#2" class="hotel-filter" data-hotel="2"> Hotel Royal</a></div>';
    
    // Add click handler for hotel filtering
    $(document).on('click', '.hotel-filter', function(e) {
        e.preventDefault();
        $('.select-holtes a').removeClass('active');
        var selectedHotel = $(this).data('hotel');
        $(this).addClass('active');

        // Remove active class from all links and add to clicked one
        $('.hotel-filter').removeClass('active');
        $(this).addClass('active');
        
        if (selectedHotel === 'all' || selectedHotel == 3) {
            $('.combo-option').show();
        } else {
            $('.combo-option').each(function() {
                var roomType = $(this).data('room-type');
                if (roomType == selectedHotel) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // Get adults and number_of_rooms from form for logic
    var $form = $('#hotel-availability-form');
    var totalAdults = parseInt($form.find('#adults').val()) || 0;
    var totalKids = parseInt($form.find('#kids').val()) || 0;
    var totalRooms = parseInt($form.find('#number_of_rooms').val()) || 1;
    var hideSingle = (totalAdults > 0 && totalRooms > 0 && totalAdults % totalRooms === 0);
    var x = 0;
    $.each(data.combinations, function (roomType, typeData) {
            if (!typeData.combo || typeData.combo.length === 0) {
                return; // Skip if no combinations
            }

            var totalOptions = typeData.combo.length;
            var firstCombo = typeData.combo[0]; // Show only the first option

    
            // Show only the first combination
            let type;
            const hotels = String(typeData.hotels); // Ensure it's a string

            if (/^1+$/.test(hotels)) {
                type = 1;
            } else if (/^2+$/.test(hotels)) {
                type = 2;
            } else {
                type = 3; // For combinations like '12', '21', etc.
            }
            html += '<div class="combo-option" data-room-type="' + type + '">';
            var productIds = [];
            $.each(firstCombo, function (roomIndex, room) {
                html += '<div class="room-details">';
                html += '<div class="room-info">';
                html += '<div class="room-image"><img src="' + (room.product_image || '') + '" alt="' + room.typeName + '"></div> ';
                html += '<div class="room-details-info">';
                if (room.hotel == 1) {
                    $stars = 4;
                }else{
                    $stars = 3;
                }

                    html += '<h1><a href="' + room.product_url + '" >Camera: ' +  room.typeName + ' (' + $stars + ' stele)</a></h1>';

                html += '<p>' + (room.description || '') + '</p>';

                // If room has variations, show dropdown
                if (room.variations && Array.isArray(room.variations) && room.variations.length > 0) {
                    html += '<label>Alegeți o variantă:</label><div class="room-variation-radio-group" data-room-index="' + roomIndex + '"><ul>';
                    var firstVisible = true;
                    $.each(room.variations, function(vi, variation) {
                        var attrs = Object.values(variation.attributes).join(', ');
                        var isSingle = false;
                        // Check if "single" is in the attributes or title (case-insensitive)
                        if (attrs.toLowerCase().includes('single') || (variation.title && variation.title.toLowerCase().includes('single'))) {
                            isSingle = true;
                        }
                        // Hide if hideSingle is true and this is a single variation
                        if (hideSingle && isSingle) {
                            return; // skip rendering this variation
                        }
                        var checkedAttr = '';
                        if (firstVisible) {
                            checkedAttr = 'checked="checked"';
                            firstVisible = false;
                        }
                        var adultMax = isSingle ? 1 : room.adultMax;
                        var childMax = isSingle ? 0 : room.kidMax;
                        html += '<li><label style="margin-right:10px;"><input type="radio" name="room-variation-' + x + '" class="room-variation-radio room-variation-select" value="' + variation.variation_id + '" data-price="' + variation.price + '" data-image="' + (variation.image || '') + '" data-adultMax="' + adultMax + '" data-childMax="' + childMax + '" ' + checkedAttr + '> ' + attrs + ' - ' + variation.price + ' lei' + (variation.in_stock ? '' : ' (Stoc epuizat)') + '</label></li>';
                        x++;
                    });
                    html += '</ul></div>';
                    // Show price for first variation by default
                    html += '<span class="room-price">Preț: ' + room.variations[0].price + ' lei</span>';
                } else if (room.product_price) {
                    html += '<span class="room-price">Preț: ' + room.product_price + ' lei</span>';
                }
                html += '</div>';
                html += '</div>';
                if (room.product_id) {
                    productIds.push(room.product_id);
                }
                html += '</div>';
            });
            if (productIds.length > 0) {
                html += '<div class="room-actions" style="margin-top:10px;">';
                html += '<button class="add-combo-to-cart-btn" data-product-ids="' + productIds.join(',') + '">Adaugă totul în coș</button>';
                html += '</div>';
            }


            html += '</div>';
            html += '</div>';
        });

        $('#search-results').html(html);
    }

    // Add all products in a combo to cart using the new AJAX endpoint
    $(document).on('click', '.add-combo-to-cart-btn', function (e) {
        e.preventDefault();
        var btn = $(this);
        var $comboOption = btn.closest('.combo-option, .single-combo-option');
        var productIds = btn.data('product-ids').toString().split(',');
        if (!productIds.length) return;
        // Get search params from the form
        var $form = $('#hotel-availability-form');
        var maxAdults = parseInt($form.find('#adults').val()) || 0;
        var maxKids = parseInt($form.find('#kids').val()) || 0;
        // Sum selected radios' adultMax and childMax
        var totalAdults = 0;
        var totalKids = 0;
        $comboOption.find('.room-details').each(function(idx, el) {
            var $roomDetails = $(el);
            var $variationSelect = $roomDetails.find('.room-variation-radio:checked');
            var adultMax = $variationSelect.length ? parseInt($variationSelect.attr('data-adultMax')) || 0 : 0;
            var childMax = $variationSelect.length ? parseInt($variationSelect.attr('data-childMax')) || 0 : 0;
            totalAdults += adultMax;
            totalKids += childMax;
        });
   
        // Remove any previous error message
        $comboOption.find('.combo-error-message').remove();
        if (totalAdults < maxAdults || totalKids < maxKids) {
            var errorMsg = $('<div class="combo-error-message" style="color:red; margin-bottom:8px;">Numărul total de adulți sau copii pentru camerele selectate depășește selecția dvs. de căutare. Vă rugăm să ajustați selecția.</div>');
            btn.closest('.room-actions').before(errorMsg);
            return;
        }
        btn.prop('disabled', true).text('Se adaugă...');
        // Build items array for AJAX
        var items = [];
        $comboOption.find('.room-details').each(function(idx, el) {
            var $roomDetails = $(el);
            var $variationSelect = $roomDetails.find('.room-variation-radio:checked');
            var productId = productIds[idx];
            var variationId = '';
            if ($variationSelect.length) {
                variationId = $variationSelect.val();
            }
            items.push({
                product_id: productId,
                variation_id: variationId ? variationId : null,
                quantity: 1
            });
        });
        var searchParams = {
            adults: maxAdults,
            kids: maxKids,
            number_of_rooms: $form.find('#number_of_rooms').val(),
            start_date: $form.find('#start_date').val(),
            end_date: $form.find('#end_date').val()
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
                // Accept both {success: true, data: {...}} and {added:..., cart_url:...}
                var isSuccess = false;
                var cartUrl = '';
                if (typeof res === 'string') {
                    try {
                        res = JSON.parse(res);
                    } catch (e) {
                        res = {};
                    }
                }
                if (res.success) {
                    isSuccess = true;
                    cartUrl = res.data && res.data.cart_url ? res.data.cart_url : '';
                } else if (typeof res.added !== 'undefined' && res.added > 0) {
                    isSuccess = true;
                    cartUrl = res.cart_url || '';
                }
                if (isSuccess) {
                    btn.text('Adăugat!').prop('disabled', false);
                    if (cartUrl) {
                        window.location.href = cartUrl;
                    }
                } else {
                    btn.text('Eroare la adăugarea în coș').prop('disabled', false);
                }
            },
            error: function() {
                btn.text('Eroare la adăugarea în coș').prop('disabled', false);
            }
        });
    });
});
