<?php
/*
Plugin Name: My Content Searcher
Plugin URI: 
Description: Searches through pages' content, ACF fields, and template files for specific content.
Version: 1.0
Author: Bradley Hughes
Author URI:
*/

// Hook for adding admin menus
add_action('admin_menu', 'my_content_searcher_menu');
// AJAX action for logged-in users
add_action('wp_ajax_search_content', 'handle_search_request');

// Add a new submenu under Settings
function my_content_searcher_menu()
{
    add_options_page('My Content Searcher', 'Content Searcher', 'manage_options', 'my-content-searcher', 'my_content_searcher_admin_page');
}

// Admin page content
function my_content_searcher_admin_page()
{
    wp_enqueue_media(); // Ensure the media uploader scripts are loaded.
?>
    <style>
        .my-content-searcher-container {
            max-width: 750px;
            margin: 2rem auto;
            padding: 1.25rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .13);
        }

        .my-content-searcher-container h2 {
            color: #23282d;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.3125rem;
            margin-bottom: 1.25rem;
        }

        .my-content-searcher-input,
        .my-content-searcher-button {
            width: 100%;
            padding: 0.5rem;
            line-height: 1.5;
            font-size: 1rem;
            margin-bottom: 0.625rem;
        }

        .my-content-searcher-input {
            border: 1px solid #ddd;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, .07);
            background-color: #fff;
            color: #32373c;
            margin-right: 0.3125rem;
        }

        .my-content-searcher-button {
            display: block;
            text-align: center;
            border-radius: 3px;
            background: #0085ba;
            color: #fff;
            cursor: pointer;
            border: none;
            box-shadow: none;
        }

        .my-content-searcher-button:hover,
        .my-content-searcher-button:focus {
            background: #0073aa;
            color: #fff;
            outline: none;
        }

        .search-results-list {
            list-style-type: none;
            padding-left: 0;
        }

        .search-results-list li {
            margin: 0.625rem 0;
            padding: 0.3125rem;
            background-color: #f9f9f9;
            border-left: 4px solid #0073aa;
        }

        .search-results-list li a {
            text-decoration: none;
            color: #0073aa;
            display: block;
            padding: 0.3125rem;
        }

        .search-results-list li a:hover {
            background-color: #f1f1f1;
        }

        .selector-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            /* Space between elements */
        }

        .selector-container>div,
        .selector-container>button {
            width: 100%;
            /* Full width for mobile */
        }

        @media (min-width: 768px) {

            /* Adjust breakpoint as needed */
            .selector-container {
                flex-direction: row;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 0.5rem;
                /* Reduced gap for larger screens */
            }

            .selector-container>div,
            .selector-container>button {
                flex: 1 1 auto;
                /* Flex grow and basis auto */
            }
        }
    </style>

    <div class="wrap">
        <h2>My Content Searcher</h2>

        <div class="selector-container">
            <!-- Image Selector -->
            <div class="image-selector-container">
                <input id="image_url" class="my-content-searcher-input" type="text" placeholder="Image URL" readonly="readonly" />
                <button id="upload_button" class="button">Select Image</button>
            </div>

            <!-- Template Selector -->
            <div class="template-selector-container">
                <select id="template_selector" class="my-content-searcher-input">
                    <option value="">Select a Template</option>
                    <?php
                    // PHP code to generate template options
                    $templates = glob(get_template_directory() . '/template-*.php');
                    foreach ($templates as $template) {
                        $template_name = basename($template);
                        echo "<option value='{$template_name}'>{$template_name}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Gravity Forms Selector -->
            <div class="gravity-form-selector-container">
                <select id="gravity_form_selector" class="my-content-searcher-input">
                    <option value="">Select a Gravity Form</option>
                    <?php
                    // PHP code to generate Gravity Forms options
                    if (class_exists('GFFormsModel')) {
                        $forms = GFFormsModel::get_forms();
                        foreach ($forms as $form) {
                            echo "<option value='[gravityform id=\"" . absint($form->id) . "\" title=\"true\"]'>" . esc_html($form->title) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Common Search Button -->
            <button id="search_button" class="my-content-searcher-button">Search</button>

            <!-- Clear Button -->
            <button id="clear_button" class="my-content-searcher-button">Clear</button>
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
        my_content_searcher_function($search_term, 'image');
    } elseif ('template' === $search_type) {
        my_content_searcher_function($search_term, 'template');
    } elseif ('gravityform' === $search_type) {
        my_content_searcher_function($search_term, 'gravityform');
    } else {
        wp_send_json_error('Invalid search type');
    }

    wp_die(); // Required to terminate immediately and return a proper response
}

function my_content_searcher_function($search_term, $search_type)
{
    global $wpdb; // Global WordPress database access

    $posts_with_image = array();
    $pages_with_template = array();
    $pages_with_form = array();

    if ('image' === $search_type) {
        // Reduce the specificity of the search term for the image URL.
        $search_basename = wp_basename($search_term); // Gets the filename part of the URL
        $search_basename_no_ext = preg_replace('/\\.[^.\\s]{3,4}$/', '', $search_basename); // Remove the file extension
        $like_pattern = '%' . $wpdb->esc_like($search_basename_no_ext) . '%'; // Prepare for LIKE query

        // Search in post content with adjusted like_pattern, excluding revisions
        $posts = $wpdb->get_results($wpdb->prepare(
            "
            SELECT ID, post_title FROM $wpdb->posts 
            WHERE post_status = 'publish' 
            AND post_type NOT IN ('revision') 
            AND post_content LIKE %s",
            $like_pattern
        ));

        foreach ($posts as $post) {
            $posts_with_image[] = "Post ID: {$post->ID}, Title: {$post->post_title}, Found in Content";
        }

        // Search in ACF fields with adjusted like_pattern, also considering only non-revision posts
        $meta_posts = $wpdb->get_results($wpdb->prepare(
            "
            SELECT post_id FROM $wpdb->postmeta 
            JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id
            WHERE $wpdb->posts.post_status = 'publish' 
            AND $wpdb->posts.post_type NOT IN ('revision') 
            AND meta_value LIKE %s",
            $like_pattern
        ));

        foreach ($meta_posts as $meta_post) {
            $post_title = get_the_title($meta_post->post_id);
            // To avoid duplicate entries, check if this post ID is already in the array
            $unique_id_title = "Post ID: {$meta_post->post_id}, Title: {$post_title}, Found in ACF";
            if (!in_array($unique_id_title, $posts_with_image)) {
                $posts_with_image[] = $unique_id_title;
            }
        }

        // Inside your my_content_searcher_function, adjust the results generation part
        $results = !empty($posts_with_image) ? '<ul class="search-results-list">' : 'No posts found with the specified image.';

        foreach ($posts_with_image as $item) {
            // Assuming $item is like "Post ID: {$post->ID}, Title: {$post->post_title}, Found in Content"
            preg_match('/Post ID: (\d+), Title: ([^,]+),/', $item, $matches);
            $post_id = $matches[1];
            $post_title = $matches[2];
            $edit_link = get_edit_post_link($post_id);
            $results .= "<li><a target='_blank' href='{$edit_link}'>{$post_title}</a></li>";
        }

        if (!empty($posts_with_image)) {
            $results .= '</ul>';
        }
    } elseif ('template' === $search_type) {
        // First, check if the search is for the 'front-page.php' template
        if ($search_term === 'front-page.php') {
            $front_page_id = get_option('page_on_front'); // Get the static front page ID
            if ($front_page_id) {
                $post_title = get_the_title($front_page_id);
                $pages_with_template[] = "Page ID: {$front_page_id}, Title: {$post_title}, Using front-page.php";
            }
        }

        // Continue with the normal template search logic
        $pages = $wpdb->get_results($wpdb->prepare(
            "
            SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_wp_page_template' 
            AND meta_value = %s",
            $search_term
        ));

        foreach ($pages as $page) {
            $post_title = get_the_title($page->post_id);
            $pages_with_template[] = "Page ID: {$page->post_id}, Title: {$post_title}";
        }

        // Combine results and format them
        $results = !empty($pages_with_template) ? '<ul class="search-results-list">' : 'No pages found using the specified template.';
        foreach ($pages_with_template as $item) {
            preg_match('/Page ID: (\d+), Title: ([^,]+)/', $item, $matches);
            $page_id = $matches[1];
            $page_title = $matches[2];
            $edit_link = get_edit_post_link($page_id);
            $results .= "<li><a target='_blank' href='{$edit_link}'>{$page_title}</a></li>";
        }
        if (!empty($pages_with_template)) {
            $results .= '</ul>';
        }
    } elseif ('gravityform' === $search_type) {
        // Prepare the LIKE pattern for searching Gravity Forms shortcodes
        $like_pattern = '%' . $wpdb->esc_like('[gravityform') . '%';

        // Log the prepared statement to the debug log
        error_log('Prepared query: ' . $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' AND post_content LIKE %s", $like_pattern));

        // Execute the query
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' AND post_content LIKE %s",
            $like_pattern
        ));

        // Log the query results
        error_log('Query results: ' . print_r($posts, true));

        // Process the results
        foreach ($posts as $post) {
            $edit_link = get_edit_post_link($post->ID);
            $pages_with_form[] = "<li><a target='_blank' href='{$edit_link}'>{$post->post_title}</a></li>";
        }

        // Prepare the final results output
        $results = !empty($pages_with_form) ? '<ul class="search-results-list">' . implode('', $pages_with_form) . '</ul>' : 'No pages found with a Gravity Form.';
    } else {
        wp_send_json_error('Invalid search type');
        return;
    }

    // Send the successful response with results
    wp_send_json_success(nl2br($results));
}
