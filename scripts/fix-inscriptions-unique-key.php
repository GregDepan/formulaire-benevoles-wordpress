#!/usr/bin/env php
<?php
/**
 * Migration script to fix unique constraint on wp_fb_inscriptions table
 * 
 * Usage: wp cli eval --file=fix-inscriptions-unique-key.php
 * Or run directly: php fix-inscriptions-unique-key.php (with WordPress loaded)
 */

// Prevent direct execution without WordPress
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress context');
}

global $wpdb;
$table = $wpdb->prefix . 'fb_inscriptions';

echo "🔧 Fixing unique constraint on $table...\n\n";

// Step 1: Check current table structure
echo "1. Checking current table structure...\n";
$show_create = $wpdb->get_row("SHOW CREATE TABLE $table");
if (empty($show_create)) {
    echo "❌ Table $table not found!\n";
    exit(1);
}

$create_sql = $show_create->Create_Table;
echo "Current structure:\n";
echo substr($create_sql, 0, 500) . "...\n\n";

// Step 2: Check if old unique key exists
if (strpos($create_sql, 'UNIQUE KEY `email_evenement`') !== false) {
    echo "2. ⚠️  Found old UNIQUE KEY `email_evenement` (email, evenement_id)\n";
    echo "   This prevents multiple registrations per email per event!\n\n";
    
    // Step 3: Drop old unique key
    echo "3. Dropping old unique key...\n";
    $wpdb->query("ALTER TABLE $table DROP INDEX `email_evenement`");
    if ($wpdb->last_error) {
        echo "❌ Error dropping old key: " . $wpdb->last_error . "\n";
        exit(1);
    }
    echo "✅ Old unique key dropped\n\n";
    
    // Step 4: Add new unique key with creneau_id
    echo "4. Adding new UNIQUE KEY `email_event_creneau` (email, evenement_id, creneau_id)...\n";
    $wpdb->query("ALTER TABLE $table ADD UNIQUE KEY `email_event_creneau` (`email`, `evenement_id`, `creneau_id`)");
    if ($wpdb->last_error) {
        echo "❌ Error adding new key: " . $wpdb->last_error . "\n";
        exit(1);
    }
    echo "✅ New unique key added\n\n";
    
    // Step 5: Verify the change
    echo "5. Verifying new structure...\n";
    $show_create = $wpdb->get_row("SHOW CREATE TABLE $table");
    $new_create_sql = $show_create->Create_Table;
    
    if (strpos($new_create_sql, 'UNIQUE KEY `email_event_creneau`') !== false) {
        echo "✅ SUCCESS! Unique constraint now allows multiple creneaux per email per event\n\n";
        echo "New constraint: (email, evenement_id, creneau_id)\n";
        echo "This means: Same email can register for multiple creneaux in same event ✅\n";
    } else {
        echo "❌ Verification failed - new key not found\n";
        exit(1);
    }
    
} elseif (strpos($create_sql, 'UNIQUE KEY `email_event_creneau`') !== false) {
    echo "2. ✅ Table already has correct UNIQUE KEY `email_event_creneau`\n";
    echo "   No migration needed!\n\n";
    
} else {
    echo "2. ⚠️  No unique key found on (email, evenement_id)\n";
    echo "   Adding new UNIQUE KEY `email_event_creneau`...\n";
    
    $wpdb->query("ALTER TABLE $table ADD UNIQUE KEY `email_event_creneau` (`email`, `evenement_id`, `creneau_id`)");
    if ($wpdb->last_error) {
        echo "❌ Error adding key: " . $wpdb->last_error . "\n";
        exit(1);
    }
    echo "✅ Unique key added\n\n";
}

// Step 6: Show current data stats
echo "6. Current data statistics:\n";
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
$unique_emails = $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM $table");
$unique_event_email = $wpdb->get_var("SELECT COUNT(DISTINCT CONCAT(email, '-', evenement_id)) FROM $table");

echo "   - Total inscriptions: $total\n";
echo "   - Unique emails: $unique_emails\n";
echo "   - Unique (email, event) combinations: $unique_event_email\n\n";

echo "🎉 Migration complete!\n";
