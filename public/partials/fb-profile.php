<?php
/**
 * Profile page - volunteer dashboard
 */

if (!defined('ABSPATH')) exit;

// Check if logged in with token/email
$saved_email = isset($_COOKIE['fb_email']) ? sanitize_email($_COOKIE['fb_email']) : '';
$saved_token = isset($_COOKIE['fb_token']) ? sanitize_text_field($_COOKIE['fb_token']) : '';
$reservations = array();

if ($saved_email) {
    // Fetch reservations from DB
    global $wpdb;
    $table = $wpdb->prefix . 'fb_inscriptions';
    
    $reservations = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE email = %s AND statut = 'confirmed' ORDER BY date_inscription DESC",
        $saved_email
    ));
}
?>

<div class="fb-profile-container">
    <h1>Mon espace bénévole</h1>
    
    <?php if (empty($reservations)) : ?>
        <!-- Login form -->
        <div class="fb-profile-login">
            <h2>Retrouver mes réservations</h2>
            <form id="fb-profile-login-form">
                <div class="fb-form-group">
                    <label for="fb-profile-email">Votre email</label>
                    <input type="email" id="fb-profile-email" name="email" required>
                </div>
                <button type="submit" class="button button-primary">Accéder à mes réservations</button>
            </form>
            <p class="fb-profile-help">
                Un email magique vous sera envoyé pour accéder à vos réservations.
            </p>
        </div>
    <?php else : ?>
        <!-- Reservations list -->
        <div class="fb-profile-reservations">
            <div class="fb-profile-header">
                <p>Connecté(e) en tant que <strong><?php echo esc_html($saved_email); ?></strong></p>
                <button type="button" class="button" id="fb-profile-logout">Se déconnecter</button>
            </div>
            
            <h2>Mes réservations</h2>
            
            <?php foreach ($reservations as $res) : 
                $stand_name = get_the_title($res->stand_id);
                $creneau_title = get_the_title($res->creneau_id);
                $evenement_id = get_post_meta($res->stand_id, '_fb_evenement_id', true);
                $evenement_title = get_the_title($evenement_id);
                $delai = get_post_meta($evenement_id, '_fb_delai_inscription', true);
                $can_modify = $delai && strtotime($delai) > time();
            ?>
                <div class="fb-reservation-item">
                    <h3><?php echo esc_html($evenement_title); ?></h3>
                    <p><strong>Stand :</strong> <?php echo esc_html($stand_name); ?></p>
                    <p><strong>Créneau :</strong> <?php echo esc_html($creneau_title); ?></p>
                    <p><strong>Date d'inscription :</strong> <?php echo date_i18n('d/m/Y H:i', strtotime($res->date_inscription)); ?></p>
                    
                    <?php if ($can_modify) : ?>
                        <div class="fb-reservation-actions">
                            <button type="button" class="button" data-reservation-id="<?php echo $res->id; ?>" data-token="<?php echo esc_attr($res->token); ?>">
                                Modifier
                            </button>
                            <button type="button" class="button button-link-delete fb-cancel-reservation" 
                                    data-reservation-id="<?php echo $res->id; ?>" 
                                    data-token="<?php echo esc_attr($res->token); ?>">
                                Annuler
                            </button>
                        </div>
                    <?php else : ?>
                        <p class="fb-reservation-locked"><em>Les modifications ne sont plus possibles pour cet événement</em></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
