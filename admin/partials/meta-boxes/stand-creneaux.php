<?php
/**
 * Stand creneaux meta box
 */

if (!defined('ABSPATH')) exit;
?>

<div class="fb-meta-box">
    <div class="fb-creneaux-header">
        <button type="button" class="button button-primary" id="fb-add-creneau-btn">
            <span class="dashicons dashicons-plus-alt"></span> Ajouter un créneau
        </button>
    </div>
    
    <div id="fb-creneaux-list" class="fb-list-container">
        <?php if (empty($creneaux)) : ?>
            <p class="no-items">Aucun créneau pour ce stand. Cliquez sur "Ajouter un créneau" pour commencer.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Créneau</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Inscrits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($creneaux as $creneau) : 
                        $heure_debut = get_post_meta($creneau->ID, '_fb_heure_debut', true);
                        $heure_fin = get_post_meta($creneau->ID, '_fb_heure_fin', true);
                        $quota_specifique = get_post_meta($creneau->ID, '_fb_quota_specifique', true);
                        
                        // Count inscriptions
                        global $wpdb;
                        $inscrits = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions 
                             WHERE creneau_id = %d AND statut = 'confirmed'",
                            $creneau->ID
                        ));
                        
                        // Get stand quota
                        $stand_id = get_post_meta($creneau->ID, '_fb_stand_id', true);
                        $quota = $quota_specifique ?: get_post_meta($stand_id, '_fb_quota_par_creneau', true) ?: 5;
                    ?>
                        <tr data-creneau-id="<?php echo $creneau->ID; ?>">
                            <td><strong><?php echo esc_html(get_the_title($creneau)); ?></strong></td>
                            <td><?php echo esc_html($heure_debut); ?></td>
                            <td><?php echo esc_html($heure_fin); ?></td>
                            <td>
                                <?php echo $inscrits; ?> / <?php echo $quota; ?>
                                <?php if ($inscrits >= $quota) : ?>
                                    <span class="fb-badge fb-badge-full">Complet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small fb-edit-creneau" data-creneau-id="<?php echo $creneau->ID; ?>">
                                    Éditer
                                </button>
                                <button type="button" class="button button-small button-link-delete fb-delete-creneau" data-creneau-id="<?php echo $creneau->ID; ?>">
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Add Creneau Modal -->
    <div id="fb-add-creneau-modal" class="fb-modal" style="display:none;">
        <div class="fb-modal-content">
            <h3>Ajouter un créneau</h3>
            <input type="hidden" id="fb-modal-stand-id" value="<?php echo $post->ID; ?>">
            
            <p>
                <label>Heure de début *</label><br>
                <input type="time" id="fb-creneau-heure-debut" class="regular-text">
            </p>
            
            <p>
                <label>Heure de fin *</label><br>
                <input type="time" id="fb-creneau-heure-fin" class="regular-text">
            </p>
            
            <p>
                <label>Quota spécifique (optionnel)</label><br>
                <input type="number" id="fb-creneau-quota" min="1" class="small-text" placeholder="Défaut du stand">
                <span class="description">Laisser vide pour utiliser le quota du stand</span>
            </p>
            
            <p class="fb-modal-actions">
                <button type="button" class="button" id="fb-creneau-cancel">Annuler</button>
                <button type="button" class="button button-primary" id="fb-creneau-save">Ajouter</button>
            </p>
        </div>
    </div>
</div>
