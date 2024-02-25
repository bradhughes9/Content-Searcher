<?php

if (!defined('ABSPATH')) {
    die;
}

function render_image_selector_tab()
{
?>
    <div id="image-selector-tab" class="tab active">
        <div class="tab-title">
            <h3>Image</h3>
        </div>
        <div id="image-selector-container" class="tab-content">
            <input id="image_url" class="content-searcher-input" type="text" placeholder="Image URL" />
            <input id="image_id" class="content-searcher-input" type="number" />

            <button id="upload_button" class="button">Select Image</button>
        </div>
    </div>
<?php
}


function single_image_search($search_term, $search_id, $wpdb)
{

    $posts_with_image = [];
    $search_term = sanitize_text_field($search_term);
    $search_term = esc_url($search_term);

    $search_basename = wp_basename($search_term);
    $search_basename_no_ext = preg_replace('/\\.[^.\\s]{3,4}$/', '', $search_basename);
    $like_pattern = '%' . $wpdb->esc_like($search_basename_no_ext) . '%';

    // Search in post content for the URL pattern
    if (!empty($search_term)) {
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title FROM $wpdb->posts 
            WHERE post_status = 'publish' 
            AND post_type NOT IN ('revision') 
            AND post_content LIKE %s",
            $like_pattern
        ));

        foreach ($posts as $post) {
            $posts_with_image[] = "Post ID: {$post->ID}, Title: {$post->post_title}, Found in Content";
        }
    }

if (!empty($search_id)) {
    // Prepare the SQL statement with placeholders
    $prepare_statement = $wpdb->prepare();



        // Search in post content for block-embedded image IDs
        $id_posts = $wpdb->get_results($prepare_statement);
        error_log(print_r($id_posts, true));
        foreach ($id_posts as $id_post) {
            $unique_id_title = "Post ID: {$id_post->ID}, Title: {$id_post->post_title}, Found with ID in Block";
            if (!in_array($unique_id_title, $posts_with_image)) {
                $posts_with_image[] = $unique_id_title;
            }
        }

        // Additionally, search in postmeta for the image ID
        $meta_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_value = %s",
            $search_id
        ));

        foreach ($meta_posts as $meta_post) {
            $post_title = get_the_title($meta_post->post_id);
            $unique_id_title = "Post ID: {$meta_post->post_id}, Title: {$post_title}, Found in Post Meta";

            if (!in_array($unique_id_title, $posts_with_image)) {
                $posts_with_image[] = $unique_id_title;
            }
        }
    }

    // Compile and return results
    $results = !empty($posts_with_image) ? '<ul class="search-results-list">' : 'No posts found with the specified image.';
    foreach ($posts_with_image as $item) {
        preg_match('/Post ID: (\d+), Title: ([^,]+),/', $item, $matches);
        $post_id = $matches[1];
        $post_title = $matches[2];
        $edit_link = get_edit_post_link($post_id);
        $results .= "<li><a target='_blank' href='{$edit_link}'>{$post_title}</a></li>";
    }

    if (!empty($posts_with_image)) {
        $results .= '</ul>';
    }

    return $results;
}
