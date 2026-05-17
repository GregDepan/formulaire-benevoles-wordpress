<?php
/**
 * Stand details meta box
 */

if (!defined('ABSPATH')) exit;
?>

<div class="fb-meta-box">
    <p>
        <label for="fb_evenement_id"><strong>Événement parent</strong></label><br>
        <select name="fb_evenement_id" id="fb_evenement_id" class="regular-input">
            <?php
            $evenements = get_posts(array(
                'post_type' => 'fb_evenement',
                'numberposts' => -1,
                'post_status' => 'any',
            ));
            
            foreach ($evenements as $evenement) :
            ?>
                <option value="<?php echo $evenement->ID; ?>" 
                        <?php selected($evenement_id, $evenement->ID); ?>>
                    <?php echo esc_html(get_the_title($evenement)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    
    <p>
        <label for="fb_quota_par_creneau"><strong>Quota par créneau</strong></label><br>
        <input type="number" name="fb_quota_par_creneau" id="fb_quota_par_creneau" 
               value="<?php echo esc_attr($quota_par_creneau); ?>" min="1" class="small-text">
        <span class="description">Nombre maximum de bénévoles par créneau horaire</span>
    </p>
    
    <p>
        <label for="fb_couleur"><strong>Couleur</strong></label><br>
        <input type="color" name="fb_couleur" id="fb_couleur" 
               value="<?php echo esc_attr($couleur); ?>" class="color-picker">
    </p>
    
    <p>
        <label for="fb_description"><strong>Description</strong></label><br>
        <textarea name="fb_description" id="fb_description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
    </p>
</div>
