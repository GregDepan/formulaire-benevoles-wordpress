<?php
/**
 * Single event template - public form
 * Reprend exactement la structure du formulaire Google
 * IMPORTANT: N'utilise PAS get_header()/get_footer() pour éviter les vérifications de connexion du thème
 */

if (!defined('ABSPATH')) exit;

// Minimal HTML header - bypass theme to avoid private site checks
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(get_the_title()); ?> - Inscription Bénévoles</title>
    <?php wp_head(); ?>
</head>
<body class="fb-template-body">

<?php

global $wpdb, $post;
$evenement_id = $post->ID;

// Check if archived
$archived = get_post_meta($evenement_id, '_fb_archived', true);
if ($archived) {
    ?>
    <div class="fb-form-container">
        <div class="fb-success-message">
            <strong>✅ Inscriptions closes</strong><br>
            Désolé, les inscriptions pour cet événement sont terminées.<br>
            À l'année prochaine !
        </div>
    </div>
    <?php
    // Minimal footer for archived page
    ?>
    </body>
    </html>
    <?php
    return;
}

// Get stands for this event (hierarchical: post_parent = event_id)
$stands = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, p.menu_order 
     FROM {$wpdb->posts} p 
     WHERE p.post_type = 'fb_stand' 
     AND p.post_parent = %d 
     AND p.post_status = 'publish'
     ORDER BY p.menu_order ASC, p.post_title ASC",
    $evenement_id
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
    
    // Get quota and count inscriptions for each creneau
    foreach ($stand->creneaux as $creneau) {
        $quota = get_post_meta($stand->ID, '_fb_quota_par_creneau', true);
        if (!$quota) $quota = 5;
        
        $inscriptions_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions 
             WHERE creneau_id = %d AND statut = 'confirmed'",
            $creneau->ID
        ));
        
        $creneau->quota = $quota;
        $creneau->inscriptions = $inscriptions_count;
        $creneau->remaining = max(0, $quota - $inscriptions_count);
        $creneau->exclusion_group = get_post_meta($creneau->ID, '_fb_exclusion_group', true);
    }
}
?>

<div class="fb-form-container">
    <h1 class="fb-form-title"><?php echo esc_html(get_the_title()); ?></h1>
    
    <?php 
    // Check for success message - show ONLY success, no form
    if (isset($_GET['success']) && $_GET['success'] == '1'): 
    ?>
        <div class="fb-success-message">
            <strong>✅ Inscription confirmée !</strong><br>
            Merci beaucoup pour votre inscription.<br>
            Vous recevrez un email de confirmation sous peu.
        </div>
        
        <?php 
        // Show modification info if before deadline
        $date_limite = get_post_meta($evenement_id, '_fb_date_limite', true);
        if ($date_limite && strtotime($date_limite) > time()): 
        ?>
            <div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-radius: 8px; border-left: 4px solid #2196f3;">
                <strong>💡 Vous pouvez modifier votre inscription</strong><br>
                Les inscriptions sont ouvertes jusqu'au <strong><?php echo date_i18n('d/m/Y', strtotime($date_limite)); ?></strong>.<br>
                Remplissez à nouveau le formulaire avec le même email pour modifier vos créneaux.
            </div>
        <?php endif; ?>
        
    <?php 
    // Check for error message
    elseif (isset($_GET['error'])): 
    ?>
        <div class="fb-error-message">
            <strong>⚠️ Erreur</strong><br>
            <?php echo esc_html(urldecode($_GET['error'])); ?>
        </div>
    <?php else: ?>
        
        <!-- Show form only if not success/error -->
    
    <p class="fb-form-subtitle">
        <?php 
        // Display event description if available, otherwise show default text
        $description = get_post_field('post_content', $evenement_id);
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
        <input type="hidden" name="fb_event_id" value="<?php echo esc_attr($evenement_id); ?>">

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
                            <?php 
                            $exclusion_group = $creneau->exclusion_group;
                            $is_full = $creneau->remaining <= 0;
                            ?>
                            <div class="fb-creneau-item <?php echo $is_full ? 'fb-creneau-full' : ''; ?>" 
                                 data-exclusion-group="<?php echo esc_attr($exclusion_group); ?>"
                                 data-creneau-id="<?php echo esc_attr($creneau->ID); ?>">
                                <input 
                                    type="checkbox" 
                                    name="fb_creneaux[]" 
                                    id="creneau-<?php echo esc_attr($creneau->ID); ?>" 
                                    value="<?php echo esc_attr($creneau->ID); ?>"
                                    data-exclusion-group="<?php echo esc_attr($exclusion_group); ?>"
                                    <?php disabled($is_full); ?>
                                >
                                <label for="creneau-<?php echo esc_attr($creneau->ID); ?>" class="fb-creneau-label">
                                    <span class="fb-creneau-title"><?php echo esc_html($creneau->post_title); ?></span>
                                    <span class="fb-quota-indicator <?php echo $creneau->remaining <= 2 ? 'fb-quota-low' : ''; ?>">
                                        <?php if ($is_full): ?>
                                            <span class="fb-quota-full">❌ Complet</span>
                                        <?php else: ?>
                                            <span class="fb-quota-text">👥 <?php echo $creneau->remaining; ?>/<?php echo $creneau->quota; ?></span>
                                        <?php endif; ?>
                                    </span>
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
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.fb-quota-indicator {
    font-size: 12px;
    color: #666;
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 4px;
    display: inline-block;
    width: fit-content;
    font-weight: 500;
}

.fb-quota-low {
    color: #ff9800;
    background: #fff8e1;
}

.fb-quota-full {
    color: #dc3545;
    background: #ffe6e6;
}

.fb-creneau-full {
    opacity: 0.6;
    background: #f0f0f1 !important;
}

.fb-creneau-full input[type="checkbox"] {
    cursor: not-allowed;
}

.fb-creneau-full label {
    cursor: not-allowed;
}

.fb-creneau-item.hidden-by-group {
    opacity: 0;
    transform: scale(0.9) translateY(-10px);
    pointer-events: none;
    height: 0;
    padding: 0;
    margin: 0;
    overflow: hidden;
    border: none;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
    max-width: 800px;
    margin: 40px auto;
}

.fb-error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    max-width: 800px;
    margin: 40px auto;
}

.fb-info-link {
    color: var(--fb-primary, #696cff);
    text-decoration: underline;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fb-registration-form');
    const checkboxes = form.querySelectorAll('input[name="fb_creneaux[]"]');
    const selectedGroups = new Set(); // Track which groups are already selected
    
    // Helper to parse groups from data attribute (comma-separated)
    function parseGroups(checkbox) {
        const groupString = checkbox.dataset.exclusionGroup;
        if (!groupString) return [];
        return groupString.split(',').map(g => g.trim()).filter(g => g);
    }
    
    checkboxes.forEach(checkbox => {
        const groups = parseGroups(checkbox);
        
        checkbox.addEventListener('change', function() {
            if (groups.length === 0) return; // No groups, no restrictions
            
            if (this.checked) {
                // Add all this creneau's groups to selected groups
                groups.forEach(group => selectedGroups.add(group));
                
                // Hide and uncheck all OTHER creneaux that share ANY of these groups
                // BUT: never hide checkboxes that are already checked
                checkboxes.forEach(otherCheckbox => {
                    if (otherCheckbox === this) return;
                    if (otherCheckbox.checked) return; // Never hide already-checked boxes
                    
                    const otherGroups = parseGroups(otherCheckbox);
                    const hasConflict = otherGroups.some(g => selectedGroups.has(g));
                    
                    if (hasConflict) {
                        otherCheckbox.closest('.fb-creneau-item').classList.add('hidden-by-group');
                        otherCheckbox.checked = false;
                    }
                });
            } else {
                // Remove this creneau's groups from selected
                groups.forEach(group => selectedGroups.delete(group));
                
                // Rebuild the set from remaining checked checkboxes
                checkboxes.forEach(otherCheckbox => {
                    if (otherCheckbox === this) return;
                    if (otherCheckbox.checked) {
                        parseGroups(otherCheckbox).forEach(g => selectedGroups.add(g));
                    }
                });
                
                // Show all creneaux that don't conflict with remaining selections
                checkboxes.forEach(otherCheckbox => {
                    if (otherCheckbox === this) return;
                    if (otherCheckbox.checked) return; // Never hide already-checked boxes
                    
                    const otherGroups = parseGroups(otherCheckbox);
                    const hasConflict = otherGroups.some(g => selectedGroups.has(g));
                    
                    if (!hasConflict) {
                        otherCheckbox.closest('.fb-creneau-item').classList.remove('hidden-by-group');
                    }
                });
            }
        });
        
        // On page load, check if this checkbox is pre-checked
        if (checkbox.checked && groups.length > 0) {
            groups.forEach(g => selectedGroups.add(g));
        }
    });
    
    form.addEventListener('submit', function(e) {
        const checkedCheckboxes = form.querySelectorAll('input[name="fb_creneaux[]"]:checked');
        
        if (checkedCheckboxes.length === 0) {
            e.preventDefault();
            alert('Merci de sélectionner au moins un créneau !');
            return false;
        }
        
        // Debug: log what's being submitted
        console.log('Submitting creneaux:', Array.from(checkedCheckboxes).map(cb => cb.value));
    });
});
</script>

<?php
// Minimal footer - bypass theme
?>
</body>
</html>
