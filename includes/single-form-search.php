<?php


function render_form_selector_tab()
{
    ?>
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
    <?php
}

// I dont know if this logic was fully working previously.
function single_form_search($search_type, $wpdb)
{
    $pages_with_form = array();
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
    return $results;
}
