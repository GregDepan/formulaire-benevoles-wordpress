<?php
/**
 * Inscriptions list admin page
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'fb_inscriptions';

// Get filters
$selected_event = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$selected_stand = isset($_GET['stand_id']) ? intval($_GET['stand_id']) : 0;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Build query
$where = 'WHERE statut = %s';
$params = array('confirmed');

if ($selected_event) {
    $where .= ' AND evenement_id = %d';
    $params[] = $selected_event;
}

if ($selected_stand) {
    $where .= ' AND stand_id = %d';
    $params[] = $selected_stand;
}

if ($search) {
    $where .= ' AND (nom LIKE %s OR prenom LIKE %s OR email LIKE %s)';
    $search_param = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$inscriptions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table $where ORDER BY date_inscription DESC",
    $params
));

// Get all events for filter
$events = get_posts(array(
    'post_type' => 'fb_evenement',
    'numberposts' => -1,
    'post_status' => 'any',
));
?>

<div class="wrap fb-admin">
    <h1>Gestion des inscriptions</h1>
    
    <!-- Filters -->
    <form method="GET" class="fb-filters">
        <input type="hidden" name="page" value="fb-inscriptions">
        
        <select name="event_id">
            <option value="0">Tous les événements</option>
            <?php foreach ($events as $event) : ?>
                <option value="<?php echo $event->ID; ?>" <?php selected($selected_event, $event->ID); ?>>
                    <?php echo esc_html(get_the_title($event)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <input type="text" name="s" placeholder="Rechercher (nom, email)..." value="<?php echo esc_attr($search); ?>">
        
        <button type="submit" class="button">Filtrer</button>
        <a href="<?php echo admin_url('admin.php?page=fb-inscriptions'); ?>" class="button">Réinitialiser</a>
    </form>
    
    <!-- Results -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Événement</th>
                <th>Stand</th>
                <th>Créneau</th>
                <th>Date inscription</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inscriptions)) : ?>
                <tr>
                    <td colspan="8">Aucune inscription trouvée</td>
                </tr>
            <?php else : 
                foreach ($inscriptions as $ins) :
                    $evenement_title = get_the_title($ins->evenement_id);
                    $stand_title = get_the_title($ins->stand_id);
                    $creneau_title = get_the_title($ins->creneau_id);
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($ins->prenom . ' ' . $ins->nom); ?></strong></td>
                        <td><?php echo esc_html($ins->email); ?></td>
                        <td><?php echo esc_html($ins->telephone); ?></td>
                        <td><?php echo esc_html($evenement_title); ?></td>
                        <td><?php echo esc_html($stand_title); ?></td>
                        <td><?php echo esc_html($creneau_title); ?></td>
                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($ins->date_inscription)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=fb-inscriptions&action=cancel&id=' . $ins->id); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('Annuler cette inscription ?')">
                                Annuler
                            </a>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    
    <p class="fb-total">
        Total: <strong><?php echo count($inscriptions); ?></strong> inscription(s)
    </p>
</div>
