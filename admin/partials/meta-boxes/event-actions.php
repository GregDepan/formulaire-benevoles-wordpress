<?php
/**
 * Event actions meta box
 */

if (!defined('ABSPATH')) exit;

$is_published = ($post->post_status === 'publish');
?>

<div class="fb-actions-box">
    <p>
        <strong>Statut:</strong> 
        <span class="fb-status fb-status-<?php echo esc_attr($post->post_status); ?>">
            <?php echo esc_html($post->post_status); ?>
        </span>
    </p>
    
    <?php if ($is_published) : ?>
        <p>
            <a href="<?php echo get_permalink($post->ID); ?>" target="_blank" class="button button-small">
                <span class="dashicons dashicons-external"></span> Voir la page publique
            </a>
        </p>
    <?php endif; ?>
    
    <hr>
    
    <p>
        <button type="button" class="button button-secondary" id="fb-clone-event-btn" data-event-id="<?php echo $post->ID; ?>">
            <span class="dashicons dashicons-admin-page"></span> Cloner l'événement
        </button>
        <span class="description">Copie stands et créneaux (pas les inscriptions)</span>
    </p>
    
    <hr>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=fb-exports&event_id=' . $post->ID); ?>" class="button button-secondary">
            <span class="dashicons dashicons-download"></span> Exporter CSV
        </a>
    </p>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=fb-stats&event_id=' . $post->ID); ?>" class="button button-secondary">
            <span class="dashicons dashicons-chart-bar"></span> Voir statistiques
        </a>
    </p>
</div>
