<?php
/**
 * Admin page for editing event with stands and slots
 */

if (!defined('ABSPATH')) exit;

class FB_Event_Editor {
    
    public function render() {
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        $event = $event_id ? get_post($event_id) : null;
        
        if (!$event || $event->post_type !== 'fb_evenement') {
            echo '<div class="notice notice-error"><p>Événement invalide.</p></div>';
            return;
        }
        
        global $wpdb;
        
        // Get stands
        $stands = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.menu_order, 
                    (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_fb_quota_par_creneau') as quota
             FROM {$wpdb->posts} p 
             WHERE p.post_type = 'fb_stand' AND p.post_parent = %d 
             ORDER BY p.menu_order ASC, p.post_title ASC",
            $event_id
        ));
        
        // Get creneaux for each stand
        foreach ($stands as $stand) {
            $stand->creneaux = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_title, p.menu_order,
                        (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_fb_quota_par_creneau') as quota
                 FROM {$wpdb->posts} p 
                 WHERE p.post_type = 'fb_creneau' AND p.post_parent = %d 
                 ORDER BY p.menu_order ASC",
                $stand->ID
            ));
        }
        
        // Get inscriptions count
        $inscriptions_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions WHERE evenement_id = %d",
            $event_id
        ));
        ?>
        
        <style>
        .fb-editor-container {
            max-width: 1200px;
            margin: 20px 20px 20px 0;
        }
        
        .fb-editor-header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .fb-editor-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .fb-editor-stats {
            display: flex;
            gap: 20px;
        }
        
        .fb-stat {
            background: #f0f0f1;
            padding: 10px 20px;
            border-radius: 6px;
            text-align: center;
        }
        
        .fb-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #696cff;
        }
        
        .fb-stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .fb-editor-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .fb-section-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .fb-section-header h2 {
            margin: 0;
            font-size: 18px;
        }
        
        .fb-stands-list {
            padding: 20px;
        }
        
        .fb-stand-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .fb-stand-header {
            background: #f8f7ff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0dfff;
        }
        
        .fb-stand-title-input {
            font-size: 16px;
            font-weight: 600;
            border: none;
            background: transparent;
            width: 300px;
            padding: 5px 0;
        }
        
        .fb-stand-title-input:focus {
            outline: none;
            border-bottom: 2px solid #696cff;
        }
        
        .fb-stand-actions {
            display: flex;
            gap: 10px;
        }
        
        .fb-stand-body {
            padding: 15px 20px;
        }
        
        .fb-quota-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .fb-quota-row label {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }
        
        .fb-quota-input {
            width: 80px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .fb-creneaux-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .fb-creneau-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f7ff;
            border-radius: 6px;
            border: 1px solid #e0dfff;
        }
        
        .fb-creneau-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .fb-creneau-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .fb-exclusion-group-input {
            width: 180px;
            padding: 8px 12px;
            border: 1px solid #ff9800;
            border-radius: 6px;
            font-size: 13px;
            background: #fff8e1;
        }
        
        .fb-exclusion-group-input::placeholder {
            color: #999;
            font-size: 12px;
        }
        
        .fb-creneau-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .fb-creneau-input:focus {
            outline: none;
            border-color: #696cff;
        }
        
        .fb-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .fb-btn-primary {
            background: #696cff;
            color: #fff;
        }
        
        .fb-btn-primary:hover {
            background: #5558e3;
        }
        
        .fb-btn-secondary {
            background: #f0f0f1;
            color: #333;
        }
        
        .fb-btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .fb-btn-danger {
            background: #ff3e1d;
            color: #fff;
        }
        
        .fb-btn-danger:hover {
            background: #e63618;
        }
        
        .fb-btn-icon {
            padding: 6px 10px;
            font-size: 16px;
        }
        
        .fb-add-stand {
            margin: 20px;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .fb-add-stand:hover {
            border-color: #696cff;
            background: #f8f7ff;
        }
        
        .fb-save-bar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            gap: 10px;
            align-items: center;
            z-index: 1000;
        }
        
        .fb-save-status {
            font-size: 14px;
            color: #666;
        }
        
        .fb-save-status.success {
            color: #71dd37;
        }
        
        .fb-save-status.error {
            color: #ff3e1d;
        }
        </style>
        
        <div class="fb-editor-container">
            <div class="fb-editor-header">
                <div>
                    <h1>✏️ <?php echo esc_html(get_the_title($event)); ?></h1>
                    <p style="color: #666; margin: 5px 0 0 0;">
                        ID: <?php echo $event_id; ?> | 
                        <a href="<?php echo get_permalink($event); ?>" target="_blank">Voir le formulaire public →</a>
                    </p>
                </div>
                <div class="fb-editor-stats">
                    <div class="fb-stat">
                        <div class="fb-stat-number"><?php echo count($stands); ?></div>
                        <div class="fb-stat-label">Stands</div>
                    </div>
                    <div class="fb-stat">
                        <div class="fb-stat-number"><?php echo array_sum(array_map(fn($s) => count($s->creneaux), $stands)); ?></div>
                        <div class="fb-stat-label">Créneaux</div>
                    </div>
                    <div class="fb-stat">
                        <div class="fb-stat-number"><?php echo $inscriptions_count; ?></div>
                        <div class="fb-stat-label">Inscriptions</div>
                    </div>
                </div>
            </div>
            
            <form id="fb-event-editor">
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                <?php wp_nonce_field('fb_event_editor', 'fb_editor_nonce'); ?>
                
                <div class="fb-editor-section">
                    <div class="fb-section-header">
                        <h2>🏪 Stands et créneaux</h2>
                        <button type="button" class="fb-btn fb-btn-secondary" onclick="fbAddStand()">
                            + Ajouter un stand
                        </button>
                    </div>
                    
                    <div class="fb-stands-list" id="fb-stands-list">
                        <!-- Stands will be loaded by JavaScript -->
                    </div>
                    
                    <div class="fb-add-stand" onclick="fbAddStand()">
                        <span style="font-size: 24px;">➕</span>
                        <p style="margin: 10px 0 0 0; color: #666;">Ajouter un nouveau stand</p>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="fb-save-bar" id="fb-save-bar" style="display: none;">
            <span class="fb-save-status" id="fb-save-status">Modifications non enregistrées</span>
            <button class="fb-btn fb-btn-secondary" onclick="fbCancelChanges()">Annuler</button>
            <button class="fb-btn fb-btn-primary" onclick="fbSaveChanges()">💾 Enregistrer</button>
        </div>
        
        <script>
        let fbHasChanges = false;
        let fbStandCounter = <?php echo count($stands); ?>;
        
        // ajaxurl is already defined by WordPress
        
        function fbMarkChanged() {
            fbHasChanges = true;
            document.getElementById('fb-save-bar').style.display = 'flex';
            document.getElementById('fb-save-status').textContent = 'Modifications non enregistrées';
            document.getElementById('fb-save-status').className = 'fb-save-status';
        }
        
        function fbAddStand(standId = null, standTitle = 'Nouveau stand', standQuota = 5, creneaux = []) {
            fbStandCounter++;
            const standIdAttr = standId || 'new-' + fbStandCounter;
            
            const standCard = document.createElement('div');
            standCard.className = 'fb-stand-card';
            standCard.dataset.standId = standIdAttr;
            standCard.innerHTML = `
                <div class="fb-stand-header">
                    <input type="text" 
                           class="fb-stand-title-input" 
                           value="${standTitle}" 
                           onchange="fbMarkChanged()"
                           placeholder="Nom du stand">
                    <div class="fb-stand-actions">
                        <button type="button" class="fb-btn fb-btn-danger fb-btn-icon" onclick="fbRemoveStand(this)">🗑️</button>
                    </div>
                </div>
            <div class="fb-stand-body">
                    <div class="fb-quota-row">
                        <label>👥 Quota max par créneau :</label>
                        <input type="number" 
                               class="fb-quota-input" 
                               value="${standQuota}" 
                               min="1" 
                               max="100"
                               onchange="fbMarkChanged()">
                    </div>
                    <h4 style="margin: 15px 0 10px 0; font-size: 14px; color: #666;">Créneaux horaires</h4>
                    <div class="fb-creneaux-list">
                        ${creneaux.map((c, i) => fbRenderCreneau(c.id, c.title, c.quota, c.exclusion_group || '')).join('')}
                    </div>
                    <button type="button" 
                            class="fb-btn fb-btn-secondary" 
                            style="margin-top: 15px;"
                            onclick="fbAddCreneau(this, '${standId}')">
                        + Ajouter un créneau
                    </button>
                </div>
            `;
            
            document.getElementById('fb-stands-list').appendChild(standCard);
            fbMarkChanged();
        }
        
        function fbRenderCreneau(id, title, quota = '', exclusionGroup = '') {
            const idAttr = id || 'new-' + Date.now();
            return `
                <div class="fb-creneau-item" data-creneau-id="${idAttr}">
                    <div class="fb-creneau-row">
                        <input type="text" 
                               class="fb-creneau-input" 
                               value="${title}" 
                               onchange="fbMarkChanged()"
                               placeholder="Ex: 18h15 - 19h">
                        <input type="text" 
                               class="fb-exclusion-group-input" 
                               value="${exclusionGroup}" 
                               onchange="fbMarkChanged()"
                               placeholder="🚫 Groupe (ex: Samedi Matin)">
                        <button type="button" class="fb-btn fb-btn-danger fb-btn-icon" onclick="fbRemoveCreneau(this)">🗑️</button>
                    </div>
                </div>
            `;
        }
        
        function fbAddCreneau(btn, standId) {
            const creneauxList = btn.previousElementSibling;
            const newCreneau = fbRenderCreneau(null, '');
            creneauxList.insertAdjacentHTML('beforeend', newCreneau);
            fbMarkChanged();
        }
        
        function fbRemoveStand(btn) {
            if (confirm('Supprimer ce stand et tous ses créneaux ?')) {
                btn.closest('.fb-stand-card').remove();
                fbMarkChanged();
            }
        }
        
        function fbRemoveCreneau(btn) {
            btn.parentElement.remove();
            fbMarkChanged();
        }
        
        function fbSaveChanges() {
            const formData = {
                action: 'fb_save_event_editor',
                nonce: document.querySelector('[name="fb_editor_nonce"]').value,
                event_id: document.querySelector('[name="event_id"]').value,
                stands: []
            };
            
            document.querySelectorAll('.fb-stand-card').forEach(standCard => {
                const standId = standCard.dataset.standId;
                const title = standCard.querySelector('.fb-stand-title-input').value;
                const quota = standCard.querySelector('.fb-quota-input').value;
                
                const creneaux = [];
                standCard.querySelectorAll('.fb-creneau-item').forEach(creneauItem => {
                    creneaux.push({
                        id: creneauItem.dataset.creneauId || null,
                        title: creneauItem.querySelector('.fb-creneau-input').value,
                        exclusion_group: creneauItem.querySelector('.fb-exclusion-group-input').value || null
                    });
                });
                
                formData.stands.push({
                    id: standId,
                    title: title,
                    quota: quota,
                    creneaux: creneaux
                });
            });
            
            const statusEl = document.getElementById('fb-save-status');
            statusEl.textContent = 'Enregistrement en cours...';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: formData.action,
                    nonce: formData.nonce,
                    event_id: formData.event_id,
                    stands: JSON.stringify(formData.stands)
                })
            })
            .then(r => r.json())
            .then(response => {
                if (response.success) {
                    statusEl.textContent = '✅ Enregistré !';
                    statusEl.className = 'fb-save-status success';
                    fbHasChanges = false;
                    
                    setTimeout(() => {
                        document.getElementById('fb-save-bar').style.display = 'none';
                    }, 2000);
                } else {
                    statusEl.textContent = '❌ Erreur: ' + (response.data?.message || 'Inconnue');
                    statusEl.className = 'fb-save-status error';
                }
            })
            .catch(err => {
                statusEl.textContent = '❌ Erreur de connexion';
                statusEl.className = 'fb-save-status error';
            });
        }
        
        function fbCancelChanges() {
            if (confirm('Annuler toutes les modifications non enregistrées ?')) {
                location.reload();
            }
        }
        
        // Initialize with existing data
        <?php foreach ($stands as $stand): ?>
        fbAddStand(
            <?php echo $stand->ID; ?>,
            '<?php echo esc_js($stand->post_title); ?>',
            <?php echo $stand->quota ?: 5; ?>,
            [<?php 
                foreach ($stand->creneaux as $creneau) {
                    $exclusion_group = get_post_meta($creneau->ID, '_fb_exclusion_group', true);
                    echo '{id: ' . $creneau->ID . ', title: "' . esc_js($creneau->post_title) . '", quota: "' . ($creneau->quota ?: '') . '", exclusion_group: "' . esc_js($exclusion_group) . '"},';
                }
            ?>]
        );
        <?php endforeach; ?>
        
        // Remove the initial empty stands that were rendered
        document.querySelectorAll('.fb-stand-card[data-stand-id^="new-"]').forEach(el => el.remove());
        </script>
        <?php
    }
    
    private function render_stand_card($stand, $event_id) {
        // This is now handled by JS
    }
    
    public function save_event_editor() {
        check_ajax_referer('fb_event_editor', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Non autorisé'));
        }
        
        $event_id = intval($_POST['event_id']);
        // Decode stands from JSON
        $stands = isset($_POST['stands']) ? json_decode(stripslashes($_POST['stands']), true) : array();
        
        if (!is_array($stands)) {
            wp_send_json_error(array('message' => 'Données invalides'));
        }
        
        // Debug log
        error_log('FB Editor Save - Event ID: ' . $event_id);
        error_log('FB Editor Save - Stands count: ' . count($stands));
        
        global $wpdb;
        $results = array('stands_updated' => 0, 'stands_created' => 0, 'stands_deleted' => 0, 'creneaux_updated' => 0, 'creneaux_created' => 0, 'creneaux_deleted' => 0);
        
        // Get all existing stands for this event BEFORE changes
        $existing_stands = $wpdb->get_results($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'fb_stand' AND post_parent = %d AND post_status = 'publish'",
            $event_id
        ));
        $existing_stand_ids = array_column($existing_stands, 'ID');
        $updated_stand_ids = array();
        
        foreach ($stands as $standData) {
            $stand_id = is_numeric($standData['id']) ? intval($standData['id']) : 0;
            $title = sanitize_text_field($standData['title']);
            $quota = intval($standData['quota']);
            
            if (empty($title)) continue;
            
            // Track this stand as updated
            if ($stand_id > 0) {
                $updated_stand_ids[] = $stand_id;
            }
            
            // Create or update stand
            if ($stand_id > 0) {
                // Update existing stand
                wp_update_post(array(
                    'ID' => $stand_id,
                    'post_title' => $title,
                ));
                update_post_meta($stand_id, '_fb_quota_par_creneau', $quota);
                $results['stands_updated']++;
                
                // Get existing creneaux for this stand
                $existing_creneaux = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_title FROM {$wpdb->posts} 
                     WHERE post_type = 'fb_creneau' AND post_parent = %d AND post_status = 'publish'",
                    $stand_id
                ));
                $existing_ids = array_column($existing_creneaux, 'ID');
                $updated_ids = array();
                
                // Update or create creneaux
                $creneaux = isset($standData['creneaux']) ? $standData['creneaux'] : array();
                $menu_order = 0;
                
                foreach ($creneaux as $creneauData) {
                    $creneau_title = sanitize_text_field($creneauData['title']);
                    $creneau_id = isset($creneauData['id']) && is_numeric($creneauData['id']) ? intval($creneauData['id']) : 0;
                    $exclusion_group = isset($creneauData['exclusion_group']) ? sanitize_text_field($creneauData['exclusion_group']) : '';
                    
                    if (empty($creneau_title)) continue;
                    
                    if ($creneau_id > 0 && in_array($creneau_id, $existing_ids)) {
                        // Update existing creneau
                        wp_update_post(array(
                            'ID' => $creneau_id,
                            'post_title' => $creneau_title,
                            'menu_order' => $menu_order++,
                        ));
                        update_post_meta($creneau_id, '_fb_exclusion_group', $exclusion_group);
                        $updated_ids[] = $creneau_id;
                        $results['creneaux_updated']++;
                    } else {
                        // Create new creneau
                        $new_creneau_id = wp_insert_post(array(
                            'post_title' => $creneau_title,
                            'post_type' => 'fb_creneau',
                            'post_parent' => $stand_id,
                            'post_status' => 'publish',
                            'menu_order' => $menu_order++,
                        ));
                        if ($new_creneau_id && !is_wp_error($new_creneau_id)) {
                            update_post_meta($new_creneau_id, '_fb_exclusion_group', $exclusion_group);
                            $updated_ids[] = $new_creneau_id;
                            $results['creneaux_created']++;
                        }
                    }
                }
                
                // Delete creneaux that are no longer in the list
                $to_delete = array_diff($existing_ids, $updated_ids);
                foreach ($to_delete as $del_id) {
                    wp_delete_post($del_id, true);
                    $results['creneaux_deleted']++;
                }
                
            } else {
                // Create new stand
                $stand_id = wp_insert_post(array(
                    'post_title' => $title,
                    'post_type' => 'fb_stand',
                    'post_parent' => $event_id,
                    'post_status' => 'publish',
                ));
                if ($stand_id && !is_wp_error($stand_id)) {
                    $updated_stand_ids[] = $stand_id;
                    update_post_meta($stand_id, '_fb_quota_par_creneau', $quota);
                    $results['stands_created']++;
                    
                    // Create creneaux for new stand
                    $creneaux = isset($standData['creneaux']) ? $standData['creneaux'] : array();
                    $menu_order = 0;
                    
                    foreach ($creneaux as $creneauData) {
                        $creneau_title = sanitize_text_field($creneauData['title']);
                        $exclusion_group = isset($creneauData['exclusion_group']) ? sanitize_text_field($creneauData['exclusion_group']) : '';
                        if (empty($creneau_title)) continue;
                        
                        $creneau_id = wp_insert_post(array(
                            'post_title' => $creneau_title,
                            'post_type' => 'fb_creneau',
                            'post_parent' => $stand_id,
                            'post_status' => 'publish',
                            'menu_order' => $menu_order++,
                        ));
                        
                        if ($creneau_id && !is_wp_error($creneau_id)) {
                            update_post_meta($creneau_id, '_fb_exclusion_group', $exclusion_group);
                            $results['creneaux_created']++;
                        }
                    }
                }
            }
        }
        
        // Delete stands that are no longer in the list
        $to_delete_stands = array_diff($existing_stand_ids, $updated_stand_ids);
        foreach ($to_delete_stands as $del_stand_id) {
            // First delete all creneaux for this stand
            $stand_creneaux = $wpdb->get_results($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                 WHERE post_type = 'fb_creneau' AND post_parent = %d AND post_status = 'publish'",
                $del_stand_id
            ));
            foreach ($stand_creneaux as $creneau) {
                wp_delete_post($creneau->ID, true);
                $results['creneaux_deleted']++;
            }
            // Then delete the stand
            wp_delete_post($del_stand_id, true);
            $results['stands_deleted']++;
        }
        
        error_log('FB Editor Save - Results: ' . json_encode($results));
        wp_send_json_success(array('message' => 'Enregistré avec succès', 'results' => $results));
    }
}
