<?php
/**
 * Event stands meta box
 */

if (!defined('ABSPATH')) exit;
?>

<div class="fb-meta-box">
    <div class="fb-stands-header">
        <button type="button" class="button button-primary" id="fb-add-stand-btn">
            <span class="dashicons dashicons-plus-alt"></span> Ajouter un stand
        </button>
    </div>
    
    <div id="fb-stands-list" class="fb-list-container">
        <?php if (empty($stands)) : ?>
            <p class="no-items">Aucun stand pour cet événement. Cliquez sur "Ajouter un stand" pour commencer.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Stand</th>
                        <th>Quota/créneau</th>
                        <th>Créneaux</th>
                        <th>Inscrits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stands as $stand) : 
                        $quota = get_post_meta($stand->ID, '_fb_quota_par_creneau', true);
                        $evenement_id = get_post_meta($stand->ID, '_fb_evenement_id', true);
                        
                        // Count creneaux
                        $creneaux = get_posts(array(
                            'post_type' => 'fb_creneau',
                            'numberposts' => -1,
                            'meta_key' => '_fb_stand_id',
                            'meta_value' => $stand->ID,
                        ));
                        
                        // Count inscriptions
                        global $wpdb;
                        $total_inscrits = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions 
                             WHERE stand_id = %d AND statut = 'confirmed'",
                            $stand->ID
                        ));
                    ?>
                        <tr data-stand-id="<?php echo $stand->ID; ?>">
                            <td>
                                <strong><?php echo esc_html(get_the_title($stand)); ?></strong>
                            </td>
                            <td><?php echo $quota ? esc_html($quota) : '5 (défaut)'; ?></td>
                            <td><?php echo count($creneaux); ?> créneau(x)</td>
                            <td><?php echo $total_inscrits; ?></td>
                            <td>
                                <button type="button" class="button button-small fb-edit-stand" data-stand-id="<?php echo $stand->ID; ?>">
                                    Éditer
                                </button>
                                <button type="button" class="button button-small fb-manage-creneaux" data-stand-id="<?php echo $stand->ID; ?>">
                                    Créneaux
                                </button>
                                <button type="button" class="button button-small button-link-delete fb-delete-stand" data-stand-id="<?php echo $stand->ID; ?>">
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Add Stand Modal -->
    <div id="fb-add-stand-modal" class="fb-modal" style="display:none;">
        <div class="fb-modal-content">
            <h3>Ajouter un stand</h3>
            <input type="hidden" id="fb-modal-evenement-id" value="<?php echo $post->ID; ?>">
            
            <p>
                <label>Nom du stand *</label><br>
                <input type="text" id="fb-stand-nom" class="regular-text" placeholder="Ex: Buvette">
            </p>
            
            <p>
                <label>Quota par créneau</label><br>
                <input type="number" id="fb-stand-quota" value="5" min="1" class="small-text">
                <span class="description">Nombre maximum de bénévoles par créneau</span>
            </p>
            
            <p>
                <label>Couleur (optionnel)</label><br>
                <input type="color" id="fb-stand-couleur" value="#3498db" class="color-picker">
            </p>
            
            <p>
                <label>Description</label><br>
                <textarea id="fb-stand-description" rows="3" class="large-text"></textarea>
            </p>
            
            <p class="fb-modal-actions">
                <button type="button" class="button" id="fb-stand-cancel">Annuler</button>
                <button type="button" class="button button-primary" id="fb-stand-save">Ajouter</button>
            </p>
        </div>
    </div>
</div>
