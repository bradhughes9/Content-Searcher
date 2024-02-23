<?php

// Add security
if (!defined('ABSPATH')) {
    die;
}

function render_template_selector_tab () {
    ?>
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
    <?php
}

function single_template_search($search_term, $wpdb)
{
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
    return $results;
}
