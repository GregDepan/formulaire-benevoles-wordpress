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
        $subject = 'Confirmation de votre inscription - Formulaire Bénévoles';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('fb_email_from_name', 'Formulaire Bénévoles') . ' <' . get_option('admin_email', '') . '>',
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
