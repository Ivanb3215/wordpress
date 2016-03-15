<?php
/**
 *
 * Official Spot.IM WP Plugin
 *
 * @package   Spot_IM
 * @author      Spot.IM (@Spot_IM) <support@spot.im>
 * @license     GPLv2
 * @link          https://www.spot.im
 * @copyright 2014 Spot.IM Ltd.
 *
 * @wordpress-plugin
 * Plugin Name:     Spot.IM
 * Plugin URI:         https://www.spot.im
 * Description:       Official Spot.IM WP Plugin
 * Version:             1.2
 * Author:              Spot.IM (@Spot_IM)
 * Author URI:        https://github.com/SpotIM
 * License:             GPLv2
 * License URI:       license.txt
 * Text Domain:     SpotIM
 * GitHub Plugin URI: git@github.com:SpotIM/WP-Plugin.git
 *
 */

require_once(dirname(__FILE__) . '/helpers/utils.php');
require_once(dirname(__FILE__) . '/helpers/form.php');

class SpotIM_Options extends FormHelper {

    public $options, $json_settings;

    public function __construct() {
        $this->json_settings = json_decode(
            file_get_contents( dirname(__FILE__) . '/data.json', true)
        );

        $this->options = get_option($this->json_settings->option_name);

        if (is_admin()) {
            $this->register_form($this->json_settings);
        }
    }

    public function add_menu_page() {
        add_options_page(
            $this->json_settings->page_options->page_title,
            $this->json_settings->page_options->menu_title,
            $this->json_settings->page_options->capability,
            'spotim.php',
            array($this, $this->json_settings->page_options->view)
        );
    }

    public function register_form($data) {
        register_setting($data->option_name, $data->option_name, array($this, $data->validation_callback));

        foreach ($data->sections as $section) {
            add_settings_section(
                $section->id,
                $section->title,
                array($this, 'like_anonymous_function_but_not'),
                'spotim.php'
            );

            foreach ($section->fields as $field) {
                $value  = !empty($this->options[$field->id]) ? $this->options[$field->id] : '';

                $args = array(
                    'id' => $field->id,
                    'type' => $field->type,
                    'group' => $data->option_name,
                    'value' => $value
                );

                if ($field->type === 'select') {
                    $args['options'] = $field->select_options;
                }

                if (isset($field->description)) {
                    $args['description'] = $field->description;
                }

                add_settings_field(
                    $field->id,
                    $field->title,
                    array($this, $field->callback),
                    'spotim.php',
                    $field->section,
                    $args
                );
            }
        }
    }

    public function like_anonymous_function_but_not() {}

    public function validate_form($options) {
        return $options;
    }

    // Views
    public function admin_view() {
        $this->addView(dirname(__FILE__) . '/views/options.php');
    }
}

function spotim_admin_menu() {
    $spotim = new SpotIM_Options();
    $spotim->add_menu_page();
}

function spotim_admin_init() {
    new SpotIM_Options();
}

function spotim_wp_footer() {
    $spotim = new SpotIM_Options();
    if (!empty($spotim->options['spotim_id'])) {
        $spotim->addTemplate(dirname(__FILE__) . '/views/embed.html', $spotim->options);
    }
}

if (is_admin()) {
    add_action('admin_menu', 'spotim_admin_menu');
    add_action('admin_init', 'spotim_admin_init');
} else {
    add_action('wp_footer', 'spotim_wp_footer');
}
