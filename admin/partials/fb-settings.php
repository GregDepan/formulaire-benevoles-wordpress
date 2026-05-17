<?php
/**
 * Settings admin page
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap fb-admin">
    <h1>Réglages - Formulaire Bénévoles</h1>
    
    <form method="POST" action="options.php">
        <?php settings_fields('fb_settings_group'); ?>
        <?php do_settings_sections('fb_settings_group'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="fb_default_slot_duration">Durée par défaut des créneaux (minutes)</label></th>
                <td>
                    <input type="number" name="fb_default_slot_duration" id="fb_default_slot_duration" 
                           value="<?php echo esc_attr(get_option('fb_default_slot_duration', 30)); ?>" 
                           min="15" max="120" step="15" class="small-text">
                    <p class="description">Durée standard pour les nouveaux créneaux</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="fb_allow_modifications">Autoriser les modifications</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="fb_allow_modifications" id="fb_allow_modifications" 
                               value="1" <?php checked(get_option('fb_allow_modifications', true), true); ?>>
                        Les bénévoles peuvent modifier ou annuler leurs réservations
                    </label>
                </td>
            </tr>
            
            <tr>
                <th><label for="fb_modification_deadline_days">Délai de modification (jours)</label></th>
                <td>
                    <input type="number" name="fb_modification_deadline_days" id="fb_modification_deadline_days" 
                           value="<?php echo esc_attr(get_option('fb_modification_deadline_days', 2)); ?>" 
                           min="0" max="30" class="small-text">
                    <p class="description">Nombre de jours avant l'événement pour autoriser les modifications</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="fb_email_from_name">Nom de l'expéditeur des emails</label></th>
                <td>
                    <input type="text" name="fb_email_from_name" id="fb_email_from_name" 
                           value="<?php echo esc_attr(get_option('fb_email_from_name', 'Formulaire Bénévoles')); ?>" 
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th><label for="fb_email_from_address">Email de l'expéditeur</label></th>
                <td>
                    <input type="email" name="fb_email_from_address" id="fb_email_from_address" 
                           value="<?php echo esc_attr(get_option('fb_email_from_address', get_option('admin_email'))); ?>" 
                           class="regular-text">
                    <p class="description">Laisser vide pour utiliser l'email de l'administrateur</p>
                </td>
            </tr>
            
            <tr>
                <th><label for="fb_badge_format">Format de badges par défaut</label></th>
                <td>
                    <select name="fb_badge_format" id="fb_badge_format">
                        <option value="avery-l7163" <?php selected(get_option('fb_badge_format', 'avery-l7163'), 'avery-l7163'); ?>>
                            Avery L7163 (99.1 x 38.1 mm)
                        </option>
                        <option value="avery-l7160" <?php selected(get_option('fb_badge_format', 'avery-l7163'), 'avery-l7160'); ?>>
                            Avery L7160 (63.5 x 38.1 mm)
                        </option>
                        <option value="a4-simple" <?php selected(get_option('fb_badge_format', 'avery-l7163'), 'a4-simple'); ?>>
                            A4 Simple (sans prédécoupe)
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Enregistrer les réglages'); ?>
    </form>
    
    <hr>
    
    <h2>Informations sur le plugin</h2>
    <table class="form-table">
        <tr>
            <th>Version</th>
            <td><?php echo FB_VERSION; ?></td>
        </tr>
        <tr>
            <th>Répertoire</th>
            <td><code><?php echo FB_PLUGIN_DIR; ?></code></td>
        </tr>
        <tr>
            <th>Tables database</th>
            <td>
                <code><?php echo global $wpdb; echo $wpdb->prefix; ?>fb_inscriptions</code><br>
                <code><?php echo $wpdb->prefix; ?>fb_waitlist</code><br>
                <code><?php echo $wpdb->prefix; ?>fb_stats_logs</code>
            </td>
        </tr>
    </table>
</div>
