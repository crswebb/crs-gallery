<?php
/*
Plugin Name: CRS Gallery
Plugin URI: https://github.com/crswebb/crs-gallery
Description: Ett WordPress-plugin för bildgallerier.
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
Author: CRS Webbproduktion AB
Author URI: https://crswebb.se
Text Domain: crs-gallery
License: MIT
License URI: https://opensource.org/licenses/MIT
*/

if (!defined('ABSPATH')) {
    exit; // Förhindra direkt åtkomst
}

// Inkludera admin.php
require_once(plugin_dir_path(__FILE__) . 'admin.php');
require_once(plugin_dir_path(__FILE__) . 'crs-gallery-block.php');

add_action('init', 'crs_register_gallery_post_type');
add_action('rest_api_init', 'crs_register_gallery_endpoint');
add_action('admin_enqueue_scripts', 'crs_gallery_admin_enqueue_scripts');
add_action('admin_menu', 'crs_gallery_register_admin_menu');
add_action('init', 'crs_register_gallery_block');

// Sätt behörigheter en gång vid aktivering, inte vid varje anrop.
register_activation_hook(__FILE__, 'crs_gallery_activate');

function crs_gallery_activate()
{
    crs_register_gallery_post_type();
    crs_set_gallery_capabilities();
}
