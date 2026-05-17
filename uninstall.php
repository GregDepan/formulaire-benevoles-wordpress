<?php
/**
 * Fired when the plugin is uninstalled (deleted from WordPress)
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop custom tables
$tables = array(
    $wpdb->prefix . 'fb_inscriptions',
    $wpdb->prefix . 'fb_waitlist',
    $wpdb->prefix . 'fb_stats_logs',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete all plugin options
$options = array(
    'fb_version',
    'fb_default_slot_duration',
    'fb_allow_modifications',
    'fb_modification_deadline_days',
    'fb_email_from_name',
    'fb_badge_format',
);

foreach ($options as $option) {
    delete_option($option);
}

// Delete all custom post types data
$post_types = array('fb_evenement', 'fb_stand', 'fb_creneau');

foreach ($post_types as $post_type) {
    $posts = get_posts(array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'any',
    ));
    
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}
