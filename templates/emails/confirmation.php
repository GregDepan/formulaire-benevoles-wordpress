<?php
/**
 * Email template: Confirmation
 */

if (!defined('ABSPATH')) exit;

// Extract email data
$data = $email_data['data'];
$slots_summary = $email_data['slots_summary'];
$event_name = $email_data['event_name'];
$custom_content = $email_data['custom_content'];
$custom_signature = $email_data['custom_signature'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #d32f2f; color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 30px 20px; }
        .slot { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #d32f2f; }
        .slot strong { color: #d32f2f; font-size: 16px; display: block; margin-bottom: 5px; }
        .slot .time { color: #666; }
        .waitlist { background: #fff3cd; border-left-color: #ffc107; }
        .footer { background: #f4f4f4; text-align: center; padding: 20px; color: #666; font-size: 12px; }
        h2 { color: #d32f2f; margin-top: 0; }
        .thank-you { font-size: 18px; color: #2e7d32; margin-bottom: 20px; }
        .custom-message { background: #fff9c4; padding: 15px; border-radius: 6px; border-left: 4px solid #fbc02d; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Confirmation d'inscription</h1>
        </div>
        <div class="content">
            <p class="thank-you">Bonjour <?php echo esc_html($data['prenom'] . ' ' . $data['nom']); ?>,</p>
            
            <?php if (!empty($custom_content)) : ?>
                <div class="custom-message">
                    <?php 
                    // Replace variables in custom content
                    $message = str_replace(
                        array('{prenom}', '{nom}', '{event_name}'),
                        array(esc_html($data['prenom']), esc_html($data['nom']), esc_html($event_name)),
                        esc_html($custom_content)
                    );
                    echo nl2br($message);
                    ?>
                </div>
            <?php else: ?>
                <p>Merci beaucoup pour votre inscription à <strong><?php echo esc_html($event_name); ?></strong> ! 🙏</p>
            <?php endif; ?>
            
            <p>Voici le récapitulatif de vos créneaux :</p>
            
            <?php foreach ($slots_summary as $slot) : ?>
                <div class="slot <?php echo $slot['waitlist'] ? 'waitlist' : ''; ?>">
                    <strong><?php echo esc_html($slot['stand_name']); ?></strong>
                    <span class="time">📅 <?php echo esc_html($slot['creneau_title']); ?></span>
                    <?php if ($slot['waitlist']) : ?>
                        <br><em style="color: #f57c00;">⏳ Liste d'attente - rang #<?php echo $slot['rank']; ?></em>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <p style="margin-top: 25px; padding: 15px; background: #e8f5e9; border-radius: 6px; border-left: 4px solid #2e7d32;">
                <strong>ℹ️ Information :</strong><br>
                Vous pouvez modifier ou annuler vos réservations tant que la date limite n'est pas dépassée.
            </p>
            
            <p style="margin-top: 25px;">
                À très bientôt !<br>
                <strong><?php echo !empty($custom_signature) ? esc_html($custom_signature) : 'L\'équipe Dépanordi Bordeaux'; ?></strong>
            </p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>© <?php echo date('Y'); ?> Dépanordi Bordeaux</p>
        </div>
    </div>
</body>
</html>
