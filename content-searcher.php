<?php
/*
Plugin Name: Content Searcher
Description: Searches through pages' content, ACF fields, and template files for specific content.
Version: 1.1.0
Author: Digital Home Developers
Author URI: https://digitalhomedevelopers.com
*/

// Potentially create a list of the "used" images, allow the user to compress and download the unused images/media.
// Then allow the user to delete the unused images/media from the media library.

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

// Add a new submenu under Tools
function content_searcher_menu()
{
    add_management_page('Content Searcher', 'Content Searcher', 'manage_options', 'content-searcher', 'content_searcher_admin_page');
}

add_action('wp_ajax_search_content', 'handle_search_request');
add_action('wp_ajax_nopriv_search_content', 'handle_search_request'); // If needed, for users not logged in

function content_searcher_enqueue_scripts()
{
    wp_enqueue_script('content-searcher-ajax', plugin_dir_url(__FILE__) . 'assets/js/ajax.js', array('jquery'), '1.0.0', true);
    wp_localize_script('content-searcher-ajax', 'ContentSearcherData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('content_searcher_nonce'),
    ));

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



<?php
}

// Handle AJAX request
function handle_search_request()
{

    if ( !wp_verify_nonce($_POST['nonce'], 'content_searcher_nonce') ) {
        wp_die('Nonce verification failed!', '', 403);
    }

    global $wpdb;
    // Security checks, e.g., check nonce here

    $search_type = isset($_POST['search_type']) ? sanitize_text_field($_POST['search_type']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $search_id = isset($_POST['search_id']) ? sanitize_text_field($_POST['search_id']) : '';

    // error_log('Search Type: ' . $search_type);
    // error_log('Search Term: ' . $search_term);
    // error_log('Search ID: ' . $search_id);

    $available_search_types = ['image', 'template', 'gravityform'];

    if ('image' === $search_type) {
        // content_searcher_function($search_term, $search_id, 'image');
        $results = single_image_search($search_term, $search_id, $wpdb);
        wp_send_json_success(nl2br($results));
        // error_log(print_r($results, true));
    } else {
        wp_send_json_error('Invalid search type');
    }

    wp_die(); // Required to terminate immediately and return a proper response
}


// function content_searcher_function($search_term, $search_id = null, $search_type)
// {
//     global $wpdb;

//     if ('image' === $search_type) {
//         // Create a new instance of the SingleImageSearch class
//         $results = single_image_search($search_term, $search_id, $wpdb);
//     } elseif ('gravityform' === $search_type) {
//         $results = single_form_search($search_term, $wpdb);
//     } elseif ('template' === $search_type) {
//         $results = single_template_search($search_term, $wpdb);
//     } else {
//         wp_send_json_error('Invalid search type');
//         return;
//     }

//     // Send the successful response with results
//     wp_send_json_success(nl2br($results));
// }
