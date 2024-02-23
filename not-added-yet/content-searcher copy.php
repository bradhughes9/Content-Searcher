<?php
/*
Plugin Name: Content Searcher
Description: Searches through pages' content, ACF fields, and template files for specific content.
Version: 1.1.0
Author: Digital Home Developers
Author URI: https://digitalhomedevelopers.com
*/

defined('ABSPATH') or die('No script kiddies please!');


require plugin_dir_path(__FILE__) . 'includes/display-active-themes-and-plugins-checkboxes.php';
require plugin_dir_path(__FILE__) . 'includes/find-media-in.php';

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
        <h2>Content Searcher</h2>

        <div id="selector-container" class="tab-group">

            <div class="tab active" id="image-selector-tab">
                <div class="tab-title">
                    <h3>Find Single Image</h3>
                </div>
                <div id="image-selector-container" class="tab-content">
                    <input id="image_url" class="content-searcher-input" type="text" placeholder="Image URL" readonly="readonly" />
                    <button id="upload_button" class="button">Select Image</button>
                </div>
            </div>

            <div class="tab" id="template-selector-tab">
                <div class="tab-title">
                    <h3>Template</h3>
                </div>
                <div id="template-selector-container" class="tab-content">
                    <select id="template_selector" class="content-searcher-input">
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
            </div>

            <div id="gravity-form-selector-tab" class="tab">
                <div class="tab-title">
                    <h3>Gravity Form</h3>
                </div>
                <div id="gravity-form-selector-container" class="tab-content">
                    <select id="gravity_form_selector" class="content-searcher-input">
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
            </div>

            <div id="media-selector-tab" class="tab">
                <div class="tab-title">
                    <h3>Media</h3>
                </div>
                <div id="media-selector-container" class="tab-content">
                    <?php display_active_themes_and_plugins_checkboxes(); ?>
                </div>
            </div>

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
                var searchType, searchTerm, selectedThemes = [],
                    selectedPlugins = [];

                $('input[name="selected_themes[]"]:checked').each(function() {
                    selectedThemes.push($(this).val());
                });

                $('input[name="selected_plugins[]"]:checked').each(function() {
                    selectedPlugins.push($(this).val());
                });

                if ($('#template_selector').val()) {
                    searchType = 'template';
                    searchTerm = $('#template_selector').val();
                } else if ($('#gravity_form_selector').val()) {
                    searchType = 'gravityform';
                    searchTerm = $('#gravity_form_selector').val();
                } else if ($('#media_selector').val()) {
                    searchType = 'media';
                    searchTerm = $('#media_selector').val();
                } else {
                    searchType = 'image';
                    searchTerm = $('#image_url').val();
                }

                triggerSearch(searchType, searchTerm, selectedThemes, selectedPlugins);
            });

            // Clear Button Click Event
            $('#clear_button').click(function() {
                // Clear the input fields and search results
                $('#image_url').val('');
                $('#template_selector').prop('selectedIndex', 0);
                $('#gravity_form_selector').prop('selectedIndex', 0);
                $('#search_results').html('');
            });

            function triggerSearch(searchType, searchTerm, selectedThemes, selectedPlugins) {
                // Assuming you have an AJAX setup ready
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'search_content',
                        search_type: searchType,
                        search_term: searchTerm,
                        selected_themes: selectedThemes,
                        selected_plugins: selectedPlugins,
                    },
                    success: function(response) {
                        $('#search_results').html(response.data);
                        console.log(response);
                    },
                    error: function(error) {
                        // Handle error
                        console.error(error);
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
    // Security checks, e.g., check nonce

    $search_type = isset($_POST['search_type']) ? sanitize_text_field($_POST['search_type']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $selected_themes = isset($_POST['selected_themes']) ? array_map('sanitize_text_field', $_POST['selected_themes']) : [];
    $selected_plugins = isset($_POST['selected_plugins']) ? array_map('sanitize_text_field', $_POST['selected_plugins']) : [];

    $search_types = array('image', 'template', 'gravityform', 'media');
    if (!in_array($search_type, $search_types)) {
        wp_send_json_error('Invalid search type');
    } else {
        content_searcher_function($search_term, $search_type, $selected_themes, $selected_plugins);
    }

    wp_die(); // Required to terminate immediately and return a proper response
}


function content_searcher_function($search_term = '', $search_type = '', $selected_themes = [], $selected_plugins = [])
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
    } elseif ('media' === $search_type) {
        // Initialize the array to hold all media items found.
        $media_items = [];

        // Find media in attachments (database)
        $attachment_media_items = find_media_in_attachments();
        $media_items = array_merge($media_items, $attachment_media_items);

        // Get selected themes and plugins from the request
        $selected_themes = isset($_POST['selected_themes']) ? (array) $_POST['selected_themes'] : [];
        $selected_plugins = isset($_POST['selected_plugins']) ? (array) $_POST['selected_plugins'] : [];

        // Find media in selected themes
        if (!empty($selected_themes)) {
            foreach ($selected_themes as $theme_slug) {
                $theme_media_items = find_media_in_theme([$theme_slug]); // Assuming the function expects an array
                $media_items = array_merge($media_items, $theme_media_items);
            }
        }

        // Find media in selected plugins
        if (!empty($selected_plugins)) {
            $plugin_media_items = find_media_in_plugins($selected_plugins);
            $media_items = array_merge($media_items, $plugin_media_items);
        }

        // Prepare the results for output
        $results = !empty($media_items) ? '<ul class="search-results-list">' : 'No media found.';

        foreach ($media_items as $item) {
            // Assuming each $item is a string description of the found media
            $results .= "<li>{$item}</li>";
            error_log('Media item: ' . $item);
        }

        // Close the list if media items were found
        if (!empty($media_items)) {
            $results .= '</ul>';
        }

        // Return or echo $results as needed
    } else {
        wp_send_json_error('Invalid search type');
        return;
    }

    // Send the successful response with results
    wp_send_json_success(nl2br($results));
}
