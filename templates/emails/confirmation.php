<?php
/**
 * Email template: Confirmation
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2271b1; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
        .slot { background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #2271b1; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        h1 { margin: 0; font-size: 24px; }
        h2 { color: #2271b1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirmation d'inscription</h1>
        </div>
        <div class="content">
            <p>Bonjour <?php echo esc_html($data['prenom'] . ' ' . $data['nom']); ?>,</p>
            
            <p>Merci pour votre inscription ! Voici le récapitulatif de vos créneaux :</p>
            
            <?php foreach ($results as $result) : ?>
                <div class="slot">
                    <strong><?php echo esc_html($result['stand_name']); ?></strong><br>
                    <?php echo esc_html($result['creneau_title']); ?>
                    <?php if (isset($result['waitlist']) && $result['waitlist']) : ?>
                        <br><em>(Liste d'attente - rang #<?php echo $result['rank']; ?>)</em>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <p>Vous pouvez modifier ou annuler vos réservations tant que la date limite n'est pas dépassée.</p>
            
            <p>À très bientôt !</p>
            <p><strong>L'équipe Formulaire Bénévoles</strong></p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
