<?php
/**
 * The admin-specific functionality of the plugin
 */

class FB_Admin {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            FB_PLUGIN_URL . 'admin/css/fb-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // FullCalendar for calendar view
        wp_enqueue_style(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css',
            array(),
            '6.1.10'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            FB_PLUGIN_URL . 'admin/js/fb-admin.js',
            array('jquery'),
            $this->version,
            false
        );
        
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            array(),
            '6.1.10',
            true
        );
        
        wp_localize_script($this->plugin_name . '-admin', 'fbAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fb_admin_nonce'),
            'strings' => array(
                'confirmClone' => 'Voulez-vous vraiment cloner cet événement ? Les stands et créneaux seront copiés (pas les inscriptions).',
                'confirmDelete' => 'Êtes-vous sûr de vouloir supprimer cet élément ?',
                'quotaReached' => 'Quota atteint',
                'slotComplete' => 'Complet',
            )
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu - use new merged dashboard
        add_menu_page(
            'Formulaire Bénévoles',
            'Bénévoles',
            'manage_options',
            'fb-dashboard',
            array($this, 'display_events_list'),
            'dashicons-groups',
            30
        );
        
        // Add submenu pages
        $this->add_admin_submenu();
        
        // AJAX handler for event editor
        add_action('wp_ajax_fb_save_event_editor', array($this, 'save_event_editor_ajax'));
    }
    
    /**
     * Save event editor via AJAX
     */
    public function save_event_editor_ajax() {
        require_once FB_PLUGIN_DIR . 'admin/class-fb-event-editor.php';
        $editor = new FB_Event_Editor();
        $editor->save_event_editor();
    }
    
    /**
     * Add metaboxes for quota settings
     */
    public function add_quota_metaboxes() {
        // For stands
        add_meta_box(
            'fb-stand-quota',
            'Configuration du stand',
            array($this, 'render_stand_quota_metabox'),
            'fb_stand',
            'side',
            'high'
        );
        
        // For creneaux
        add_meta_box(
            'fb-creneau-quota',
            'Configuration du créneau',
            array($this, 'render_creneau_quota_metabox'),
            'fb_creneau',
            'side',
            'high'
        );
    }
    
    /**
     * Render stand quota metabox
     */
    public function render_stand_quota_metabox($post) {
        wp_nonce_field('fb_stand_quota', 'fb_stand_quota_nonce');
        $quota = get_post_meta($post->ID, '_fb_quota_par_creneau', true);
        if (!$quota) $quota = 5; // Default
        ?>
        <p>
            <label for="fb-quota"><strong>Nombre de bénévoles max par créneau :</strong></label>
            <input type="number" 
                   id="fb-quota" 
                   name="fb_quota_par_creneau" 
                   value="<?php echo esc_attr($quota); ?>" 
                   min="1" 
                   max="100" 
                   style="width: 100%; padding: 8px; margin-top: 5px;">
        </p>
        <p class="description">
            Limite le nombre de bénévoles pour chaque créneau de ce stand.
        </p>
        <?php
    }
    
    /**
     * Render creneau quota metabox
     */
    public function render_creneau_quota_metabox($post) {
        wp_nonce_field('fb_creneau_quota', 'fb_creneau_quota_nonce');
        $quota = get_post_meta($post->ID, '_fb_quota_par_creneau', true);
        $stand_id = wp_get_post_parent_id($post->ID);
        $stand_quota = $stand_id ? get_post_meta($stand_id, '_fb_quota_par_creneau', true) : 5;
        if (!$quota) $quota = $stand_quota; // Inherit from stand
        ?>
        <p>
            <label for="fb-creneau-quota"><strong>Nombre de bénévoles max :</strong></label>
            <input type="number" 
                   id="fb-creneau-quota" 
                   name="fb_creneau_quota" 
                   value="<?php echo esc_attr($quota); ?>" 
                   min="1" 
                   max="100" 
                   style="width: 100%; padding: 8px; margin-top: 5px;">
        </p>
        <p class="description">
            Laisse vide pour utiliser le quota du stand (<?php echo esc_html($stand_quota); ?>).
        </p>
        <?php
    }
    
    /**
     * Save stand quota
     */
    public function save_stand_quota($post_id) {
        if (!isset($_POST['fb_stand_quota_nonce']) || !wp_verify_nonce($_POST['fb_stand_quota_nonce'], 'fb_stand_quota')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['fb_quota_par_creneau'])) {
            update_post_meta($post_id, '_fb_quota_par_creneau', intval($_POST['fb_quota_par_creneau']));
        }
    }
    
    /**
     * Save creneau quota
     */
    public function save_creneau_quota($post_id) {
        if (!isset($_POST['fb_creneau_quota_nonce']) || !wp_verify_nonce($_POST['fb_creneau_quota_nonce'], 'fb_creneau_quota')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['fb_creneau_quota']) && !empty($_POST['fb_creneau_quota'])) {
            update_post_meta($post_id, '_fb_quota_par_creneau', intval($_POST['fb_creneau_quota']));
        } else {
            delete_post_meta($post_id, '_fb_quota_par_creneau'); // Inherit from stand
        }
    }
    
    /**
     * Add admin menu (continued)
     */
    public function add_admin_submenu() {
        // Dashboard - remplace la page d'accueil par la liste des événements
        add_submenu_page(
            'fb-dashboard',
            'Tableau de bord',
            '📊 Tableau de bord',
            'manage_options',
            'fb-dashboard',
            array($this, 'display_events_list')
        );
        
        // Event Editor page (hidden from menu, but must be registered for access)
        add_submenu_page(
            'fb-dashboard',
            'Éditeur d\'événement',
            'Éditeur',
            'manage_options',
            'fb-event-editor',
            array($this, 'display_event_editor')
        );
        
        // Inscriptions submenu
        add_submenu_page(
            'fb-dashboard',
            'Inscriptions',
            'Inscriptions',
            'manage_options',
            'fb-inscriptions',
            array($this, 'display_inscriptions')
        );
        
        // Exports submenu
        add_submenu_page(
            'fb-dashboard',
            'Exports',
            'Exports',
            'manage_options',
            'fb-exports',
            array($this, 'display_exports')
        );
        
        // Statistiques submenu
        add_submenu_page(
            'fb-dashboard',
            'Statistiques',
            'Statistiques',
            'manage_options',
            'fb-stats',
            array($this, 'display_stats')
        );
        
        // Réglages submenu
        add_submenu_page(
            'fb-dashboard',
            'Réglages',
            'Réglages',
            'manage_options',
            'fb-settings',
            array($this, 'display_settings')
        );
    }
    
    /**
     * Display dashboard
     */
    public function display_dashboard() {
        include FB_PLUGIN_DIR . 'admin/partials/fb-dashboard.php';
    }
    
    /**
     * Display events list with full management (merged dashboard + events)
     */
    public function display_events_list() {
        global $wpdb;
        
        // Handle event deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['event_id'])) {
            $this->handle_event_delete(intval($_GET['event_id']));
        }
        
        // Handle event duplication
        if (isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['event_id'])) {
            $this->handle_event_duplicate(intval($_GET['event_id']));
        }
        
        // Handle event creation/update
        if (isset($_POST['fb_create_event']) || isset($_POST['fb_update_event'])) {
            $this->handle_event_save();
        }
        
        // Check if editing single event or creating new
        $edit_event_id = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        $new_event = isset($_GET['action']) && $_GET['action'] === 'new';
        
        if ($edit_event_id) {
            $this->display_event_details($edit_event_id);
            return;
        }
        
        if ($new_event) {
            $this->display_event_details(0); // 0 = new event
            return;
        }
        
        // Get all events
        $events = $wpdb->get_results(
            "SELECT p.ID, p.post_title, p.post_date, p.post_status, 
                    pm.meta_value AS date_limite
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_fb_date_limite'
             WHERE p.post_type = 'fb_evenement'
             ORDER BY p.post_date DESC"
        );
        
        $today = current_time('Y-m-d');
        
        // Get stats
        $total_events = wp_count_posts('fb_evenement')->publish;
        $total_inscriptions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions WHERE statut = 'confirmed'");
        $total_benevoles = $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}fb_inscriptions WHERE statut = 'confirmed'");
        
        echo '<div class="wrap fb-admin">';
        echo '<h1>📊 Tableau de bord - Événements</h1>';
        
        // Header with stats and new event button
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">';
        
        // Stats cards
        echo '<div class="fb-dashboard-cards" style="display: flex; gap: 20px;">';
        
        echo '<div class="fb-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1;">';
        echo '<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Événements actifs</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #696cff;">' . $total_events . '</div>';
        echo '</div>';
        
        echo '<div class="fb-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1;">';
        echo '<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Inscriptions totales</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #71dd37;">' . $total_inscriptions . '</div>';
        echo '</div>';
        
        echo '<div class="fb-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1;">';
        echo '<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Bénévoles uniques</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #ff3e1d;">' . $total_benevoles . '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // New event button
        echo '<div>';
        echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=new') . '" class="button button-primary" style="margin-left: 20px;">';
        echo '<span class="dashicons dashicons-plus-alt"></span> Nouvel événement';
        echo '</a>';
        echo '</div>';
        
        echo '</div>';
        
        // Events list
        if (empty($events)) {
            echo '<div class="notice notice-info"><p>Aucun événement. <a href="' . admin_url('post-new.php?post_type=fb_evenement') . '">Créer votre premier événement</a> !</p></div>';
        } else {
            $past_events = array_filter($events, fn($e) => date('Y-m-d', strtotime($e->post_date)) < $today);
            $future_events = array_filter($events, fn($e) => date('Y-m-d', strtotime($e->post_date)) >= $today);
            
            // Future events - right after stats cards
            if (!empty($future_events)) {
                echo '<h2 style="margin-top: 10px;">🔮 Événements à venir</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th>Titre</th>';
                echo '<th>Date</th>';
                echo '<th>Date limite</th>';
                echo '<th>Inscriptions</th>';
                echo '<th style="width: 250px;">Actions</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ($future_events as $event) {
                    $inscription_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions WHERE evenement_id = %d AND statut = 'confirmed'",
                        $event->ID
                    ));
                    
                    $date_limite_display = $event->date_limite ? date_i18n('d/m/Y', strtotime($event->date_limite)) : '<em>Non définie</em>';
                    $is_past_limite = $event->date_limite && strtotime($event->date_limite) < time();
                    
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($event->post_title) . '</strong></td>';
                    echo '<td>' . date_i18n('d/m/Y', strtotime($event->post_date)) . '</td>';
                    echo '<td>' . $date_limite_display . ($is_past_limite ? ' ⚠️' : '') . '</td>';
                    echo '<td>' . $inscription_count . '</td>';
                    echo '<td>';
                    echo '<a href="' . admin_url('admin.php?page=fb-event-editor&event_id=' . $event->ID) . '" class="button button-primary" style="margin-bottom: 4px;">';
                    echo '<span class="dashicons dashicons-edit"></span> Éditer';
                    echo '</a> ';
                    echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=edit&event_id=' . $event->ID) . '" class="button button-secondary" style="margin-bottom: 4px;">';
                    echo '<span class="dashicons dashicons-admin-settings"></span> Détails';
                    echo '</a> ';
                    echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=duplicate&event_id=' . $event->ID) . '" class="button button-secondary" style="margin-bottom: 4px;" onclick="return confirm(\'Dupliquer cet événement avec tous ses stands et créneaux ?\')">';
                    echo '<span class="dashicons dashicons-admin-page"></span> Dupliquer';
                    echo '</a> ';
                    echo '<a href="' . get_permalink($event->ID) . '" target="_blank" class="button button-secondary" style="margin-bottom: 4px;">';
                    echo '<span class="dashicons dashicons-external"></span> Voir';
                    echo '</a> ';
                    echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=delete&event_id=' . $event->ID) . '" class="button button-link-delete" onclick="return confirm(\'Supprimer cet événement et toutes ses inscriptions ?\')" style="color: #d63638;">';
                    echo '<span class="dashicons dashicons-trash"></span>';
                    echo '</a>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            }
            
            // Past events
            if (!empty($past_events)) {
                echo '<h2 style="margin-top: 30px;">📚 Événements passés</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th>Titre</th>';
                echo '<th>Date</th>';
                echo '<th>Inscriptions</th>';
                echo '<th style="width: 200px;">Actions</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ($past_events as $event) {
                    $inscription_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}fb_inscriptions WHERE evenement_id = %d AND statut = 'confirmed'",
                        $event->ID
                    ));
                    
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($event->post_title) . '</strong></td>';
                    echo '<td>' . date_i18n('d/m/Y', strtotime($event->post_date)) . '</td>';
                    echo '<td>' . $inscription_count . '</td>';
                    echo '<td>';
                    echo '<a href="' . admin_url('admin.php?page=fb-event-editor&event_id=' . $event->ID) . '" class="button button-secondary" style="margin-bottom: 4px;">';
                    echo '<span class="dashicons dashicons-edit"></span> Voir';
                    echo '</a> ';
                    echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=edit&event_id=' . $event->ID) . '" class="button button-secondary" style="margin-bottom: 4px;">';
                    echo '<span class="dashicons dashicons-admin-settings"></span> Détails';
                    echo '</a> ';
                    echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=duplicate&event_id=' . $event->ID) . '" class="button button-secondary" style="margin-bottom: 4px;" onclick="return confirm(\'Dupliquer cet événement avec tous ses stands et créneaux ?\')">';
                    echo '<span class="dashicons dashicons-admin-page"></span> Dupliquer';
                    echo '</a> ';
                    echo '<a href="' . get_permalink($event->ID) . '" target="_blank" class="button button-secondary" style="margin-bottom: 4px;">';
                    echo '<span class="dashicons dashicons-external"></span> Voir public';
                    echo '</a> ';
                    echo '<a href="' . admin_url('admin.php?page=fb-dashboard&action=delete&event_id=' . $event->ID) . '" class="button button-link-delete" onclick="return confirm(\'Supprimer cet événement et toutes ses inscriptions ?\')" style="color: #d63638;">';
                    echo '<span class="dashicons dashicons-trash"></span>';
                    echo '</a>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Display single event details/edit form (or create new)
     */
    private function display_event_details($event_id) {
        $is_new = $event_id === 0;
        $event = $is_new ? null : get_post($event_id);
        
        if (!$is_new && (!$event || $event->post_type !== 'fb_evenement')) {
            wp_die('Événement introuvable');
        }
        
        $date_limite = $is_new ? '' : get_post_meta($event_id, '_fb_date_limite', true);
        $today = current_time('Y-m-d');
        
        echo '<div class="wrap fb-admin">';
        echo '<h1>' . ($is_new ? '➕ Nouvel événement' : '✏️ Modifier l\'événement') . '</h1>';
        
        echo '<form method="POST" style="max-width: 600px;">';
        echo '<input type="hidden" name="' . ($is_new ? 'fb_create_event' : 'fb_update_event') . '" value="1">';
        if (!$is_new) {
            echo '<input type="hidden" name="fb_event_id" value="' . $event_id . '">';
        }
        
        echo '<table class="form-table">';
        
        echo '<tr><th><label for="edit_title">Titre *</label></th>';
        echo '<td><input type="text" name="fb_title" id="edit_title" required value="' . ($is_new ? '' : esc_attr($event->post_title)) . '" style="width: 100%; max-width: 400px;"></td></tr>';
        
        echo '<tr><th><label for="edit_date">Date de l\'événement *</label></th>';
        echo '<td><input type="date" name="fb_date" id="edit_date" required value="' . ($is_new ? $today : date('Y-m-d', strtotime($event->post_date))) . '"></td></tr>';
        
        echo '<tr><th><label for="edit_date_limite">Date limite de réponse</label></th>';
        echo '<td><input type="date" name="fb_date_limite" id="edit_date_limite" value="' . esc_attr($date_limite) . '"></td></tr>';
        echo '<p class="description">Les inscriptions seront automatiquement fermées après cette date.</p>';
        
        echo '<tr><th><label for="edit_description">Description</label></th>';
        echo '<td><textarea name="fb_description" id="edit_description" rows="6" style="width: 100%; max-width: 600px;">' . ($is_new ? '' : esc_textarea($event->post_content)) . '</textarea></td></tr>';
        
        echo '</table>';
        
        echo '<p class="submit">';
        echo '<button type="submit" class="button button-primary">' . ($is_new ? 'Créer l\'événement' : 'Enregistrer les modifications') . '</button> ';
        echo '<a href="' . admin_url('admin.php?page=fb-dashboard') . '" class="button">Retour</a>';
        echo '</p>';
        
        echo '</form>';
        
        if (!$is_new) {
            // Quick link to full editor
            echo '<hr style="margin: 40px 0;">';
            echo '<h2>🛠️ Éditeur complet</h2>';
            echo '<p>Pour modifier les stands et créneaux, utilisez l\'éditeur complet :</p>';
            echo '<a href="' . admin_url('admin.php?page=fb-event-editor&event_id=' . $event_id) . '" class="button button-primary">';
            echo '<span class="dashicons dashicons-edit"></span> Ouvrir l\'éditeur de stands/créneaux';
            echo '</a>';
        }
        
        echo '</div>';
    }
    
    /**
     * Handle event creation/update
     */
    private function handle_event_save() {
        if (!current_user_can('manage_options')) {
            wp_die('Non autorisé');
        }
        
        $title = sanitize_text_field($_POST['fb_title']);
        $date = sanitize_text_field($_POST['fb_date']);
        $date_limite = isset($_POST['fb_date_limite']) && !empty($_POST['fb_date_limite']) ? sanitize_text_field($_POST['fb_date_limite']) : null;
        $description = sanitize_textarea_field($_POST['fb_description']);
        
        $event_id = isset($_POST['fb_event_id']) ? intval($_POST['fb_event_id']) : 0;
        
        if ($event_id) {
            // Update existing
            wp_update_post(array(
                'ID' => $event_id,
                'post_title' => $title,
                'post_date' => $date . ' 00:00:00',
            ));
            
            if ($description) {
                wp_update_post(array(
                    'ID' => $event_id,
                    'post_content' => $description,
                ));
            }
            
            if ($date_limite) {
                update_post_meta($event_id, '_fb_date_limite', $date_limite);
            } else {
                delete_post_meta($event_id, '_fb_date_limite');
            }
            
            add_action('admin_notices', function() use ($title) {
                echo '<div class="notice notice-success"><p>Événement <strong>' . esc_html($title) . '</strong> mis à jour.</p></div>';
            });
        } else {
            // Create new
            $event_id = wp_insert_post(array(
                'post_title' => $title,
                'post_content' => $description,
                'post_type' => 'fb_evenement',
                'post_status' => 'publish',
                'post_date' => $date . ' 00:00:00',
            ));
            
            if ($date_limite) {
                update_post_meta($event_id, '_fb_date_limite', $date_limite);
            }
            
            add_action('admin_notices', function() use ($title) {
                echo '<div class="notice notice-success"><p>Événement <strong>' . esc_html($title) . '</strong> créé.</p></div>';
            });
        }
    }
    
    /**
     * Handle event deletion
     */
    private function handle_event_delete($event_id) {
        if (!current_user_can('manage_options')) {
            wp_die('Non autorisé');
        }
        
        global $wpdb;
        
        // Delete all inscriptions first
        $wpdb->delete($wpdb->prefix . 'fb_inscriptions', array('evenement_id' => $event_id));
        
        // Get all stands for this event and delete them (creneaux will cascade)
        $stands = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'fb_stand' AND post_parent = %d",
            $event_id
        ));
        
        foreach ($stands as $stand_id) {
            wp_delete_post($stand_id, true);
        }
        
        // Delete the event
        wp_delete_post($event_id, true);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Événement et toutes ses données supprimées.</p></div>';
        });
    }
    
    /**
     * Handle event duplication
     */
    private function handle_event_duplicate($event_id) {
        if (!current_user_can('manage_options')) {
            wp_die('Non autorisé');
        }
        
        $old_event = get_post($event_id);
        if (!$old_event || $old_event->post_type !== 'fb_evenement') {
            wp_die('Événement invalide');
        }
        
        global $wpdb;
        $today = current_time('Y-m-d');
        
        // Create new event
        $new_event_id = wp_insert_post(array(
            'post_title' => $old_event->post_title . ' (copie)',
            'post_content' => $old_event->post_content,
            'post_type' => 'fb_evenement',
            'post_status' => 'publish',
            'post_date' => $today . ' 00:00:00',
        ));
        
        // Copy meta
        $date_limite = get_post_meta($event_id, '_fb_date_limite', true);
        if ($date_limite) {
            update_post_meta($new_event_id, '_fb_date_limite', $date_limite);
        }
        
        // Get all stands from old event
        $old_stands = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, menu_order FROM {$wpdb->posts} 
             WHERE post_type = 'fb_stand' AND post_parent = %d 
             ORDER BY menu_order ASC",
            $event_id
        ));
        
        // Duplicate stands and creneaux
        foreach ($old_stands as $old_stand) {
            // Create new stand
            $new_stand_id = wp_insert_post(array(
                'post_title' => $old_stand->post_title,
                'post_type' => 'fb_stand',
                'post_status' => 'publish',
                'post_parent' => $new_event_id,
                'menu_order' => $old_stand->menu_order,
            ));
            
            // Copy stand meta (quota)
            $quota = get_post_meta($old_stand->ID, '_fb_quota_par_creneau', true);
            if ($quota) {
                update_post_meta($new_stand_id, '_fb_quota_par_creneau', $quota);
            }
            
            // Get creneaux for this stand
            $old_creneaux = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_title, menu_order FROM {$wpdb->posts} 
                 WHERE post_type = 'fb_creneau' AND post_parent = %d 
                 ORDER BY menu_order ASC",
                $old_stand->ID
            ));
            
            // Duplicate creneaux
            foreach ($old_creneaux as $old_creneau) {
                $new_creneau_id = wp_insert_post(array(
                    'post_title' => $old_creneau->post_title,
                    'post_type' => 'fb_creneau',
                    'post_status' => 'publish',
                    'post_parent' => $new_stand_id,
                    'menu_order' => $old_creneau->menu_order,
                ));
                
                // Copy creneau meta (quota, exclusion group, hours)
                $creneau_quota = get_post_meta($old_creneau->ID, '_fb_quota_par_creneau', true);
                if ($creneau_quota) {
                    update_post_meta($new_creneau_id, '_fb_quota_par_creneau', $creneau_quota);
                }
                
                $exclusion_group = get_post_meta($old_creneau->ID, '_fb_exclusion_group', true);
                if ($exclusion_group) {
                    update_post_meta($new_creneau_id, '_fb_exclusion_group', $exclusion_group);
                }
                
                $heure_debut = get_post_meta($old_creneau->ID, '_fb_heure_debut', true);
                if ($heure_debut) {
                    update_post_meta($new_creneau_id, '_fb_heure_debut', $heure_debut);
                }
                
                $heure_fin = get_post_meta($old_creneau->ID, '_fb_heure_fin', true);
                if ($heure_fin) {
                    update_post_meta($new_creneau_id, '_fb_heure_fin', $heure_fin);
                }
            }
        }
        
        // Redirect to editor for the new event
        wp_redirect(admin_url('admin.php?page=fb-event-editor&event_id=' . $new_event_id));
        exit;
    }
    
    /**
     * Display single event editor (custom editor, not WordPress)
     */
    public function display_event_editor() {
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        
        if (!$event_id) {
            wp_redirect(admin_url('admin.php?page=fb-events'));
            exit;
        }
        
        require_once FB_PLUGIN_DIR . 'admin/class-fb-event-editor.php';
        $editor = new FB_Event_Editor();
        $editor->render();
    }
    
    /**
     * Display inscriptions list
     */
    public function display_inscriptions() {
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
            $this->handle_cancel_inscription(intval($_GET['id']));
        }
        
        include FB_PLUGIN_DIR . 'admin/partials/fb-inscriptions-list.php';
    }
    
    /**
     * Cancel/delete a single inscription
     */
    private function handle_cancel_inscription($inscription_id) {
        if (!current_user_can('manage_options')) {
            wp_die('Non autorisé');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        // Get inscription details before deleting
        $inscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $inscription_id
        ));
        
        if (!$inscription) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Inscription introuvable.</p></div>';
            });
            return;
        }
        
        // Delete the inscription
        $deleted = $wpdb->delete($table, array('id' => $inscription_id));
        
        if ($deleted) {
            // Log the deletion
            error_log("FB Admin - Inscription #{$inscription_id} cancelled for {$inscription->email}");
            
            // Send notification email to volunteer
            $this->send_cancellation_email($inscription);
            
            add_action('admin_notices', function() use ($inscription) {
                echo '<div class="notice notice-success"><p>Inscription annulée pour <strong>' . 
                     esc_html($inscription->nom . ' ' . $inscription->prenom) . '</strong> (' . 
                     esc_html($inscription->email) . ')</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Erreur lors de l\'annulation.</p></div>';
            });
        }
    }
    
    /**
     * Send cancellation email to volunteer
     */
    private function send_cancellation_email($inscription) {
        $to = $inscription->email;
        $subject = 'Votre inscription a été annulée';
        
        $evenement_title = get_the_title($inscription->evenement_id);
        $stand_title = get_the_title($inscription->stand_id);
        $creneau_title = get_the_title($inscription->creneau_id);
        
        $message = sprintf(
            "Bonjour %s %s,\n\n" .
            "Votre inscription a été annulée.\n\n" .
            "Événement : %s\n" .
            "Stand : %s\n" .
            "Créneau : %s\n\n" .
            "Cordialement,\n" .
            "L'équipe Dépanordi",
            $inscription->prenom,
            $inscription->nom,
            $evenement_title,
            $stand_title,
            $creneau_title
        );
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Display exports page
     */
    public function display_exports() {
        include FB_PLUGIN_DIR . 'admin/partials/fb-exports.php';
    }
    
    /**
     * Display stats page
     */
    public function display_stats() {
        include FB_PLUGIN_DIR . 'admin/partials/fb-stats.php';
    }
    
    /**
     * Display settings page
     */
    public function display_settings() {
        include FB_PLUGIN_DIR . 'admin/partials/fb-settings.php';
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('fb_settings_group', 'fb_default_slot_duration');
        register_setting('fb_settings_group', 'fb_allow_modifications');
        register_setting('fb_settings_group', 'fb_modification_deadline_days');
        register_setting('fb_settings_group', 'fb_email_from_name');
        register_setting('fb_settings_group', 'fb_email_from_address');
        register_setting('fb_settings_group', 'fb_badge_format');
    }
    
    /**
     * Add meta boxes for Évvenement CPT
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'fb_event_details',
            'Détails de l\'événement',
            array($this, 'render_event_details_box'),
            'fb_evenement',
            'normal',
            'high'
        );
        
        add_meta_box(
            'fb_event_stands',
            'Stands de cet événement',
            array($this, 'render_event_stands_box'),
            'fb_evenement',
            'normal',
            'high'
        );
        
        add_meta_box(
            'fb_event_stats',
            'Statistiques',
            array($this, 'render_event_stats_box'),
            'fb_evenement',
            'side',
            'default'
        );
        
        add_meta_box(
            'fb_event_actions',
            'Actions',
            array($this, 'render_event_actions_box'),
            'fb_evenement',
            'side',
            'high'
        );
    }
    
    /**
     * Add meta boxes for Stand CPT
     */
    public function add_stand_meta_boxes() {
        add_meta_box(
            'fb_stand_details',
            'Détails du stand',
            array($this, 'render_stand_details_box'),
            'fb_stand',
            'normal',
            'high'
        );
        
        add_meta_box(
            'fb_stand_creneaux',
            'Créneaux horaires',
            array($this, 'render_stand_creneaux_box'),
            'fb_stand',
            'normal',
            'high'
        );
    }
    
    /**
     * Render event details meta box
     */
    public function render_event_details_box($post) {
        wp_nonce_field('fb_event_details', 'fb_event_details_nonce');
        
        $date_debut = get_post_meta($post->ID, '_fb_date_debut', true);
        $date_fin = get_post_meta($post->ID, '_fb_date_fin', true);
        $delai_inscription = get_post_meta($post->ID, '_fb_delai_inscription', true);
        $lieu = get_post_meta($post->ID, '_fb_lieu', true);
        $description = get_post_meta($post->ID, '_fb_description', true);
        $archived = get_post_meta($post->ID, '_fb_archived', true);
        
        include FB_PLUGIN_DIR . 'admin/partials/meta-boxes/event-details.php';
    }
    
    /**
     * Render event stands meta box
     */
    public function render_event_stands_box($post) {
        $stands = get_posts(array(
            'post_type' => 'fb_stand',
            'numberposts' => -1,
            'post_status' => 'any',
            'meta_key' => '_fb_evenement_id',
            'meta_value' => $post->ID,
        ));
        
        include FB_PLUGIN_DIR . 'admin/partials/meta-boxes/event-stands.php';
    }
    
    /**
     * Render event stats meta box
     */
    public function render_event_stats_box($post) {
        global $wpdb;
        $table = $wpdb->prefix . 'fb_inscriptions';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE evenement_id = %d AND statut = 'confirmed'",
            $post->ID
        ));
        
        $waitlist = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}fb_waitlist WHERE evenement_id = %d AND statut = 'pending'",
            $post->ID
        ));
        
        include FB_PLUGIN_DIR . 'admin/partials/meta-boxes/event-stats.php';
    }
    
    /**
     * Render event actions meta box
     */
    public function render_event_actions_box($post) {
        include FB_PLUGIN_DIR . 'admin/partials/meta-boxes/event-actions.php';
    }
    
    /**
     * Render stand details meta box
     */
    public function render_stand_details_box($post) {
        wp_nonce_field('fb_stand_details', 'fb_stand_details_nonce');
        
        $evenement_id = get_post_meta($post->ID, '_fb_evenement_id', true);
        $quota_par_creneau = get_post_meta($post->ID, '_fb_quota_par_creneau', true);
        $description = get_post_meta($post->ID, '_fb_description', true);
        $couleur = get_post_meta($post->ID, '_fb_couleur', true);
        
        include FB_PLUGIN_DIR . 'admin/partials/meta-boxes/stand-details.php';
    }
    
    /**
     * Render stand creneaux meta box
     */
    public function render_stand_creneaux_box($post) {
        $creneaux = get_posts(array(
            'post_type' => 'fb_creneau',
            'numberposts' => -1,
            'post_status' => 'any',
            'meta_key' => '_fb_stand_id',
            'meta_value' => $post->ID,
            'orderby' => 'meta_value',
            'meta_key' => '_fb_heure_debut',
        ));
        
        include FB_PLUGIN_DIR . 'admin/partials/meta-boxes/stand-creneaux.php';
    }
    
    /**
     * Save event meta
     */
    public function save_event_meta($post_id) {
        if (!isset($_POST['fb_event_details_nonce']) || !wp_verify_nonce($_POST['fb_event_details_nonce'], 'fb_event_details')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['fb_date_debut'])) {
            update_post_meta($post_id, '_fb_date_debut', sanitize_text_field($_POST['fb_date_debut']));
        }
        if (isset($_POST['fb_date_fin'])) {
            update_post_meta($post_id, '_fb_date_fin', sanitize_text_field($_POST['fb_date_fin']));
        }
        if (isset($_POST['fb_delai_inscription'])) {
            update_post_meta($post_id, '_fb_delai_inscription', sanitize_text_field($_POST['fb_delai_inscription']));
        }
        if (isset($_POST['fb_lieu'])) {
            update_post_meta($post_id, '_fb_lieu', sanitize_text_field($_POST['fb_lieu']));
        }
        if (isset($_POST['fb_description'])) {
            update_post_meta($post_id, '_fb_description', sanitize_textarea_field($_POST['fb_description']));
        }
        if (isset($_POST['fb_archived'])) {
            update_post_meta($post_id, '_fb_archived', 1);
        } else {
            delete_post_meta($post_id, '_fb_archived');
        }
    }
    
    /**
     * Save stand meta
     */
    public function save_stand_meta($post_id) {
        if (!isset($_POST['fb_stand_details_nonce']) || !wp_verify_nonce($_POST['fb_stand_details_nonce'], 'fb_stand_details')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        if (isset($_POST['fb_evenement_id'])) {
            update_post_meta($post_id, '_fb_evenement_id', intval($_POST['fb_evenement_id']));
        }
        if (isset($_POST['fb_quota_par_creneau'])) {
            update_post_meta($post_id, '_fb_quota_par_creneau', intval($_POST['fb_quota_par_creneau']));
        }
        if (isset($_POST['fb_description'])) {
            update_post_meta($post_id, '_fb_description', sanitize_textarea_field($_POST['fb_description']));
        }
        if (isset($_POST['fb_couleur'])) {
            update_post_meta($post_id, '_fb_couleur', sanitize_hex_color($_POST['fb_couleur']));
        }
    }
}
