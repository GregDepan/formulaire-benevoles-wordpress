<?php
/**
 * Event details meta box
 */

if (!defined('ABSPATH')) exit;
?>

<div class="fb-meta-box">
    <p>
        <label for="fb_date_debut"><strong>Date de début</strong></label><br>
        <input type="datetime-local" name="fb_date_debut" id="fb_date_debut" 
               value="<?php echo esc_attr($date_debut); ?>" class="regular-input">
    </p>
    
    <p>
        <label for="fb_date_fin"><strong>Date de fin</strong></label><br>
        <input type="datetime-local" name="fb_date_fin" id="fb_date_fin" 
               value="<?php echo esc_attr($date_fin); ?>" class="regular-input">
    </p>
    
    <p>
        <label for="fb_delai_inscription"><strong>Délai d'inscription (fin)</strong></label><br>
        <input type="datetime-local" name="fb_delai_inscription" id="fb_delai_inscription" 
               value="<?php echo esc_attr($delai_inscription); ?>" class="regular-input">
        <span class="description">Après cette date, les inscriptions et modifications sont fermées</span>
    </p>
    
    <p>
        <label for="fb_lieu"><strong>Lieu</strong></label><br>
        <input type="text" name="fb_lieu" id="fb_lieu" 
               value="<?php echo esc_attr($lieu); ?>" class="regular-input" placeholder="Ex: École Jules Ferry, Bordeaux">
    </p>
    
    <p>
        <label for="fb_description"><strong>Description</strong></label><br>
        <textarea name="fb_description" id="fb_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
        <span class="description">Cette description apparaît sur le formulaire public</span>
    </p>
    
    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">
    
    <h3>📧 Email de confirmation</h3>
    <p class="description">Personnalisez le message envoyé aux bénévoles après leur inscription. Laissez vide pour utiliser le message par défaut.</p>
    
    <p>
        <label for="fb_email_subject"><strong>Objet de l'email</strong></label><br>
        <input type="text" name="fb_email_subject" id="fb_email_subject" 
               value="<?php echo esc_attr($email_subject); ?>" class="regular-input" 
               placeholder="Confirmation de votre inscription - Kermesse">
    </p>
    
    <p>
        <label for="fb_email_content"><strong>Message de remerciement</strong></label><br>
        <textarea name="fb_email_content" id="fb_email_content" rows="5" class="large-text" 
                  placeholder="Merci beaucoup pour votre inscription à notre événement ! Votre aide nous est précieuse."><?php echo esc_textarea($email_content); ?></textarea>
        <span class="description">Ce message apparaît au-dessus du récapitulatif des créneaux. Variables disponibles : {prenom}, {nom}, {event_name}</span>
    </p>
    
    <p>
        <label for="fb_email_signature"><strong>Signature</strong></label><br>
        <input type="text" name="fb_email_signature" id="fb_email_signature" 
               value="<?php echo esc_attr($email_signature); ?>" class="regular-input" 
               placeholder="L'équipe Dépanordi Bordeaux">
    </p>
    
    <p>
        <label>
            <input type="checkbox" name="fb_archived" value="1" <?php checked($archived, 1); ?>>
            <strong>Archiver cet événement</strong>
        </label>
        <span class="description">Les événements archivés ne sont plus accessibles au public</span>
    </p>
</div>
