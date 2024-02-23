<?php

function find_media_in_attachments() {
    global $wpdb;
    $media_items = [];
    error_log(print_r($media_items, true));
    $attachment_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment'");

    foreach ($attachment_ids as $attachment_id) {
        $post_title = get_the_title($attachment_id);
        $edit_link = get_edit_post_link($attachment_id);
        $media_items[] = "Attachment ID: {$attachment_id}, Title: {$post_title}, Found in Media Library";
    }



    return $media_items;
}


function find_media_in_theme($selected_themes) {
    $media_items = [];
    error_log(print_r($media_items, true));

    foreach ($selected_themes as $theme_slug) {
        $theme = wp_get_theme($theme_slug);
        $theme_directory = $theme->get_stylesheet_directory();

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_directory));
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $contents = file_get_contents($file->getRealPath());
                preg_match_all('/(src="([^"]+\.(jpg|jpeg|png|gif))")/i', $contents, $matches);
                foreach ($matches[2] as $match) {
                    if (!in_array($match, $media_items)) {
                        $media_items[] = "Hardcoded Media: {$match}, Found in Theme File: {$file->getRealPath()}";
                    }
                }
            }
        }
    }


    return $media_items;
}


function find_media_in_plugins($selected_plugins) {
    $media_items = [];
    error_log(print_r($media_items, true));

    foreach ($selected_plugins as $plugin_path) {
        $plugin_directory = WP_PLUGIN_DIR . '/' . dirname($plugin_path);

        if (is_dir($plugin_directory)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_directory));
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $contents = file_get_contents($file->getRealPath());
                    preg_match_all('/(src="([^"]+\.(jpg|jpeg|png|gif))")/i', $contents, $matches);
                    foreach ($matches[2] as $match) {
                        if (!in_array($match, $media_items)) {
                            $media_items[] = "Hardcoded Media: {$match}, Found in Plugin File: {$file->getRealPath()}";
                        }
                    }
                }
            }
        }
    }



    return $media_items;
}
