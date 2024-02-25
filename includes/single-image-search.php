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


function cs_parse_post_content_for_item($id = null, $url = '')
{

    $posts_with_item = [];

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

        if (strpos($post_content, $id) !== false) {
            error_log('found id in content: ' . $id);
            // error log the post id
            error_log('post id: ' . $post_id);
            $posts_with_item[] = "Post ID: {$post_id}, Title: {$post_title}, Found in Content";
        }

        if (strpos($post_content, $url) !== false) {
            error_log('found url in content: ' . $url);
            error_log('post id: ' . $post_id);
            $posts_with_item[] = "Post ID: {$post_id}, Title: {$post_title}, Found in Content";
        }
    }
    wp_reset_postdata();

    return $posts_with_item;
}


function single_image_search($search_term, $search_id, $wpdb)
{

    $posts_with_image = [];

    // working
    if (!empty($search_id)) {
        // cs_parse_post_content_for_item($search_id);
        $posts_with_image = array_merge($posts_with_image, cs_parse_post_content_for_item($search_id, $search_term));
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
