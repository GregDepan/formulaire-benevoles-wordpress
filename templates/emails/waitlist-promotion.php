<?php
/**
 * Email template: Waitlist promotion
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
        .alert { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Place disponible !</h1>
        </div>
        <div class="content">
            <p>Bonjour <?php echo esc_html($entry->prenom . ' ' . $entry->nom); ?>,</p>
            
            <div class="alert">
                <strong>Bonne nouvelle !</strong> Une place s'est libérée pour le créneau auquel vous étiez en liste d'attente.
            </div>
            
            <p><strong>Stand :</strong> <?php echo esc_html($stand_name); ?></p>
            <p><strong>Créneau :</strong> <?php echo esc_html($creneau_title); ?></p>
            
            <p>Vous êtes automatiquement inscrit(e). Aucune action nécessaire de votre part.</p>
            
            <p>Si vous ne pouvez finalement pas être présent(e), merci d'annuler votre réservation pour libérer la place.</p>
            
            <p>À très bientôt !</p>
        </div>
        <div class="footer">
            <p>Formulaire Bénévoles</p>
        </div>
    </div>
</body>
</html>
