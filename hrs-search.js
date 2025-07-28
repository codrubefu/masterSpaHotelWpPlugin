jQuery(document).ready(function($){
    // Dacă există parametri de căutare, declanșează automat căutarea
    if(window.hrs_search_params) {
        performSearch(window.hrs_search_params);
    }
    $('#hotel-room-search-form').on('submit', function(e){
        // Nu mai folosim AJAX la submit, lăsăm browserul să redirecționeze
    });
    function performSearch(params) {
        $('#hotel-room-search-results').html('<em>Searching...</em>');
        $.post(hrs_ajax.ajax_url, {
            action: 'hrs_search_rooms',
            data: params
        }, function(response){
            if(response.success && response.data.length > 0){
                var html = '<div class="table-responsive"><table class="table table-striped align-middle">';
                html += '<thead><tr>'
                    + '<th scope="col">Image</th>'
                    + '<th scope="col">Name</th>'
                    + '<th scope="col">Price</th>'
                    + '<th scope="col">Info</th>'
                    + '</tr></thead><tbody>';
                $.each(response.data, function(i, room){
                    html += '<tr>'
                        + '<td><img class="img-thumbnail" style="width:80px;height:60px;object-fit:cover;" src="' + (room.image ? room.image : 'https://resortparadis.ro/wp-content/uploads/2023/09/Venetia-Resort-Paradis-Ramnicu-Valcea-11-1200x801.webp') + '" alt="'+room.name+'"></td>'
                        + '<td>' + room.name + '</td>'
                        + '<td>$' + room.price + ' / night</td>'
                        + '<td>' + (room.info ? room.info : '-') + '</td>'
                        + '</tr>';
                });
                html += '</tbody></table></div>';
                $('#hotel-room-search-results').html(html);
            } else {
                $('#hotel-room-search-results').html('<em>No rooms found.</em>');
            }
        });
    }
});
