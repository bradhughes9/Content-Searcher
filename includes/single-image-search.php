<?php

if (!defined('ABSPATH')) {
    die;
}

function render_image_selector_tab () {
    ?>
    <div id="image-selector-tab" class="tab active">
        <div class="tab-title">
            <h3>Image</h3>
        </div>
        <div id="image-selector-container" class="tab-content">
            <input id="image_url" class="content-searcher-input" type="text" placeholder="Image URL" readonly="readonly" />
            <button id="upload_button" class="button">Select Image</button>
        </div>
    </div>
    <?php
}


function single_image_search($search_term, $wpdb)
    {
        $posts_with_image = [];
        $search_term = sanitize_text_field($search_term);
        $search_term = esc_url($search_term);
        
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

        return $results;
    }