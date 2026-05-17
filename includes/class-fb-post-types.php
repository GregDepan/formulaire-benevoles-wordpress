<?php
/**
 * Register Custom Post Types
 */

class FB_Post_Types {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Register Évvenement CPT
     */
    public function register_evenement_cpt() {
        $labels = array(
            'name'                  => 'Événements',
            'singular_name'         => 'Événement',
            'menu_name'             => 'Événements',
            'add_new'               => 'Ajouter un événement',
            'add_new_item'          => 'Ajouter un nouvel événement',
            'edit_item'             => 'Modifier l\'événement',
            'new_item'              => 'Nouvel événement',
            'view_item'             => 'Voir l\'événement',
            'search_items'          => 'Rechercher des événements',
            'not_found'             => 'Aucun événement trouvé',
            'not_found_in_trash'    => 'Aucun événement trouvé dans la corbeille',
            'all_items'             => 'Tous les événements',
            'archive'               => 'Archiver',
            'archived'              => 'Archivé',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // We'll add custom menu
            'query_var'             => true,
            'rewrite'               => array('slug' => 'evenements'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => null,
            'menu_icon'             => 'dashicons-calendar-alt',
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'show_in_rest'          => true, // Enable Gutenberg
            'taxonomies'            => array(),
        );
        
        register_post_type('fb_evenement', $args);
    }
    
    /**
     * Register Stand CPT
     */
    public function register_stand_cpt() {
        $labels = array(
            'name'                  => 'Stands',
            'singular_name'         => 'Stand',
            'menu_name'             => 'Stands',
            'add_new'               => 'Ajouter un stand',
            'add_new_item'          => 'Ajouter un nouveau stand',
            'edit_item'             => 'Modifier le stand',
            'new_item'              => 'Nouveau stand',
            'view_item'             => 'Voir le stand',
            'search_items'          => 'Rechercher des stands',
            'not_found'             => 'Aucun stand trouvé',
            'not_found_in_trash'    => 'Aucun stand trouvé dans la corbeille',
            'all_items'             => 'Tous les stands',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false, // Not publicly accessible
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'query_var'             => false,
            'rewrite'               => false,
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array('title', 'custom-fields'),
            'show_in_rest'          => false, // Disable Gutenberg - use classic editor
        );
        
        register_post_type('fb_stand', $args);
    }
    
    /**
     * Register Créneau CPT
     */
    public function register_creneau_cpt() {
        $labels = array(
            'name'                  => 'Créneaux',
            'singular_name'         => 'Créneau',
            'menu_name'             => 'Créneaux',
            'add_new'               => 'Ajouter un créneau',
            'add_new_item'          => 'Ajouter un nouveau créneau',
            'edit_item'             => 'Modifier le créneau',
            'new_item'              => 'Nouveau créneau',
            'view_item'             => 'Voir le créneau',
            'search_items'          => 'Rechercher des créneaux',
            'not_found'             => 'Aucun créneau trouvé',
            'not_found_in_trash'    => 'Aucun créneau trouvé dans la corbeille',
            'all_items'             => 'Tous les créneaux',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'query_var'             => false,
            'rewrite'               => false,
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => array('title', 'custom-fields'),
            'show_in_rest'          => false,
        );
        
        register_post_type('fb_creneau', $args);
    }
}
