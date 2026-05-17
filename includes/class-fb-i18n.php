<?php
/**
 * Define internationalization functionality.
 *
 * @package    Formulaire_Benevoles
 * @subpackage Formulaire_Benevoles/includes
 * @author     Grégory <gregory@depanordi-bordeaux.fr>
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define internationalization functionality.
 *
 * Loads and registers the domain for translation.
 *
 * @since    1.0.0
 */
class Formulaire_Benevoles_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'formulaire-benevoles',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
