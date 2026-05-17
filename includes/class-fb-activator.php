<?php
/**
 * Fired during plugin activation
 */

class FB_Activator {
    
    public static function activate() {
        // Create custom database tables
        self::create_tables();
        
        // Register custom post types (for rewrite rules flush)
        self::register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        self::set_default_options();
    }
    
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Inscriptions table
        $table_inscriptions = $wpdb->prefix . 'fb_inscriptions';
        $sql_inscriptions = "CREATE TABLE $table_inscriptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            evenement_id bigint(20) UNSIGNED NOT NULL,
            stand_id bigint(20) UNSIGNED NOT NULL,
            creneau_id bigint(20) UNSIGNED NOT NULL,
            nom varchar(100) NOT NULL,
            prenom varchar(100) NOT NULL,
            email varchar(150) NOT NULL,
            telephone varchar(20) DEFAULT '',
            wp_user_id bigint(20) UNSIGNED DEFAULT NULL,
            date_inscription datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modification datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            statut varchar(20) NOT NULL DEFAULT 'confirmed',
            token varchar(64) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email_evenement (email, evenement_id),
            KEY evenement_id (evenement_id),
            KEY stand_id (stand_id),
            KEY creneau_id (creneau_id),
            KEY token (token)
        ) $charset_collate;";
        
        // Waitlist table
        $table_waitlist = $wpdb->prefix . 'fb_waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            evenement_id bigint(20) UNSIGNED NOT NULL,
            stand_id bigint(20) UNSIGNED NOT NULL,
            creneau_id bigint(20) UNSIGNED NOT NULL,
            nom varchar(100) NOT NULL,
            prenom varchar(100) NOT NULL,
            email varchar(150) NOT NULL,
            telephone varchar(20) DEFAULT '',
            date_demande datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            rang int(11) NOT NULL DEFAULT 0,
            statut varchar(20) NOT NULL DEFAULT 'pending',
            token varchar(64) NOT NULL,
            PRIMARY KEY  (id),
            KEY evenement_id (evenement_id),
            KEY stand_id (stand_id),
            KEY creneau_id (creneau_id),
            KEY token (token)
        ) $charset_collate;";
        
        // Stats logs table
        $table_stats = $wpdb->prefix . 'fb_stats_logs';
        $sql_stats = "CREATE TABLE $table_stats_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            evenement_id bigint(20) UNSIGNED NOT NULL,
            stand_id bigint(20) UNSIGNED DEFAULT NULL,
            creneau_id bigint(20) UNSIGNED DEFAULT NULL,
            inscription_id bigint(20) UNSIGNED DEFAULT NULL,
            action varchar(50) NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            meta_data text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY evenement_id (evenement_id),
            KEY action (action),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_inscriptions);
        dbDelta($sql_waitlist);
        dbDelta($sql_stats);
    }
    
    private static function register_post_types() {
        // Register temporarily to flush rewrite rules
        $evenement_args = array(
            'label' => 'Événements',
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'evenements'),
            'supports' => array('title', 'editor', 'custom-fields'),
            'show_in_admin_bar' => true,
        );
        register_post_type('fb_evenement', $evenement_args);
    }
    
    private static function set_default_options() {
        $defaults = array(
            'fb_version' => FB_VERSION,
            'fb_default_slot_duration' => 30, // minutes
            'fb_allow_modifications' => true,
            'fb_modification_deadline_days' => 2,
            'fb_email_from_name' => 'Formulaire Bénévoles',
            'fb_badge_format' => 'avery-l7163',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
