<?php
/**
 * Fired during plugin deactivation
 */

class FB_Deactivator {
    
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Optionally clean up tables on uninstall (not deactivation)
        // Tables are preserved so data isn't lost on accidental deactivation
    }
}
