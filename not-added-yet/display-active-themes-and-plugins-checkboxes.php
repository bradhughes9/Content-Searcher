<?php


defined('ABSPATH') or die('No script kiddies please!');

/**
 * Display checkboxes for active themes and plugins.
 */
function display_active_themes_and_plugins_checkboxes() {
    // Get active themes.
    $active_themes = wp_get_themes();

    // Get active plugins.
    $active_plugins = get_option('active_plugins');

    echo '<h3>Active Themes</h3>';
    // Display checkboxes for active themes.
    foreach ($active_themes as $theme) {
        echo '<label><input type="checkbox" name="selected_themes[]" value="' . esc_attr($theme->get_stylesheet()) . '">' . esc_html($theme->get('Name')) . '</label><br>';
    }

    echo '<h3>Active Plugins</h3>';
    // Display checkboxes for active plugins.
    foreach ($active_plugins as $plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        echo '<label><input type="checkbox" name="selected_plugins[]" value="' . esc_attr($plugin) . '">' . esc_html($plugin_data['Name']) . '</label><br>';
    }
}


