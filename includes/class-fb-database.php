<?php
/**
 * Define database functionality for custom tables.
 *
 * @package    Formulaire_Benevoles
 * @subpackage Formulaire_Benevoles/includes
 * @author     Grégory <gregory@depanordi-bordeaux.fr>
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database handler for custom tables.
 *
 * Manages custom table operations for volunteer registrations, waitlist, and stats.
 *
 * @since    1.0.0
 */
class Formulaire_Benevoles_Database {

    /**
     * Get table name with prefix.
     *
     * @since    1.0.0
     * @param    string    $table    Table name (without prefix).
     * @return   string              Full table name with prefix.
     */
    public static function get_table($table) {
        global $wpdb;
        return $wpdb->prefix . 'fb_' . $table;
    }

    /**
     * Get inscriptions table name.
     *
     * @since    1.0.0
     * @return   string    Table name.
     */
    public static function get_inscriptions_table() {
        return self::get_table('inscriptions');
    }

    /**
     * Get waitlist table name.
     *
     * @since    1.0.0
     * @return   string    Table name.
     */
    public static function get_waitlist_table() {
        return self::get_table('waitlist');
    }

    /**
     * Get stats logs table name.
     *
     * @since    1.0.0
     * @return   string    Table name.
     */
    public static function get_stats_logs_table() {
        return self::get_table('stats_logs');
    }

    /**
     * Get inscription by ID.
     *
     * @since    1.0.0
     * @param    int       $id    Inscription ID.
     * @return   object|false     Inscription data or false if not found.
     */
    public static function get_inscription($id) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get all inscriptions for an event.
     *
     * @since    1.0.0
     * @param    int       $event_id    Event ID.
     * @return   array                   Array of inscription objects.
     */
    public static function get_event_inscriptions($event_id) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE event_id = %d ORDER BY created_at DESC", $event_id));
    }

    /**
     * Get inscriptions for a specific stand and slot.
     *
     * @since    1.0.0
     * @param    int       $stand_id    Stand ID.
     * @param    int       $slot_id     Slot ID.
     * @return   array                   Array of inscription objects.
     */
    public static function get_stand_slot_inscriptions($stand_id, $slot_id) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE stand_id = %d AND slot_id = %d",
            $stand_id,
            $slot_id
        ));
    }

    /**
     * Count inscriptions for a stand and slot.
     *
     * @since    1.0.0
     * @param    int       $stand_id    Stand ID.
     * @param    int       $slot_id     Slot ID.
     * @return   int                    Number of inscriptions.
     */
    public static function count_inscriptions($stand_id, $slot_id) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE stand_id = %d AND slot_id = %d",
            $stand_id,
            $slot_id
        ));
    }

    /**
     * Insert new inscription.
     *
     * @since    1.0.0
     * @param    array     $data    Inscription data.
     * @return   int|false          Insertion ID or false on failure.
     */
    public static function insert_inscription($data) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        $data['created_at'] = current_time('mysql');
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Update inscription.
     *
     * @since    1.0.0
     * @param    int       $id      Inscription ID.
     * @param    array     $data    Data to update.
     * @return   int|false          Number of rows updated or false on failure.
     */
    public static function update_inscription($id, $data) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update($table, $data, array('id' => $id));
    }

    /**
     * Delete inscription.
     *
     * @since    1.0.0
     * @param    int       $id      Inscription ID.
     * @return   int|false          Number of rows deleted or false on failure.
     */
    public static function delete_inscription($id) {
        global $wpdb;
        $table = self::get_inscriptions_table();
        return $wpdb->delete($table, array('id' => $id));
    }

    /**
     * Get waitlist entries for a stand and slot.
     *
     * @since    1.0.0
     * @param    int       $stand_id    Stand ID.
     * @param    int       $slot_id     Slot ID.
     * @return   array                   Array of waitlist entries.
     */
    public static function get_waitlist_entries($stand_id, $slot_id) {
        global $wpdb;
        $table = self::get_waitlist_table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE stand_id = %d AND slot_id = %d ORDER BY position ASC",
            $stand_id,
            $slot_id
        ));
    }

    /**
     * Add to waitlist.
     *
     * @since    1.0.0
     * @param    array     $data    Waitlist entry data.
     * @return   int|false          Insertion ID or false on failure.
     */
    public static function add_to_waitlist($data) {
        global $wpdb;
        $table = self::get_waitlist_table();
        $data['created_at'] = current_time('mysql');
        
        // Get next position
        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM $table WHERE stand_id = %d AND slot_id = %d",
            $data['stand_id'],
            $data['slot_id']
        ));
        $data['position'] = ($max_position ? $max_position : 0) + 1;
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Remove from waitlist.
     *
     * @since    1.0.0
     * @param    int       $id      Waitlist entry ID.
     * @return   int|false          Number of rows deleted or false on failure.
     */
    public static function remove_from_waitlist($id) {
        global $wpdb;
        $table = self::get_waitlist_table();
        return $wpdb->delete($table, array('id' => $id));
    }

    /**
     * Log stats entry.
     *
     * @since    1.0.0
     * @param    array     $data    Stats log data.
     * @return   int|false          Insertion ID or false on failure.
     */
    public static function log_stats($data) {
        global $wpdb;
        $table = self::get_stats_logs_table();
        $data['logged_at'] = current_time('mysql');
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
}
