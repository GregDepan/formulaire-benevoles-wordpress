<?php
/**
 * Stats admin page
 */

if (!defined('ABSPATH')) exit;

$selected_event = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Get all events
$events = get_posts(array(
    'post_type' => 'fb_evenement',
    'numberposts' => -1,
    'post_status' => 'any',
));

// Get stats for selected event
if ($selected_event) {
    global $wpdb;
    $table = $wpdb->prefix . 'fb_inscriptions';
    $logs_table = $wpdb->prefix . 'fb_stats_logs';
    
    // Total inscriptions
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE evenement_id = %d AND statut = 'confirmed'",
        $selected_event
    ));
    
    // By stand
    $by_stand = $wpdb->get_results($wpdb->prepare(
        "SELECT stand_id, COUNT(*) as count 
         FROM $table 
         WHERE evenement_id = %d AND statut = 'confirmed'
         GROUP BY stand_id
         ORDER BY count DESC",
        $selected_event
    ));
    
    // By hour (peak times)
    $by_hour = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE_FORMAT(date_inscription, '%%H:00') as hour, COUNT(*) as count
         FROM $table
         WHERE evenement_id = %d AND statut = 'confirmed'
         GROUP BY hour
         ORDER BY hour",
        $selected_event
    ));
    
    // Daily inscriptions
    $daily = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(date_inscription) as date, COUNT(*) as count
         FROM $table
         WHERE evenement_id = %d AND statut = 'confirmed'
         GROUP BY date
         ORDER BY date",
        $selected_event
    ));
}
?>

<div class="wrap fb-admin">
    <h1>Statistiques</h1>
    
    <!-- Event selector -->
    <form method="GET" class="fb-filters">
        <input type="hidden" name="page" value="fb-stats">
        <select name="event_id" onchange="this.form.submit()">
            <option value="0">Sélectionner un événement</option>
            <?php foreach ($events as $event) : ?>
                <option value="<?php echo $event->ID; ?>" <?php selected($selected_event, $event->ID); ?>>
                    <?php echo esc_html(get_the_title($event)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <?php if ($selected_event) : ?>
        <!-- Overview cards -->
        <div class="fb-dashboard-cards">
            <div class="fb-card">
                <h3>Total inscriptions</h3>
                <div class="fb-card-value"><?php echo $total; ?></div>
            </div>
        </div>
        
        <!-- By stand -->
        <div class="fb-stats-section">
            <h2>Inscriptions par stand</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Stand</th>
                        <th>Nombre d'inscrits</th>
                        <th>% du total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_stand as $row) : 
                        $stand_name = get_the_title($row->stand_id);
                        $percent = $total > 0 ? round(($row->count / $total) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($stand_name); ?></strong></td>
                            <td><?php echo $row->count; ?></td>
                            <td>
                                <div class="fb-progress-bar">
                                    <div class="fb-progress" style="width: <?php echo $percent; ?>%"></div>
                                    <span><?php echo $percent; ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Daily chart placeholder -->
        <div class="fb-stats-section">
            <h2>Inscriptions par jour</h2>
            <div id="fb-daily-chart" style="height: 300px; background: #f6f7f7; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #646970;">
                Graphique des inscriptions par jour (à implémenter avec Chart.js)
            </div>
        </div>
        
        <!-- Peak hours -->
        <div class="fb-stats-section">
            <h2>Heures d'inscription (pic d'affluence)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Inscriptions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_hour as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->hour); ?></td>
                            <td><?php echo $row->count; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p>Sélectionnez un événement pour voir les statistiques.</p>
    <?php endif; ?>
</div>

<style>
.fb-stats-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.fb-stats-section h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.fb-progress-bar {
    display: flex;
    align-items: center;
    height: 20px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
}

.fb-progress {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s;
}

.fb-progress-bar span {
    margin-left: 10px;
    font-size: 12px;
    font-weight: 600;
}
</style>
