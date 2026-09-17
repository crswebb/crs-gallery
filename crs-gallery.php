<?php
/*
Plugin Name: CRS Gallery
Plugin URI: https://github.com/crswebb/crs-gallery
Description: A WordPress plugin for image galleries with a Gutenberg block and lightbox.
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
Author: CRS Webbproduktion AB
Author URI: https://crswebb.se
Text Domain: crs-gallery
Domain Path: /languages
License: MIT
License URI: https://opensource.org/licenses/MIT
*/

if (!defined('ABSPATH')) {
    exit; // Förhindra direkt åtkomst
}

define('CRS_GALLERY_VERSION', '1.0.0');

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

// register_activation_hook does not fire on plugin updates, so also seed the
// capabilities on upgrade. Gated by a stored version so it only runs when the
// plugin version changes.
add_action('admin_init', 'crs_gallery_maybe_upgrade');

function crs_gallery_maybe_upgrade()
{
    if (get_option('crs_gallery_version') === CRS_GALLERY_VERSION) {
        return;
    }

    crs_set_gallery_capabilities();
    update_option('crs_gallery_version', CRS_GALLERY_VERSION);
}

// Translations are loaded automatically by WordPress for plugins hosted on
// WordPress.org (and from the bundled /languages folder via the Domain Path
// header on WordPress 6.7+), so no load_plugin_textdomain() call is needed.
