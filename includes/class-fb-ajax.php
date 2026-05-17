<?php
/**
 * AJAX handlers for admin operations
 */

class FB_Ajax {
    
    public function __construct() {
        // Admin AJAX actions
        add_action('wp_ajax_fb_clone_event', array($this, 'clone_event'));
        add_action('wp_ajax_fb_add_stand', array($this, 'add_stand'));
        add_action('wp_ajax_fb_add_creneau', array($this, 'add_creneau'));
        add_action('wp_ajax_fb_delete_stand', array($this, 'delete_stand'));
        add_action('wp_ajax_fb_delete_creneau', array($this, 'delete_creneau'));
        add_action('wp_ajax_fb_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_fb_generate_badges', array($this, 'generate_badges'));
    }
    
    /**
     * Clone event with all stands and creneaux
     */
    public function clone_event() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $source_id = intval($_POST['source_id']);
        $new_title = sanitize_text_field($_POST['new_title']);
        
        // Get source event
        $source = get_post($source_id);
        if (!$source || $source->post_type !== 'fb_evenement') {
            wp_send_json_error(array('message' => 'Événement source invalide'));
        }
        
        // Create new event
        $new_event_id = wp_insert_post(array(
            'post_title' => $new_title,
            'post_type' => 'fb_evenement',
            'post_status' => 'draft',
            'post_content' => $source->post_content,
        ));
        
        if (is_wp_error($new_event_id)) {
            wp_send_json_error(array('message' => 'Erreur création événement'));
        }
        
        // Copy meta
        $meta_keys = array('_fb_date_debut', '_fb_date_fin', '_fb_delai_inscription', '_fb_lieu', '_fb_description');
        foreach ($meta_keys as $key) {
            $value = get_post_meta($source_id, $key, true);
            if ($value) {
                update_post_meta($new_event_id, $key, $value);
            }
        }
        
        // Copy stands
        $stands = get_posts(array(
            'post_type' => 'fb_stand',
            'numberposts' => -1,
            'meta_key' => '_fb_evenement_id',
            'meta_value' => $source_id,
        ));
        
        $cloned_stands = 0;
        foreach ($stands as $stand) {
            $new_stand_id = wp_insert_post(array(
                'post_title' => $stand->post_title,
                'post_type' => 'fb_stand',
                'post_status' => 'publish',
            ));
            
            if ($new_stand_id) {
                // Copy stand meta
                $stand_meta = array('_fb_evenement_id', '_fb_quota_par_creneau', '_fb_description', '_fb_couleur');
                foreach ($stand_meta as $key) {
                    $value = get_post_meta($stand->ID, $key, true);
                    if ($key === '_fb_evenement_id') {
                        $value = $new_event_id;
                    }
                    if ($value) {
                        update_post_meta($new_stand_id, $key, $value);
                    }
                }
                
                // Copy creneaux
                $creneaux = get_posts(array(
                    'post_type' => 'fb_creneau',
                    'numberposts' => -1,
                    'meta_key' => '_fb_stand_id',
                    'meta_value' => $stand->ID,
                ));
                
                foreach ($creneaux as $creneau) {
                    $new_creneau_id = wp_insert_post(array(
                        'post_title' => $creneau->post_title,
                        'post_type' => 'fb_creneau',
                        'post_status' => 'publish',
                    ));
                    
                    if ($new_creneau_id) {
                        // Copy creneau meta
                        $creneau_meta = array('_fb_stand_id', '_fb_heure_debut', '_fb_heure_fin', '_fb_quota_specifique');
                        foreach ($creneau_meta as $key) {
                            $value = get_post_meta($creneau->ID, $key, true);
                            if ($key === '_fb_stand_id') {
                                $value = $new_stand_id;
                            }
                            if ($value) {
                                update_post_meta($new_creneau_id, $key, $value);
                            }
                        }
                    }
                }
                
                $cloned_stands++;
            }
        }
        
        wp_send_json_success(array(
            'message' => 'Événement cloné avec succès',
            'new_event_id' => $new_event_id,
            'cloned_stands' => $cloned_stands,
            'edit_url' => get_edit_post_link($new_event_id, 'raw'),
        ));
    }
    
    /**
     * Add stand to event
     */
    public function add_stand() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $evenement_id = intval($_POST['evenement_id']);
        $nom = sanitize_text_field($_POST['nom']);
        $quota = intval($_POST['quota']);
        $couleur = sanitize_hex_color($_POST['couleur']);
        $description = sanitize_textarea_field($_POST['description']);
        
        $stand_id = wp_insert_post(array(
            'post_title' => $nom,
            'post_type' => 'fb_stand',
            'post_status' => 'publish',
        ));
        
        if ($stand_id) {
            update_post_meta($stand_id, '_fb_evenement_id', $evenement_id);
            update_post_meta($stand_id, '_fb_quota_par_creneau', $quota);
            update_post_meta($stand_id, '_fb_couleur', $couleur);
            update_post_meta($stand_id, '_fb_description', $description);
            
            wp_send_json_success(array(
                'stand_id' => $stand_id,
                'message' => 'Stand ajouté',
            ));
        }
        
        wp_send_json_error(array('message' => 'Erreur ajout stand'));
    }
    
    /**
     * Add creneau to stand
     */
    public function add_creneau() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $stand_id = intval($_POST['stand_id']);
        $heure_debut = sanitize_text_field($_POST['heure_debut']);
        $heure_fin = sanitize_text_field($_POST['heure_fin']);
        $quota_specifique = intval($_POST['quota_specifique']);
        
        // Generate title from times
        $title = $heure_debut . ' - ' . $heure_fin;
        
        $creneau_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'fb_creneau',
            'post_status' => 'publish',
        ));
        
        if ($creneau_id) {
            update_post_meta($creneau_id, '_fb_stand_id', $stand_id);
            update_post_meta($creneau_id, '_fb_heure_debut', $heure_debut);
            update_post_meta($creneau_id, '_fb_heure_fin', $heure_fin);
            
            if ($quota_specifique > 0) {
                update_post_meta($creneau_id, '_fb_quota_specifique', $quota_specifique);
            }
            
            wp_send_json_success(array(
                'creneau_id' => $creneau_id,
                'message' => 'Créneau ajouté',
            ));
        }
        
        wp_send_json_error(array('message' => 'Erreur ajout créneau'));
    }
    
    /**
     * Delete stand
     */
    public function delete_stand() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $stand_id = intval($_POST['stand_id']);
        
        // Delete associated creneaux first
        $creneaux = get_posts(array(
            'post_type' => 'fb_creneau',
            'numberposts' => -1,
            'meta_key' => '_fb_stand_id',
            'meta_value' => $stand_id,
        ));
        
        foreach ($creneaux as $creneau) {
            wp_delete_post($creneau->ID, true);
        }
        
        if (wp_delete_post($stand_id, true)) {
            wp_send_json_success(array('message' => 'Stand supprimé'));
        }
        
        wp_send_json_error(array('message' => 'Erreur suppression'));
    }
    
    /**
     * Delete creneau
     */
    public function delete_creneau() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $creneau_id = intval($_POST['creneau_id']);
        
        if (wp_delete_post($creneau_id, true)) {
            wp_send_json_success(array('message' => 'Créneau supprimé'));
        }
        
        wp_send_json_error(array('message' => 'Erreur suppression'));
    }
    
    /**
     * Export CSV - Format pivoté : une ligne par bénévole, une colonne par stand
     */
    public function export_csv() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $evenement_id = intval($_POST['evenement_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        // Récupérer toutes les inscriptions de l'événement
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, s.post_title AS stand_nom, c.post_title AS creneau_horaire
             FROM $table i
             LEFT JOIN {$wpdb->posts} s ON i.stand_id = s.ID
             LEFT JOIN {$wpdb->posts} c ON i.creneau_id = c.ID
             WHERE i.evenement_id = %d AND i.statut = 'confirmed'
             ORDER BY i.date_inscription, i.nom",
            $evenement_id
        ));
        
        // Pivoter les données : grouper par email
        $volunteers = array();
        foreach ($results as $row) {
            $email = $row->email;
            
            if (!isset($volunteers[$email])) {
                $volunteers[$email] = array(
                    'nom' => $row->nom,
                    'prenom' => $row->prenom,
                    'email' => $email,
                    'telephone' => $row->telephone,
                    'date_inscription' => $row->date_inscription,
                    'stands' => array()
                );
            }
            
            // Ajouter le créneau au stand correspondant
            $stand_nom = $row->stand_nom ?: 'Stand ' . $row->stand_id;
            if (!isset($volunteers[$email]['stands'][$stand_nom])) {
                $volunteers[$email]['stands'][$stand_nom] = array();
            }
            $volunteers[$email]['stands'][$stand_nom][] = $row->creneau_horaire;
        }
        
        // Récupérer la liste de tous les stands (pour avoir les colonnes dans le bon ordre)
        $stands = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title 
             FROM {$wpdb->posts} p 
             WHERE p.post_type = 'fb_stand' 
             AND p.post_parent = %d
             ORDER BY p.ID",
            $evenement_id
        ));
        
        // Generate CSV
        $filename = 'export-benevoles-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers : Horodateur, Nom, Prénom, Email, Téléphone, puis un colonne par stand
        $headers = array('Horodateur', 'Nom', 'Prénom', 'Email', 'Téléphone');
        foreach ($stands as $stand) {
            $headers[] = $stand->post_title ?: 'Stand ' . $stand->ID;
        }
        fputcsv($output, $headers);
        
        // Data : une ligne par bénévole
        foreach ($volunteers as $volunteer) {
            $row = array(
                date('d/m/Y H:i', strtotime($volunteer['date_inscription'])),
                $volunteer['nom'],
                $volunteer['prenom'],
                $volunteer['email'],
                $volunteer['telephone']
            );
            
            // Ajouter les horaires pour chaque stand
            foreach ($stands as $stand) {
                $stand_nom = $stand->post_title ?: 'Stand ' . $stand->ID;
                if (isset($volunteer['stands'][$stand_nom])) {
                    // Plusieurs créneaux séparés par des virgules
                    $row[] = implode(', ', $volunteer['stands'][$stand_nom]);
                } else {
                    $row[] = ''; // Cellule vide si pas inscrit à ce stand
                }
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Generate badges PDF
     */
    public function generate_badges() {
        check_ajax_referer('fb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $evenement_id = intval($_POST['evenement_id']);
        $stand_id = isset($_POST['stand_id']) ? intval($_POST['stand_id']) : 0;
        
        // This would use TCPDF or Dompdf
        // For now, return placeholder
        wp_send_json_success(array(
            'message' => 'Génération PDF non implémentée',
            'note' => 'Installer TCPDF ou Dompdf pour cette fonctionnalité',
        ));
    }
}
