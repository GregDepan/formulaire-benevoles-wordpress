<?php
/**
 * Event stats meta box
 */

if (!defined('ABSPATH')) exit;
?>

<div class="fb-stats-box">
    <div class="fb-stat-item">
        <span class="fb-stat-value"><?php echo $total; ?></span>
        <span class="fb-stat-label">Inscrits confirmés</span>
    </div>
    
    <div class="fb-stat-item">
        <span class="fb-stat-value"><?php echo $waitlist; ?></span>
        <span class="fb-stat-label">En liste d'attente</span>
    </div>
    
    <?php 
    // Calculate fill rate if stands exist
    $stands = get_posts(array(
        'post_type' => 'fb_stand',
        'numberposts' => -1,
        'meta_key' => '_fb_evenement_id',
        'meta_value' => $post->ID,
    ));
    
    if ($stands) {
        $total_slots = 0;
        $total_quota = 0;
        
        foreach ($stands as $stand) {
            $quota = get_post_meta($stand->ID, '_fb_quota_par_creneau', true) ?: 5;
            $creneaux = get_posts(array(
                'post_type' => 'fb_creneau',
                'numberposts' => -1,
                'meta_key' => '_fb_stand_id',
                'meta_value' => $stand->ID,
            ));
            
            $total_slots += count($creneaux);
            $total_quota += count($creneaux) * $quota;
        }
        
        $fill_rate = $total_quota > 0 ? round(($total / $total_quota) * 100) : 0;
        ?>
        <div class="fb-stat-item">
            <span class="fb-stat-value"><?php echo $fill_rate; ?>%</span>
            <span class="fb-stat-label">Taux de remplissage</span>
        </div>
        <?php
    }
    ?>
</div>
