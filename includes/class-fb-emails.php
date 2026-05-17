<?php
/**
 * Handle all email notifications
 */

class FB_Emails {
    
    /**
     * Send confirmation email to volunteer
     */
    public function send_confirmation($data, $results) {
        $to = $data['email'];
        
        // Get event-specific email settings or use defaults
        $event_id = $data['event_id'];
        $event_name = get_the_title($event_id);
        
        // Get event dates
        $event_date = get_post_meta($event_id, '_fb_date_debut', true);
        if (empty($event_date)) {
            $event_date = get_post_field('post_date', $event_id);
        }
        $date_limite = get_post_meta($event_id, '_fb_date_limite', true);
        
        // Format dates
        $event_date_formatted = !empty($event_date) ? date_i18n('d/m/Y', strtotime($event_date)) : 'Non définie';
        $date_limite_formatted = !empty($date_limite) ? date_i18n('d/m/Y', strtotime($date_limite)) : 'Non définie';
        
        // Build creneaux summary text
        $creneaux_summary = array();
        foreach ($results as $result) {
            $creneaux_summary[] = $result['stand_name'] . ' - ' . $result['creneau_title'];
        }
        $creneaux_summary_text = implode("\n• ", $creneaux_summary);
        
        // Custom subject or default
        $custom_subject = get_post_meta($event_id, '_fb_email_subject', true);
        $subject = !empty($custom_subject) ? $custom_subject : 'Confirmation de votre inscription - ' . $event_name;
        
        // Custom content or default
        $custom_content = get_post_meta($event_id, '_fb_email_content', true);
        $custom_signature = get_post_meta($event_id, '_fb_email_signature', true);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('fb_email_from_name', 'Dépanordi Bordeaux') . ' <' . get_option('admin_email', '') . '>',
            'Reply-To: ' . get_option('admin_email', ''),
        );
        
        // Build slots summary
        $slots_summary = array();
        foreach ($results as $result) {
            $slots_summary[] = array(
                'stand_name' => $result['stand_name'],
                'creneau_title' => $result['creneau_title'],
                'waitlist' => isset($result['waitlist']) && $result['waitlist'],
                'rank' => isset($result['rank']) ? $result['rank'] : null,
            );
        }
        
        // Pass custom fields to template
        $email_data = array(
            'data' => $data,
            'slots_summary' => $slots_summary,
            'event_name' => $event_name,
            'event_date' => $event_date_formatted,
            'date_limite' => $date_limite_formatted,
            'creneaux_summary' => $creneaux_summary_text,
            'custom_content' => $custom_content,
            'custom_signature' => $custom_signature,
        );
        
        ob_start();
        include FB_PLUGIN_DIR . 'templates/emails/confirmation.php';
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send waitlist promotion email
     */
    public function send_waitlist_promotion($entry) {
        $to = $entry->email;
        $subject = 'Place disponible - Formulaire Bénévoles';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('fb_email_from_name', 'Formulaire Bénévoles') . ' <' . get_option('admin_email', '') . '>',
        );
        
        $stand_name = get_the_title($entry->stand_id);
        $creneau_title = get_the_title($entry->creneau_id);
        
        ob_start();
        include FB_PLUGIN_DIR . 'templates/emails/waitlist-promotion.php';
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send modification confirmation
     */
    public function send_modification_confirmation($inscription, $new_creneau) {
        $to = $inscription->email;
        $subject = 'Modification de votre réservation - Formulaire Bénévoles';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('fb_email_from_name', 'Formulaire Bénévoles') . ' <' . get_option('admin_email', '') . '>',
        );
        
        ob_start();
        include FB_PLUGIN_DIR . 'templates/emails/modification.php';
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send cancellation confirmation
     */
    public function send_cancellation_confirmation($inscription) {
        $to = $inscription->email;
        $subject = 'Annulation de votre réservation - Formulaire Bénévoles';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('fb_email_from_name', 'Formulaire Bénévoles') . ' <' . get_option('admin_email', '') . '>',
        );
        
        ob_start();
        include FB_PLUGIN_DIR . 'templates/emails/cancellation.php';
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, $headers);
    }
}
