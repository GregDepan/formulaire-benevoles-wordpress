<?php
/**
 * Fired during plugin activation
 */

class FB_Activator {
    
    public static function activate() {
        // Create custom database tables
        self::create_tables();
        
        // Check and fix unique constraint if needed
        self::fix_unique_constraint();
        
        // Register custom post types (for rewrite rules flush)
        self::register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        self::set_default_options();
    }
    
    /**
     * Auto-fix unique constraint on plugin load if incorrect
     * This ensures existing installations get the fix without manual migration
     */
    public static function fix_unique_constraint() {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (empty($table_exists)) {
            return; // Table doesn't exist yet
        }
        
        // Check current table structure
        $show_create = $wpdb->get_row("SHOW CREATE TABLE $table");
        if (empty($show_create)) {
            return;
        }
        
        $create_sql = $show_create->Create_Table;
        
        // Check if old (buggy) constraint exists
        if (strpos($create_sql, 'UNIQUE KEY `email_evenement`') !== false || 
            strpos($create_sql, 'UNIQUE KEY email_evenement') !== false) {
            
            error_log('FB Activator: Found old unique constraint, fixing...');
            
            // Drop old unique key
            $wpdb->query("ALTER TABLE $table DROP INDEX `email_evenement`");
            
            if ($wpdb->last_error) {
                error_log('FB Activator: Error dropping old key: ' . $wpdb->last_error);
                return;
            }
            
            // Add new correct unique key
            $wpdb->query("ALTER TABLE $table ADD UNIQUE KEY `email_event_creneau` (`email`, `evenement_id`, `creneau_id`)");
            
            if ($wpdb->last_error) {
                error_log('FB Activator: Error adding new key: ' . $wpdb->last_error);
                return;
            }
            
            error_log('FB Activator: Unique constraint fixed successfully!');
            
            // Set flag to show admin notice
            update_option('fb_constraint_fixed', true);
        }
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
            UNIQUE KEY email_event_creneau (email, evenement_id, creneau_id),
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
