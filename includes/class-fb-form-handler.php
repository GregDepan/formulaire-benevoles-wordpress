<?php
/**
 * Handle form submissions and AJAX requests
 */

class FB_Form_Handler {
    
    /**
     * Constructor - register hooks
     */
    public function __construct() {
        // Handle direct form POST submission
        add_action('template_redirect', array($this, 'handle_direct_form_submission'));
        
        add_action('wp_ajax_fb_submit_inscription', array($this, 'handle_inscription'));
        add_action('wp_ajax_nopriv_fb_submit_inscription', array($this, 'handle_inscription'));
        
        add_action('wp_ajax_fb_check_slot_availability', array($this, 'check_slot_availability'));
        add_action('wp_ajax_nopriv_fb_check_slot_availability', array($this, 'check_slot_availability'));
        
        add_action('wp_ajax_fb_get_profile', array($this, 'get_profile'));
        add_action('wp_ajax_nopriv_fb_get_profile', array($this, 'get_profile'));
        
        add_action('wp_ajax_fb_modify_reservation', array($this, 'modify_reservation'));
        add_action('wp_ajax_nopriv_fb_modify_reservation', array($this, 'modify_reservation'));
        
        add_action('wp_ajax_fb_cancel_reservation', array($this, 'cancel_reservation'));
        add_action('wp_ajax_nopriv_fb_cancel_reservation', array($this, 'cancel_reservation'));
    }
    
    /**
     * Handle inscription submission
     */
    public function handle_inscription() {
        check_ajax_referer('fb_public_nonce', 'nonce');
        
        // Validate required fields
        $required = array('nom', 'prenom', 'email', 'telephone', 'evenement_id', 'creneaux');
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => "Le champ $field est obligatoire"));
            }
        }
        
        $data = array(
            'nom' => sanitize_text_field($_POST['nom']),
            'prenom' => sanitize_text_field($_POST['prenom']),
            'email' => sanitize_email($_POST['email']),
            'telephone' => sanitize_text_field($_POST['telephone']),
            'evenement_id' => intval($_POST['evenement_id']),
            'creneaux' => array_map('intval', $_POST['creneaux']),
            'create_account' => isset($_POST['create_account']) && $_POST['create_account'] === '1',
        );
        
        // Validate email format
        if (!is_email($data['email'])) {
            wp_send_json_error(array('message' => 'Email invalide'));
        }
        
        // Check if already registered for this event
        if ($this->is_already_registered($data['email'], $data['evenement_id'])) {
            wp_send_json_error(array('message' => 'Vous êtes déjà inscrit à cet événement'));
        }
        
        // Check for time conflicts
        $conflicts = $this->check_time_conflicts($data['creneaux']);
        if (!empty($conflicts)) {
            wp_send_json_error(array(
                'message' => 'Certains créneaux se chevauchent',
                'conflicts' => $conflicts
            ));
        }
        
        // Process each selected slot
        $results = array();
        foreach ($data['creneaux'] as $creneau_id) {
            $result = $this->process_single_inscription($data, $creneau_id);
            $results[] = $result;
        }
        
        // Check if all slots were successful
        $all_success = !in_array(false, array_column($results, 'success'));
        
        if ($all_success) {
            // Send confirmation email
            $this->send_confirmation_email($data, $results);
            
            // Create WordPress account if requested
            if ($data['create_account']) {
                $this->create_wp_account($data);
            }
            
            wp_send_json_success(array(
                'message' => 'Inscription réussie',
                'results' => $results
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Certaines inscriptions ont échoué',
                'results' => $results
            ));
        }
    }
    
    /**
     * Handle direct form POST submission (non-AJAX)
     */
    public function handle_direct_form_submission() {
        if (!isset($_POST['fb_action']) || $_POST['fb_action'] !== 'register') {
            return;
        }
        
        // Debug log
        error_log('FB Form Submit - Action: ' . $_POST['fb_action']);
        error_log('FB Form Submit - POST keys: ' . implode(', ', array_keys($_POST)));
        
        // Debug creneaux
        if (isset($_POST['fb_creneaux'])) {
            error_log('FB Form Submit - Crenaux RAW: ' . print_r($_POST['fb_creneaux'], true));
            error_log('FB Form Submit - Crenaux count: ' . count($_POST['fb_creneaux']));
        } else {
            error_log('FB Form Submit - NO CRENEAUX IN POST!');
        }
        
        if (!isset($_POST['fb_nonce']) || !wp_verify_nonce($_POST['fb_nonce'], 'fb_register_volunteer')) {
            error_log('FB Form Submit - Nonce failed');
            wp_die('Erreur de sécurité', 'Erreur', array('response' => 403));
        }
        
        // Validate required fields
        $required = array('fb_email', 'fb_nom', 'fb_prenom', 'fb_telephone', 'fb_event_id', 'fb_creneaux');
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                error_log('FB Form Submit - Missing field: ' . $field);
                $this->redirect_with_error('Tous les champs obligatoires doivent être remplis');
                return;
            }
        }
        
        $data = array(
            'nom' => sanitize_text_field($_POST['fb_nom']),
            'prenom' => sanitize_text_field($_POST['fb_prenom']),
            'email' => sanitize_email($_POST['fb_email']),
            'telephone' => sanitize_text_field($_POST['fb_telephone']),
            'event_id' => intval($_POST['fb_event_id']),
            'creneaux' => array_map('intval', $_POST['fb_creneaux']),
        );
        
        error_log('FB Form Submit - Data: ' . json_encode($data));
        
        // Validate email format
        if (!is_email($data['email'])) {
            $this->redirect_with_error('Email invalide');
            return;
        }
        
        // Check if already registered for this event - allow modification before deadline
        $existing = $this->get_existing_inscriptions($data['email'], $data['event_id']);
        $date_limite = get_post_meta($data['event_id'], '_fb_date_limite', true);
        $can_modify = $date_limite && strtotime($date_limite) > time();
        
        if (!empty($existing) && !$can_modify) {
            $this->redirect_with_error('Vous êtes déjà inscrit à cet événement et la date limite est passée');
            return;
        }
        
        // If user already registered and can modify, delete old inscriptions first
        if (!empty($existing) && $can_modify) {
            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'fb_inscriptions', array(
                'email' => $data['email'],
                'evenement_id' => $data['event_id']
            ));
        }
        
        // Check for exclusion group conflicts
        $conflict = $this->check_exclusion_conflicts($data['creneaux']);
        if ($conflict['has_conflict']) {
            $this->redirect_with_error('Conflit de créneaux: Vous ne pouvez pas choisir "' . $conflict['creneau1'] . '" et "' . $conflict['creneau2'] . '" car ils appartiennent au même groupe d\'exclusion ("' . $conflict['group'] . '")');
            return;
        }
        
        // Process each selected slot
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        $success_count = 0;
        $errors = array();
        $results = array();
        
        foreach ($data['creneaux'] as $creneau_id) {
            $creneau = get_post($creneau_id);
            if (!$creneau) {
                $errors[] = "Créneau $creneau_id invalide";
                continue;
            }
            
            $stand_id = wp_get_post_parent_id($creneau_id);
            
            $result = $wpdb->insert($table, array(
                'evenement_id' => $data['event_id'],
                'stand_id' => $stand_id,
                'creneau_id' => $creneau_id,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'statut' => 'confirmed',
            ));
            
            if ($result) {
                $success_count++;
                // Build result array for email
                $results[] = array(
                    'success' => true,
                    'stand_name' => get_the_title($stand_id),
                    'creneau_title' => get_the_title($creneau_id),
                    'waitlist' => false,
                );
                error_log('FB Form Submit - Inscription created: ' . $wpdb->insert_id);
            } else {
                error_log('FB Form Submit - Insert failed: ' . $wpdb->last_error);
                $errors[] = $wpdb->last_error;
            }
        }
        
        error_log('FB Form Submit - Success count: ' . $success_count . ', Errors: ' . implode(', ', $errors));
        
        if ($success_count > 0) {
            // Send confirmation email
            $this->send_confirmation_email($data, $results);
            
            // Redirect with success
            $redirect_url = add_query_arg('success', '1', get_permalink($data['event_id']));
            wp_redirect($redirect_url);
            exit;
        } else {
            $this->redirect_with_error('Une erreur est survenue lors de l\'inscription: ' . implode(', ', $errors));
        }
    }
    
    /**
     * Redirect with error message
     */
    private function redirect_with_error($message) {
        $event_id = isset($_POST['fb_event_id']) ? intval($_POST['fb_event_id']) : 0;
        $redirect_url = add_query_arg('error', urlencode($message), get_permalink($event_id));
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Check for exclusion group conflicts among selected creneaux
     * Supports multiple groups per creneau (comma-separated)
     * Returns array with has_conflict flag and details if conflict found
     */
    private function check_exclusion_conflicts($creneaux_ids) {
        $selected_groups = array(); // group_name => creneau_id
        
        foreach ($creneaux_ids as $creneau_id) {
            $group_string = get_post_meta($creneau_id, '_fb_exclusion_group', true);
            
            // Skip if no exclusion group defined
            if (empty($group_string)) {
                continue;
            }
            
            // Parse multiple groups (comma-separated)
            $groups = array_map('trim', explode(',', $group_string));
            
            foreach ($groups as $group) {
                if (empty($group)) continue;
                
                if (isset($selected_groups[$group])) {
                    // Conflict found!
                    return array(
                        'has_conflict' => true,
                        'group' => $group,
                        'creneau1' => get_the_title($selected_groups[$group]),
                        'creneau2' => get_the_title($creneau_id),
                    );
                }
                
                $selected_groups[$group] = $creneau_id;
            }
        }
        
        return array('has_conflict' => false);
    }
    
    /**
     * Process single inscription
     */
    private function process_single_inscription($data, $creneau_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        // Get slot info
        $creneau = get_post($creneau_id);
        if (!$creneau) {
            return array('success' => false, 'error' => 'Créneau invalide');
        }
        
        $stand_id = get_post_meta($creneau_id, '_fb_stand_id', true);
        $evenement_id = get_post_meta($stand_id, '_fb_evenement_id', true);
        
        // Check quota
        $quota = get_post_meta($stand_id, '_fb_quota_par_creneau', true);
        if (!$quota) $quota = 5; // Default quota
        
        $current_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE creneau_id = %d AND statut = 'confirmed'",
            $creneau_id
        ));
        
        if ($current_count >= $quota) {
            // Add to waitlist
            return $this->add_to_waitlist($data, $creneau_id, $stand_id, $evenement_id);
        }
        
        // Insert inscription
        $token = wp_generate_password(64, false);
        $inserted = $wpdb->insert($table, array(
            'evenement_id' => $evenement_id,
            'stand_id' => $stand_id,
            'creneau_id' => $creneau_id,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'statut' => 'confirmed',
            'token' => $token,
        ));
        
        if ($inserted === false) {
            return array('success' => false, 'error' => 'Erreur base de données');
        }
        
        $inscription_id = $wpdb->insert_id;
        
        // Log for stats
        $this->log_action('inscription', $evenement_id, $stand_id, $creneau_id, $inscription_id);
        
        return array(
            'success' => true,
            'inscription_id' => $inscription_id,
            'token' => $token,
            'stand_name' => get_the_title($stand_id),
            'creneau_title' => get_the_title($creneau_id),
        );
    }
    
    /**
     * Add to waitlist
     */
    private function add_to_waitlist($data, $creneau_id, $stand_id, $evenement_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_waitlist';
        
        $token = wp_generate_password(64, false);
        
        // Get current max rank for this slot
        $max_rank = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(rang) FROM $table WHERE creneau_id = %d",
            $creneau_id
        ));
        $rank = ($max_rank ? $max_rank : 0) + 1;
        
        $inserted = $wpdb->insert($table, array(
            'evenement_id' => $evenement_id,
            'stand_id' => $stand_id,
            'creneau_id' => $creneau_id,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'rang' => $rank,
            'statut' => 'pending',
            'token' => $token,
        ));
        
        if ($inserted === false) {
            return array('success' => false, 'error' => 'Erreur liste d\'attente');
        }
        
        return array(
            'success' => true,
            'waitlist' => true,
            'rank' => $rank,
            'token' => $token,
        );
    }
    
    /**
     * Check if email already registered for event
     */
    private function is_already_registered($email, $evenement_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE email = %s AND evenement_id = %d AND statut = 'confirmed'",
            $email,
            $evenement_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get existing inscriptions for email/event
     */
    private function get_existing_inscriptions($email, $evenement_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s AND evenement_id = %d AND statut = 'confirmed'",
            $email,
            $evenement_id
        ));
    }
    
    /**
     * Check for time conflicts between selected slots
     */
    private function check_time_conflicts($creneau_ids) {
        $conflicts = array();
        $slots = array();
        
        foreach ($creneau_ids as $creneau_id) {
            $start = get_post_meta($creneau_id, '_fb_heure_debut', true);
            $end = get_post_meta($creneau_id, '_fb_heure_fin', true);
            
            if (!$start || !$end) continue;
            
            $slots[] = array(
                'id' => $creneau_id,
                'start' => strtotime($start),
                'end' => strtotime($end),
                'title' => get_the_title($creneau_id),
            );
        }
        
        // Check overlaps
        for ($i = 0; $i < count($slots); $i++) {
            for ($j = $i + 1; $j < count($slots); $j++) {
                if ($this->slots_overlap($slots[$i], $slots[$j])) {
                    $conflicts[] = array(
                        'slot1' => $slots[$i]['title'],
                        'slot2' => $slots[$j]['title'],
                    );
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check if two slots overlap
     */
    private function slots_overlap($slot1, $slot2) {
        return ($slot1['start'] < $slot2['end'] && $slot2['start'] < $slot1['end']);
    }
    
    /**
     * Check slot availability (AJAX)
     */
    public function check_slot_availability() {
        check_ajax_referer('fb_public_nonce', 'nonce');
        
        $creneau_id = intval($_POST['creneau_id']);
        $stand_id = get_post_meta($creneau_id, '_fb_stand_id', true);
        
        $quota = get_post_meta($stand_id, '_fb_quota_par_creneau', true);
        if (!$quota) $quota = 5;
        
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE creneau_id = %d AND statut = 'confirmed'",
            $creneau_id
        ));
        
        $available = $quota - $current;
        $is_full = $available <= 0;
        
        wp_send_json_success(array(
            'available' => $available,
            'quota' => $quota,
            'current' => $current,
            'is_full' => $is_full,
        ));
    }
    
    /**
     * Get user profile by email/token
     */
    public function get_profile() {
        check_ajax_referer('fb_public_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $token = sanitize_text_field($_POST['token']);
        
        if (!$email && !$token) {
            wp_send_json_error(array('message' => 'Email ou token requis'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        $where = '1=1';
        $params = array();
        
        if ($email) {
            $where .= ' AND email = %s';
            $params[] = $email;
        }
        if ($token) {
            $where .= ' AND token = %s';
            $params[] = $token;
        }
        
        $inscriptions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where",
            $params
        ));
        
        if (empty($inscriptions)) {
            wp_send_json_error(array('message' => 'Aucune réservation trouvée'));
        }
        
        // Format response
        $formatted = array();
        foreach ($inscriptions as $ins) {
            $formatted[] = array(
                'id' => $ins->id,
                'stand_name' => get_the_title($ins->stand_id),
                'creneau_title' => get_the_title($ins->creneau_id),
                'creneau_id' => $ins->creneau_id,
                'date_inscription' => $ins->date_inscription,
                'statut' => $ins->statut,
                'token' => $ins->token,
            );
        }
        
        wp_send_json_success(array('reservations' => $formatted));
    }
    
    /**
     * Modify reservation
     */
    public function modify_reservation() {
        check_ajax_referer('fb_public_nonce', 'nonce');
        
        $inscription_id = intval($_POST['inscription_id']);
        $new_creneau_id = intval($_POST['new_creneau_id']);
        $token = sanitize_text_field($_POST['token']);
        
        // Verify token
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        $inscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND token = %s",
            $inscription_id,
            $token
        ));
        
        if (!$inscription) {
            wp_send_json_error(array('message' => 'Réservation non trouvée'));
        }
        
        // Check modification deadline
        $evenement_id = $inscription->evenement_id;
        $delai = get_post_meta($evenement_id, '_fb_delai_inscription', true);
        
        if ($delai && strtotime($delai) < time()) {
            wp_send_json_error(array('message' => 'Délai de modification dépassé'));
        }
        
        // Check new slot availability
        $stand_id = get_post_meta($new_creneau_id, '_fb_stand_id', true);
        $quota = get_post_meta($stand_id, '_fb_quota_par_creneau', true);
        if (!$quota) $quota = 5;
        
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE creneau_id = %d AND statut = 'confirmed'",
            $new_creneau_id
        ));
        
        if ($current >= $quota) {
            wp_send_json_error(array('message' => 'Ce créneau est complet'));
        }
        
        // Update reservation
        $wpdb->update($table, array(
            'creneau_id' => $new_creneau_id,
            'stand_id' => $stand_id,
            'date_modification' => current_time('mysql'),
        ), array('id' => $inscription_id));
        
        $this->log_action('modification', $evenement_id, $stand_id, $new_creneau_id, $inscription_id);
        
        wp_send_json_success(array('message' => 'Réservation modifiée'));
    }
    
    /**
     * Cancel reservation
     */
    public function cancel_reservation() {
        check_ajax_referer('fb_public_nonce', 'nonce');
        
        $inscription_id = intval($_POST['inscription_id']);
        $token = sanitize_text_field($_POST['token']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        $inscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND token = %s",
            $inscription_id,
            $token
        ));
        
        if (!$inscription) {
            wp_send_json_error(array('message' => 'Réservation non trouvée'));
        }
        
        // Check cancellation deadline
        $evenement_id = $inscription->evenement_id;
        $delai = get_post_meta($evenement_id, '_fb_delai_inscription', true);
        
        if ($delai && strtotime($delai) < time()) {
            wp_send_json_error(array('message' => 'Délai d\'annulation dépassé'));
        }
        
        // Mark as cancelled (don't delete for stats)
        $wpdb->update($table, array(
            'statut' => 'cancelled',
        ), array('id' => $inscription_id));
        
        $this->log_action('annulation', $evenement_id, $inscription->stand_id, $inscription->creneau_id, $inscription_id);
        
        // Promote someone from waitlist if applicable
        $this->promote_from_waitlist($inscription->creneau_id);
        
        wp_send_json_success(array('message' => 'Réservation annulée'));
    }
    
    /**
     * Promote from waitlist
     */
    private function promote_from_waitlist($creneau_id) {
        global $wpdb;
        $waitlist_table = $wpdb->prefix . 'fb_waitlist';
        $inscriptions_table = $wpdb->prefix . 'fb_inscriptions';
        
        // Get first person in line
        $waitlist_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $waitlist_table WHERE creneau_id = %d AND statut = 'pending' ORDER BY rang ASC LIMIT 1",
            $creneau_id
        ));
        
        if (!$waitlist_entry) return;
        
        // Create inscription
        $token = wp_generate_password(64, false);
        $wpdb->insert($inscriptions_table, array(
            'evenement_id' => $waitlist_entry->evenement_id,
            'stand_id' => $waitlist_entry->stand_id,
            'creneau_id' => $creneau_id,
            'nom' => $waitlist_entry->nom,
            'prenom' => $waitlist_entry->prenom,
            'email' => $waitlist_entry->email,
            'telephone' => $waitlist_entry->telephone,
            'statut' => 'confirmed',
            'token' => $token,
        ));
        
        // Update waitlist entry
        $wpdb->update($waitlist_table, array(
            'statut' => 'promoted',
        ), array('id' => $waitlist_entry->id));
        
        // Send promotion email
        $this->send_waitlist_promotion_email($waitlist_entry);
    }
    
    /**
     * Log action for stats
     */
    private function log_action($action, $evenement_id, $stand_id = null, $creneau_id = null, $inscription_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_stats_logs';
        
        $wpdb->insert($table, array(
            'action' => $action,
            'evenement_id' => $evenement_id,
            'stand_id' => $stand_id,
            'creneau_id' => $creneau_id,
            'inscription_id' => $inscription_id,
            'timestamp' => current_time('mysql'),
        ));
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($data, $results) {
        // Implementation in FB_Emails class
        $emails = new FB_Emails();
        $emails->send_confirmation($data, $results);
    }
    
    /**
     * Send waitlist promotion email
     */
    private function send_waitlist_promotion_email($waitlist_entry) {
        $emails = new FB_Emails();
        $emails->send_waitlist_promotion($waitlist_entry);
    }
    
    /**
     * Create WordPress account
     */
    private function create_wp_account($data) {
        // Check if user exists
        $user_id = email_exists($data['email']);
        if ($user_id) return;
        
        // Generate random password
        $password = wp_generate_password(12, true);
        
        $user_id = wp_create_user($data['email'], $password, $data['email']);
        
        if (is_wp_error($user_id)) return;
        
        // Set display name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $data['prenom'] . ' ' . $data['nom'],
            'first_name' => $data['prenom'],
            'last_name' => $data['nom'],
        ));
        
        // Send password email
        wp_new_user_notification($user_id, null, 'both');
    }
}
