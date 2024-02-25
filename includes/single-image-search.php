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

    // working
    if (!empty($search_id)) {

        error_log('Search ID: ' . $search_id);

        // make a new wp query, we are going to get the post_content from all the posts and parse through them
        $query = new WP_Query(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_content = get_the_content();
            $post_title = get_the_title();

            // check if the image is in the post_content
            if (strpos($post_content, $search_id) !== false) {
                // error log the the part of the post content where the id was found
                error_log('Post Content: ' . $post_content);
                $posts_with_image[] = "Post ID: {$post_id}, Title: {$post_title}, Found in Content";
            }
        }

        wp_reset_postdata();


        
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
