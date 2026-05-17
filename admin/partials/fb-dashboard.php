<?php
/**
 * Admin dashboard
 */

if (!defined('ABSPATH')) exit;

// Get stats
global $wpdb;
$total_events = wp_count_posts('fb_evenement')->publish;
$total_inscriptions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions WHERE statut = 'confirmed'");
$total_benevoles = $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}fb_inscriptions WHERE statut = 'confirmed'");

// Recent events
$recent_events = get_posts(array(
    'post_type' => 'fb_evenement',
    'numberposts' => 5,
    'post_status' => 'any',
    'orderby' => 'modified',
    'order' => 'DESC',
));
?>

<div class="wrap fb-admin">
    <h1>Tableau de bord - Formulaire Bénévoles</h1>
    
    <div class="fb-dashboard-cards">
        <div class="fb-card">
            <h3>Événements actifs</h3>
            <div class="fb-card-value"><?php echo $total_events; ?></div>
        </div>
        
        <div class="fb-card">
            <h3>Inscriptions totales</h3>
            <div class="fb-card-value"><?php echo $total_inscriptions; ?></div>
        </div>
        
        <div class="fb-card">
            <h3>Bénévoles uniques</h3>
            <div class="fb-card-value"><?php echo $total_benevoles; ?></div>
        </div>
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <h2 style="margin: 0;">📅 Tous vos événements</h2>
        <a href="<?php echo admin_url('post-new.php?post_type=fb_evenement'); ?>" class="button button-primary">
            <span class="dashicons dashicons-plus-alt"></span> Nouvel événement
        </a>
    </div>
    
    <p class="description" style="margin-top: 10px;">
        💡 Astuce : Utilise le menu "📊 Tableau de bord" pour une vue complète avec les événements à venir et passés.
    </p>
</div>
