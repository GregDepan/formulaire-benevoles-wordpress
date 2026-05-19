<?php
/**
 * Plugin Name: Formulaire Bénévoles
 * Plugin URI: https://wp.gb-solution.fr
 * Description: Système complet de gestion d'inscriptions bénévoles pour événements (kermesses, lotos, etc.)
 * Version: 1.0.0
 * Author: GB Solution
 * Author URI: https://gb-solution.fr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: formulaire-benevoles
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FB_VERSION', '1.0.0');
define('FB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('FB_PLUGIN_ADMIN_DIR', FB_PLUGIN_DIR . 'admin/');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'FB_';
    $base_dir = FB_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
class Formulaire_Benevoles {
    
    protected $loader;
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->version = FB_VERSION;
        $this->plugin_name = 'formulaire-benevoles';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_cpt_hooks();
    }
    
    private function load_dependencies() {
        require_once FB_PLUGIN_DIR . 'includes/class-fb-loader.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-i18n.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-database.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-post-types.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-admin.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-public.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-form-handler.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-emails.php';
        require_once FB_PLUGIN_DIR . 'includes/class-fb-ajax.php';
        
        $this->loader = new FB_Loader();
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new FB_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // AJAX handlers (must be loaded early)
        $this->loader->add_action('wp_ajax_fb_save_event_editor', $plugin_admin, 'save_event_editor_ajax');
        
        // Initialize AJAX class to register all AJAX handlers
        new FB_Ajax();
        
        // Add meta boxes
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_event_meta_boxes');
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_stand_meta_boxes');
        
        // Save meta boxes
        $this->loader->add_action('save_post_fb_evenement', $plugin_admin, 'save_event_meta');
        $this->loader->add_action('save_post_fb_stand', $plugin_admin, 'save_stand_meta');
    }
    
    private function define_public_hooks() {
        $plugin_public = new FB_Public($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Template redirect for custom event pages
        $this->loader->add_action('template_include', $plugin_public, 'load_event_template');
        
        // Allow public access to event pages even if site is private
        // Use 'parse_request' with priority 0 - before WordPress checks site visibility
        $this->loader->add_action('parse_request', $plugin_public, 'allow_public_event_access', 0);
        
        // Force HTTP 200 status for event pages - runs after WordPress decides the status
        // Priority 100 ensures this runs after WordPress sets 404 status
        $this->loader->add_action('template_redirect', $plugin_public, 'force_http_200', 100);
        
        // Shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Form handler - instantiate to register hooks
        new FB_Form_Handler();
    }
    
    private function define_cpt_hooks() {
        $post_types = new FB_Post_Types($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('init', $post_types, 'register_evenement_cpt');
        $this->loader->add_action('init', $post_types, 'register_stand_cpt');
        $this->loader->add_action('init', $post_types, 'register_creneau_cpt');
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_version() {
        return $this->version;
    }
    
    public function get_loader() {
        return $this->loader;
    }
}

// Initialize on plugins_loaded
function run_formulaire_benevoles() {
    $plugin = new Formulaire_Benevoles();
    $plugin->get_loader()->run();
    
    // Auto-fix database constraint on every load (idempotent, runs once if needed)
    require_once FB_PLUGIN_DIR . 'includes/class-fb-activator.php';
    FB_Activator::fix_unique_constraint();
}
add_action('plugins_loaded', 'run_formulaire_benevoles');

// Activation hook
register_activation_hook(__FILE__, 'fb_activate');
function fb_activate() {
    require_once FB_PLUGIN_DIR . 'includes/class-fb-activator.php';
    FB_Activator::activate();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'fb_deactivate');
function fb_deactivate() {
    require_once FB_PLUGIN_DIR . 'includes/class-fb-deactivator.php';
    FB_Deactivator::deactivate();
}
