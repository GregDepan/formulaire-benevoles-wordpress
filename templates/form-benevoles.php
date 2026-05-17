<?php
/**
 * Template for volunteer registration form
 *
 * @package    Formulaire_Benevoles
 * @subpackage Formulaire_Benevoles/templates
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $wpdb;
$event_id = get_the_ID();

// Get all stands for this event
$stands = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, p.menu_order 
     FROM {$wpdb->posts} p 
     WHERE p.post_type = 'fb_stand' 
     AND p.post_parent = %d 
     AND p.post_status = 'publish'
     ORDER BY p.menu_order ASC, p.post_title ASC",
    $event_id
));

// Get creneaux for each stand
foreach ($stands as $stand) {
    $stand->creneaux = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, p.menu_order 
         FROM {$wpdb->posts} p 
         WHERE p.post_type = 'fb_creneau' 
         AND p.post_parent = %d 
         AND p.post_status = 'publish'
         ORDER BY p.menu_order ASC",
        $stand->ID
    ));
}
?>

<style>
.fb-form-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    font-family: 'Public Sans', sans-serif;
}

.fb-form-title {
    color: #333;
    font-size: 28px;
    margin-bottom: 10px;
    text-align: center;
}

.fb-form-subtitle {
    color: #666;
    font-size: 14px;
    text-align: center;
    margin-bottom: 30px;
    line-height: 1.6;
}

.fb-form-section {
    margin-bottom: 35px;
    padding-bottom: 25px;
    border-bottom: 1px solid #eee;
}

.fb-form-section:last-child {
    border-bottom: none;
}

.fb-section-title {
    color: var(--fb-primary, #696cff);
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fb-section-title::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 18px;
    background: var(--fb-primary, #696cff);
    border-radius: 2px;
}

.fb-field-group {
    margin-bottom: 20px;
}

.fb-field-label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.fb-field-required {
    color: var(--fb-error, #ff3e1d);
}

.fb-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 15px;
    font-family: 'Public Sans', sans-serif;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.fb-input:focus {
    outline: none;
    border-color: var(--fb-primary, #696cff);
    box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.1);
}

.fb-creneaux-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 12px;
    margin-top: 15px;
}

.fb-creneau-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: #f8f7ff;
    border: 1px solid #e0dfff;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.fb-creneau-item:hover {
    background: #f0efff;
    border-color: var(--fb-primary, #696cff);
}

.fb-creneau-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 12px;
    accent-color: var(--fb-primary, #696cff);
    cursor: pointer;
}

.fb-creneau-item label {
    font-size: 14px;
    color: #333;
    cursor: pointer;
    flex: 1;
}

.fb-submit-btn {
    width: 100%;
    padding: 16px 32px;
    background: var(--fb-primary, #696cff);
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Public Sans', sans-serif;
}

.fb-submit-btn:hover {
    background: #5558e3;
}

.fb-submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.fb-success-message {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.fb-error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.fb-info-link {
    color: var(--fb-primary, #696cff);
    text-decoration: underline;
    font-weight: 500;
}
</style>

<div class="fb-form-container">
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="fb-success-message">
            <strong>✅ Inscription enregistrée !</strong><br>
            Merci pour votre participation. Vous recevrez un email de confirmation.
        </div>
        
        <?php 
        // Show modification info if before deadline
        $date_limite = get_post_meta($event_id, '_fb_date_limite', true);
        if ($date_limite && strtotime($date_limite) > time()): 
        ?>
            <div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-radius: 8px; border-left: 4px solid #2196f3;">
                <strong>💡 Vous pouvez modifier votre inscription</strong><br>
                Les inscriptions sont ouvertes jusqu'au <strong><?php echo date_i18n('d/m/Y', strtotime($date_limite)); ?></strong>.<br>
                Remplissez à nouveau le formulaire avec le même email pour modifier vos créneaux.
            </div>
        <?php endif; ?>
        
    <?php elseif (isset($_GET['error'])): ?>
        <div class="fb-error-message">
            <strong>❌ Erreur :</strong> <?php echo esc_html($_GET['error']); ?>
        </div>
    <?php else: ?>

    <h1 class="fb-form-title"><?php echo esc_html(get_the_title()); ?></h1>
    
    <p class="fb-form-subtitle">
        <?php 
        // Display event description if available, otherwise show default text
        $description = get_post_field('post_content', $event_id);
        if (!empty($description)) {
            echo nl2br(esc_html($description));
        } else {
            ?>
            Retrouvez toutes les informations sur la kermesse <a href="#" class="fb-info-link">ICI</a><br>
            <strong>Merci de choisir UN SEUL créneau par tranche horaire</strong><br>
            Merci de votre participation !
            <?php
        }
        ?>
    </p>

    <form method="post" action="" id="fb-registration-form">
        <?php wp_nonce_field('fb_register_volunteer', 'fb_nonce'); ?>
        <input type="hidden" name="fb_action" value="register">
        <input type="hidden" name="fb_event_id" value="<?php echo esc_attr($event_id); ?>">

        <!-- Informations personnelles -->
        <div class="fb-form-section">
            <h2 class="fb-section-title">Vos coordonnées</h2>
            
            <div class="fb-field-group">
                <label class="fb-field-label">Adresse email <span class="fb-field-required">*</span></label>
                <input type="email" name="fb_email" class="fb-input" required placeholder="votre@email.com">
            </div>

            <div class="fb-field-group">
                <label class="fb-field-label">Nom <span class="fb-field-required">*</span></label>
                <input type="text" name="fb_nom" class="fb-input" required placeholder="Votre nom">
            </div>

            <div class="fb-field-group">
                <label class="fb-field-label">Prénom <span class="fb-field-required">*</span></label>
                <input type="text" name="fb_prenom" class="fb-input" required placeholder="Votre prénom">
            </div>

            <div class="fb-field-group">
                <label class="fb-field-label">Téléphone <span class="fb-field-required">*</span></label>
                <input type="tel" name="fb_telephone" class="fb-input" required placeholder="06 12 34 56 78">
            </div>
        </div>

        <!-- Stands et créneaux -->
        <div class="fb-form-section">
            <h2 class="fb-section-title">Choisissez vos créneaux</h2>
            <p style="color: #666; font-size: 13px; margin-bottom: 20px;">
                Cochez les créneaux qui vous intéressent. Un seul créneau par tranche horaire.
            </p>

            <?php foreach ($stands as $stand): ?>
                <div style="margin-bottom: 25px;">
                    <h3 style="color: #333; font-size: 16px; margin-bottom: 12px;">
                        <?php echo esc_html($stand->post_title); ?>
                    </h3>
                    
                    <div class="fb-creneaux-list">
                        <?php foreach ($stand->creneaux as $creneau): ?>
                            <div class="fb-creneau-item">
                                <input 
                                    type="checkbox" 
                                    name="fb_creneaux[]" 
                                    id="creneau-<?php echo esc_attr($creneau->ID); ?>" 
                                    value="<?php echo esc_attr($creneau->ID); ?>"
                                >
                                <label for="creneau-<?php echo esc_attr($creneau->ID); ?>">
                                    <?php echo esc_html($creneau->post_title); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="fb-submit-btn">
            ✅ S'inscrire comme bénévole
        </button>
    </form>
    
    <?php endif; // End of form display (not success/error) ?>
    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fb-registration-form');
    
    form.addEventListener('submit', function(e) {
        const checkboxes = form.querySelectorAll('input[name="fb_creneaux[]"]:checked');
        
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Merci de sélectionner au moins un créneau !');
            return false;
        }
    });
});
</script>

<?php
get_footer();
