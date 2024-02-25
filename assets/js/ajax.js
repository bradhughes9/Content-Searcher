jQuery(document).ready(function ($) {
    var image_frame;
    $('#upload_button').click(function (e) {
        e.preventDefault();
        if (image_frame) {
            image_frame.open();
            return;
        }
        // Define image_frame as wp.media object
        image_frame = wp.media({
            title: 'Select Media',
            multiple: false,
            library: {
                type: 'image',
            }
        });

        image_frame.on('select', function () {
            // Get selected image and set its URL to the input field
            var selection = image_frame.state().get('selection').first().toJSON();
            $('#image_url').val(selection.url);
            $('#image_id').val(selection.id);
        });

        image_frame.open();
    });

    $('#search_button').click(function () {
        var searchType = $('#template_selector').val() ? 'template' :
            ($('#gravity_form_selector').val() ? 'gravityform' : 'image');
        // Update to include image ID in the search term when the search type is 'image'
        var searchTerm = searchType === 'image' ? { url: $('#image_url').val(), id: $('#image_id').val() } :
            (searchType === 'template' ? $('#template_selector').val() :
                $('#gravity_form_selector').val());

        triggerSearch(searchType, searchTerm);
    });
    // Clear Button Click Event
    $('#clear_button').click(function () {
        // Clear the input fields and search results
        $('#image_url').val('');
        $('#image_id').val('');
        $('#template_selector').prop('selectedIndex', 0);
        $('#gravity_form_selector').prop('selectedIndex', 0);
        $('#search_results').html('');
    });

    function triggerSearch(searchType, searchTerm) {
        var data = {
            action: 'search_content',
            search_type: searchType,
            nonce: ContentSearcherData.nonce
        };
    
        if (searchType === 'image') {
            data.search_url = searchTerm.url;
            data.search_id = searchTerm.id;
        } else {
            data.search_term = searchTerm;
        }
    
        $.ajax({
            url: ContentSearcherData.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                $('#search_results').html(response.data);
            },
            error: function(error) {
                console.log(error);
            }
        });
    }
    
});