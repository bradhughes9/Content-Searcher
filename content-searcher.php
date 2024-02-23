<?php
/*
Plugin Name: Content Searcher
Description: Searches through pages' content, ACF fields, and template files for specific content.
Version: 1.1.0
Author: Digital Home Developers
Author URI: https://digitalhomedevelopers.com
*/

/**
 * Todo
 * Break Logic into smaller functions
 * Make search themes, plugins, into their own functions.
 * 
 */

$inc = plugin_dir_path(__FILE__) . 'includes/';
$files = [
    'single-form-search',
    'single-image-search',
    'single-template-search'
];

foreach ($files as $file) {
    require_once $inc . $file . '.php';
}

// Hook for adding admin menus
add_action('admin_menu', 'content_searcher_menu');

// AJAX action for logged-in users
add_action('wp_ajax_search_content', 'handle_search_request');

// Add a new submenu under Tools
function content_searcher_menu()
{
    add_management_page('Content Searcher', 'Content Searcher', 'manage_options', 'content-searcher', 'content_searcher_admin_page');
}

// enqueue scripts and styles
function content_searcher_enqueue_scripts()
{
    wp_enqueue_style('content-searcher-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('content-searcher-scripts', plugin_dir_url(__FILE__) . 'assets/js/content-searcher.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'content_searcher_enqueue_scripts');



// Admin page content
function content_searcher_admin_page()
{
    wp_enqueue_media(); // Ensure the media uploader scripts are loaded.
?>


    <div class="wrap">
        <div id="selector-container" class="tab-group">

            <?php

            render_image_selector_tab();

            render_form_selector_tab();

            render_template_selector_tab();

            ?>

        </div>

        <div class="btn-container">
            <!-- Common Search Button -->
            <button id="search_button" class="content-searcher-button">Search</button>

            <!-- Clear Button -->
            <button id="clear_button" class="content-searcher-button">Clear</button>
        </div>

        <!-- Search Results -->
        <div id="search_results"></div>


    </div>


    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var image_frame;
            $('#image_url').click(function(e) {
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

                image_frame.on('select', function() {
                    // Get selected image and set its URL to the input field
                    var selection = image_frame.state().get('selection').first().toJSON();
                    $('#image_url').val(selection.url);
                });

                image_frame.open();
            });

            $('#search_button').click(function() {
                var searchType = $('#template_selector').val() ? 'template' :
                    ($('#gravity_form_selector').val() ? 'gravityform' : 'image');
                var searchTerm = searchType === 'image' ? $('#image_url').val() :
                    (searchType === 'template' ? $('#template_selector').val() :
                        $('#gravity_form_selector').val());

                triggerSearch(searchType, searchTerm);
            });
            // Clear Button Click Event
            $('#clear_button').click(function() {
                // Clear the input fields and search results
                $('#image_url').val('');
                $('#template_selector').prop('selectedIndex', 0);
                $('#gravity_form_selector').prop('selectedIndex', 0);
                $('#search_results').html('');
            });

            function triggerSearch(searchType, searchTerm) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_content',
                        search_type: searchType,
                        search_term: searchTerm,
                    },
                    success: function(response) {
                        // Display search results
                        $('#search_results').html(response.data);
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }
        });
    </script>



<?php
}

// Handle AJAX request
function handle_search_request()
{
    // Security checks, e.g., check nonce here

    $search_type = isset($_POST['search_type']) ? sanitize_text_field($_POST['search_type']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

    if ('image' === $search_type) {
        content_searcher_function($search_term, 'image');
    } elseif ('template' === $search_type) {
        content_searcher_function($search_term, 'template');
    } elseif ('gravityform' === $search_type) {
        content_searcher_function($search_term, 'gravityform');
    } else {
        wp_send_json_error('Invalid search type');
    }

    wp_die(); // Required to terminate immediately and return a proper response
}

function content_searcher_function($search_term, $search_type)
{
    global $wpdb;

    if ('image' === $search_type) {

        // Create a new instance of the SingleImageSearch class
        $results = single_image_search($search_term, $wpdb);
    } elseif ('gravityform' === $search_type) {
        $results = single_form_search($search_term, $wpdb);
    } else {
        wp_send_json_error('Invalid search type');
        return;
    }

    // Send the successful response with results
    wp_send_json_success(nl2br($results));
}
