<?php
/**
 * Exports admin page
 */

if (!defined('ABSPATH')) exit;

$selected_event = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$selected_stand = isset($_GET['stand_id']) ? intval($_GET['stand_id']) : 0;

// Get all events
$events = get_posts(array(
    'post_type' => 'fb_evenement',
    'numberposts' => -1,
    'post_status' => 'any',
));

// Get stands for selected event
$stands = array();
if ($selected_event) {
    $stands = get_posts(array(
        'post_type' => 'fb_stand',
        'numberposts' => -1,
        'meta_key' => '_fb_evenement_id',
        'meta_value' => $selected_event,
    ));
}
?>

<div class="wrap fb-admin">
    <h1>Exports</h1>
    
    <div class="fb-export-section">
        <h2>Exporter les inscriptions (CSV)</h2>
        <p>Exportez les données des bénévoles pour un événement. Une ligne par bénévole, une colonne par stand.</p>
        
        <form id="fb-export-form" method="POST" action="<?php echo admin_url('admin-ajax.php'); ?>" class="fb-export-form">
            <input type="hidden" name="action" value="fb_export_csv">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('fb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="export-event">Événement *</label></th>
                    <td>
                        <select name="evenement_id" id="export-event" required>
                            <option value="">Sélectionner un événement</option>
                            <?php foreach ($events as $event) : ?>
                                <option value="<?php echo $event->ID; ?>" <?php selected($selected_event, $event->ID); ?>>
                                    <?php echo esc_html(get_the_title($event)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-download"></span> Télécharger CSV
                </button>
            </p>
        </form>
    </div>
    
    <div class="fb-export-section">
        <h2>Générer les badges</h2>
        <p>Créez des badges nominatifs pour les bénévoles (format A4 imprimable).</p>
        
        <form id="fb-badges-form" method="POST" action="<?php echo admin_url('admin-ajax.php'); ?>" class="fb-export-form">
            <input type="hidden" name="action" value="fb_generate_badges">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('fb_admin_nonce'); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="badges-event">Événement *</label></th>
                    <td>
                        <select name="evenement_id" id="badges-event" required>
                            <option value="">Sélectionner un événement</option>
                            <?php foreach ($events as $event) : ?>
                                <option value="<?php echo $event->ID; ?>" <?php selected($selected_event, $event->ID); ?>>
                                    <?php echo esc_html(get_the_title($event)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="badges-stand">Stand (optionnel)</label></th>
                    <td>
                        <select name="stand_id" id="badges-stand">
                            <option value="0">Tous les stands</option>
                            <?php foreach ($stands as $stand) : ?>
                                <option value="<?php echo $stand->ID; ?>" <?php selected($selected_stand, $stand->ID); ?>>
                                    <?php echo esc_html(get_the_title($stand)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="badges-format">Format</label></th>
                    <td>
                        <select name="format" id="badges-format">
                            <option value="avery-l7163">Avery L7163 (99.1 x 38.1 mm)</option>
                            <option value="avery-l7160">Avery L7160 (63.5 x 38.1 mm)</option>
                            <option value="a4-simple">A4 Simple (sans prédécoupe)</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-id-alt"></span> Générer les badges
                </button>
            </p>
            
            <p class="description">
                <strong>Note:</strong> La génération de badges PDF nécessite l'installation de TCPDF ou Dompdf.
                Contactez votre administrateur système pour activer cette fonctionnalité.
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update stands when event changes
    $('#export-event, #badges-event').on('change', function() {
        const eventId = $(this).val();
        const standSelect = $(this).closest('form').find('#export-stand, #badges-stand');
        
        if (!eventId) {
            standSelect.html('<option value="0">Tous les stands</option>');
            return;
        }
        
        // Load stands via AJAX (could be implemented)
        // For now, page reload is simplest
    });
    
    // Form submission - CSV export
    $('#fb-export-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: fbAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            xhrFields: {
                responseType: 'blob'
            },
            success: function(blob, status, xhr) {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = xhr.getResponseHeader('Content-Disposition').split('filename=')[1];
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            },
            error: function(xhr) {
                alert('Erreur lors de l\'export: ' + (xhr.responseJSON?.data?.message || 'Erreur inconnue'));
            }
        });
    });
    
    $('#fb-badges-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: fbAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Erreur: ' + response.data.message);
                }
            }
        });
    });
});
</script>

<style>
.fb-export-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.fb-export-section h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.fb-export-form .form-table {
    margin: 20px 0;
}

.fb-export-form .form-table th {
    padding: 10px 10px 10px 0;
    width: 200px;
}

.fb-export-form .form-table td {
    padding: 10px 0;
}

.fb-export-form select {
    min-width: 300px;
}
</style>
