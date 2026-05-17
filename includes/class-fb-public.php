<?php
/**
 * The public-facing functionality of the plugin
 */

class FB_Public {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-public',
            FB_PLUGIN_URL . 'public/css/fb-public.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-public',
            FB_PLUGIN_URL . 'public/js/fb-public.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script($this->plugin_name . '-public', 'fbPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fb_public_nonce'),
            'strings' => array(
                'slotFull' => 'Ce créneau est complet',
                'conflictError' => 'Vous avez déjà sélectionné un créneau qui chevauche celui-ci',
                'requiredField' => 'Ce champ est obligatoire',
                'invalidEmail' => 'Email invalide',
                'submitting' => 'Inscription en cours...',
                'success' => 'Inscription confirmée !',
                'error' => 'Une erreur est survenue',
            )
        ));
    }
    
    /**
     * Load custom template for single event
     */
    public function load_event_template($template) {
        if (is_singular('fb_evenement')) {
            $custom_template = FB_PLUGIN_DIR . 'templates/single-fb_evenement.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('fb_formulaire', array($this, 'render_form_shortcode'));
        add_shortcode('fb_profil', array($this, 'render_profile_shortcode'));
        add_shortcode('fb_liste_evenements', array($this, 'render_events_list_shortcode'));
    }
    
    /**
     * Render form shortcode
     */
    public function render_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0,
        ), $atts);
        
        ob_start();
        include FB_PLUGIN_DIR . 'public/partials/fb-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render profile shortcode
     */
    public function render_profile_shortcode($atts) {
        ob_start();
        include FB_PLUGIN_DIR . 'public/partials/fb-profile.php';
        return ob_get_clean();
    }
    
    /**
     * Render events list shortcode
     */
    public function render_events_list_shortcode($atts) {
        ob_start();
        include FB_PLUGIN_DIR . 'public/partials/fb-events-list.php';
        return ob_get_clean();
    }
}
