<?php
/*
Plugin Name: CRS Gallery
Plugin URI: https://github.com/crswebb/crs-gallery
Description: Ett WordPress-plugin för bildgallerier.
Version: 1.0.0
Author: CRS Webbproduktion AB
Author URI: https://crswebb.se
Text Domain: crs-gallery
*/

// Inkludera admin.php
require_once(plugin_dir_path(__FILE__) . 'admin.php');
require_once(plugin_dir_path(__FILE__) . 'crs-gallery-block.php');
add_action('rest_api_init', 'crs_register_gallery_endpoint');
add_action('admin_init', 'crs_set_gallery_capabilities');
add_action('admin_init', 'crs_gallery_admin_enqueue_scripts');
add_action('admin_menu', 'crs_gallery_register_admin_menu');
add_action('init', 'crs_register_gallery_block');



?>