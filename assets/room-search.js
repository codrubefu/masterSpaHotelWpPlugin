jQuery(document).ready(function ($) {
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
        $('.search-btn').prop('disabled', true).text('Searching...');

    $.post(window.hotelRoomSearchVars.ajax_url, formData, function (response) {
        $('#search-loading').hide();
        $('.search-btn').prop('disabled', false).text('Search Available Rooms');
        // Fix: parse response if it's a string
        var resp = response;
        if (typeof response === 'string') {
            try {
                resp = JSON.parse(response);
            } catch (e) {
                resp = {};
            }
        }
        console.log('Search response:', resp.success);
        if (resp.success) {
            displayResults(resp.data);
        } else {
            $('#search-results').html('<div class="error-message">' + (resp.data || 'No data') + '</div>');
        }
    }).fail(function () {
        $('#search-loading').hide();
        $('.search-btn').prop('disabled', false).text('Search Available Rooms');
        $('#search-results').html('<div class="error-message">Search failed. Please try again.</div>');
    });
    });

    function displayResults(data) {
        console.log('Search results:', data.combinations);
        if (!data.combinations || Object.keys(data.combinations).length === 0) {
            $('#search-results').html('<div class="no-results">No rooms available for the selected dates and criteria.</div>');
            return;
        }

        var html = '<h3>Available Room Combinations</h3>';

        $.each(data.combinations, function (roomType, typeData) {
            if (!typeData.combo || typeData.combo.length === 0) {
                return; // Skip if no combinations
            }

            var totalOptions = typeData.combo.length;
            var firstCombo = typeData.combo[0]; // Show only the first option

            html += '<div class="room-combination">';
            html += '<div class="room-type-title">' + roomType;

            // Add option count
            if (totalOptions > 1) {
                html += ' <span class="option-count">(' + totalOptions + ' options available)</span>';
            }

            html += '</div>';

            // Show only the first combination
            html += '<div class="combo-option">';
            html += '<strong>Option 1' + (totalOptions > 1 ? ' of ' + totalOptions : '') + ':</strong><br>';
            var productIds = [];
            $.each(firstCombo, function (roomIndex, room) {
                html += '<div class="room-details">';
                html += '<div class="room-info">';
                html += '<strong>Room ' + room.nr + '</strong> - ' + room.typeName + '<br>';
                html += 'Max Adults: ' + room.adultMax + ', Max Children: ' + room.kidMax;
                // If room has variations, show dropdown
                if (room.variations && Array.isArray(room.variations) && room.variations.length > 0) {
                    html += '<br><label>Choose variation: <select class="room-variation-select" data-room-index="' + roomIndex + '">';
                    $.each(room.variations, function(vi, variation) {
                        var attrs = Object.values(variation.attributes).join(', ');
                        html += '<option value="' + variation.variation_id + '" data-price="' + variation.price + '" data-image="' + (variation.image || '') + '">' + attrs + ' - $' + variation.price + (variation.in_stock ? '' : ' (Out of stock)') + '</option>';
                    });
                    html += '</select></label>';
                    // Show price for first variation by default
                    html += '<br><span class="room-price">Price: $' + room.variations[0].price + '</span>';
                } else if (room.product_price) {
                    html += '<br><span class="room-price">Price: $' + room.product_price + '</span>';
                }
                html += '</div>';
                if (room.product_url) {
                    html += '<div class="room-actions">';
                    html += '<a href="' + room.product_url + '" class="view-product-btn" target="_blank">View Details</a>';
                    html += '</div>';
                }
                html += '</div>';
    // Handle variation dropdown change to update price
    $('#search-results').on('change', '.room-variation-select', function() {
        var $select = $(this);
        var price = $select.find('option:selected').data('price');
        var $roomDetails = $select.closest('.room-info');
        $roomDetails.find('.room-price').text('Price: $' + price);
    });
                if (room.product_id) {
                    productIds.push(room.product_id);
                }
            });
            if (productIds.length > 0) {
                html += '<div class="room-actions" style="margin-top:10px;">';
                html += '<button class="add-combo-to-cart-btn" data-product-ids="' + productIds.join(',') + '">Add All To Cart</button>';
                html += '</div>';
            }


            html += '</div>';
            html += '</div>';
        });

        $('#search-results').html(html);
    }

    function showSingleOption(roomType, typeData, $container, $button) {
        var firstCombo = typeData.combo[0];
        var totalOptions = typeData.combo.length;
        var html = '<strong>Option 1' + (totalOptions > 1 ? ' of ' + totalOptions : '') + ':</strong><br>';
        var productIds = [];
        $.each(firstCombo, function (roomIndex, room) {
            html += '<div class="room-details">';
            html += '<div class="room-info">';
            html += '<strong>Room ' + room.nr + '</strong> - ' + room.typeName + '<br>';
            html += 'Max Adults: ' + room.adultMax + ', Max Children: ' + room.kidMax;
            if (room.product_price) {
                html += '<br><span class="room-price">Price: $' + room.product_price + '</span>';
            }
            html += '</div>';
            if (room.product_url) {
                html += '<div class="room-actions">';
                html += '<a href="' + room.product_url + '" class="view-product-btn" target="_blank">View Details</a>';
                html += '</div>';
            }
            html += '</div>';
            if (room.product_id) {
                productIds.push(room.product_id);
            }
        });

        html += '<div class="room-actions" style="margin-top:10px;">';
        html += '<button class="add-combo-to-cart-btn" data-product-ids="' + productIds.join(',') + '">Add All To Cart</button>';
        html += '</div>';

        html += '<div class="more-options">';
        html += '<button class="show-more-btn" data-room-type="' + roomType + '">Show ' + (totalOptions - 1) + ' more option(s)</button>';
        html += '</div>';
        $container.html(html);
        $button.removeClass('expanded');
    }

    function showAllOptions(roomType, typeData, $container, $button) {
        var html = '';
        $.each(typeData.combo, function (index, combo) {
            html += '<div class="single-combo-option">';
            html += '<strong>Option ' + (index + 1) + ' of ' + typeData.combo.length + ':</strong><br>';
            var productIds = [];
            $.each(combo, function (roomIndex, room) {
                html += '<div class="room-details">';
                html += '<div class="room-info">';
                html += '<strong>Room ' + room.nr + '</strong> - ' + room.typeName + '<br>';
                html += 'Max Adults: ' + room.adultMax + ', Max Children: ' + room.kidMax;
                if (room.product_price) {
                    html += '<br><span class="room-price">Price: $' + room.product_price + '</span>';
                }
                html += '</div>';
                if (room.product_url) {
                    html += '<div class="room-actions">';
                    html += '<a href="' + room.product_url + '" class="view-product-btn" target="_blank">View Details</a>';
                    html += '</div>';
                }
                html += '</div>';
                if (room.product_id) {
                    productIds.push(room.product_id);
                }
            });
            if (productIds.length > 0) {
                html += '<div class="room-actions" style="margin-top:10px;">';
                html += '<button class="add-combo-to-cart-btn" data-product-ids="' + productIds.join(',') + '">Add All To Cart</button>';
                html += '</div>';
            }
            html += '</div>';
            if (index < typeData.combo.length - 1) {
                html += '<hr class="option-separator">';
            }
        });
        html += '<div class="more-options">';
        html += '<button class="show-more-btn expanded" data-room-type="' + roomType + '">Show less</button>';
        html += '</div>';
        $container.html(html);
        $button.addClass('expanded');
    }
    // Add all products in a combo to cart using the new AJAX endpoint
    $(document).on('click', '.add-combo-to-cart-btn', function (e) {
        e.preventDefault();
        var btn = $(this);
        var $comboOption = btn.closest('.combo-option, .single-combo-option');
        var productIds = btn.data('product-ids').toString().split(',');
        if (!productIds.length) return;
        btn.prop('disabled', true).text('Adding...');
        // Build items array for AJAX
        var items = [];
        $comboOption.find('.room-details').each(function(idx, el) {
            var $roomDetails = $(el);
            var $variationSelect = $roomDetails.find('.room-variation-select');
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
        // Get search params from the form
        var $form = $('#hotel-availability-form');
        var searchParams = {
            adults: parseInt($form.find('#adults').val()),
            kids: parseInt($form.find('#kids').val()),
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
                    btn.text('Added!').prop('disabled', false);
                    if (cartUrl) {
                        window.location.href = cartUrl;
                    }
                } else {
                    btn.text('Error adding to cart').prop('disabled', false);
                }
            },
            error: function() {
                btn.text('Error adding to cart').prop('disabled', false);
            }
        });
    });
});
