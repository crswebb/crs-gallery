<?php
if (!defined('ABSPATH')) {
    exit; // Förhindra direkt åtkomst
}

// Registrera anpassad inläggstyp för gallerier
function crs_register_gallery_post_type()
{
    register_post_type('crs_gallery', array(
        'labels' => array(
            'name' => __('Gallerier', 'crs-gallery'),
            'singular_name' => __('Galleri', 'crs-gallery'),
        ),
        'public' => false,
        'show_ui' => false,
        'has_archive' => false,
        'hierarchical' => false,
        'supports' => array('title', 'editor'),
        'capability_type' => array('crs_gallery', 'crs_galleries'),
        'map_meta_cap' => true,
    ));
}

// Funktion för att hämta alla gallerier från databasen
function crs_get_all_galleries()
{
    $args = array(
        'post_type' => 'crs_gallery',
        'posts_per_page' => -1
    );

    $galleries = get_posts($args);

    return $galleries;
}
function crs_get_gallery_by_id($gallery_id)
{
    $gallery = get_post($gallery_id);

    if ($gallery && $gallery->post_type === 'crs_gallery') {
        return $gallery;
    }

    return null;
}

// Funktion för att visa listan över befintliga gallerier
function crs_display_galleries_list()
{
    $galleries = crs_get_all_galleries();

    if ($galleries) {
        echo '<ul>';
        foreach ($galleries as $gallery) {
            $gallery_id = $gallery->ID;
            $gallery_name = $gallery->post_title;
            $edit_link = add_query_arg('edit', $gallery_id, admin_url('admin.php?page=crs-gallery-admin'));

            echo '<li>';
            echo '<strong>' . esc_html($gallery_name) . '</strong>';
            echo '<span class="gallery-actions">';
            echo '<a href="' . esc_url($edit_link) . '">Redigera</a>';
            echo '</span>';
            echo '<div class="gallery-images">' . crs_display_gallery_images($gallery_id) . '</div>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Inga gallerier hittades.</p>';
    }
}

// Funktion för att sätta behörigheter för galleri-inläggstyp
function crs_set_gallery_capabilities()
{
    $role = get_role('administrator'); // Anpassa rollnamnet om det behövs

    if (!$role) {
        return;
    }

    // Ge administratörsrollen behörighet att redigera galleri-inlägg
    $role->add_cap('edit_crs_gallery');
    $role->add_cap('edit_crs_galleries');
    $role->add_cap('edit_others_crs_galleries');
    $role->add_cap('publish_crs_galleries');
    $role->add_cap('read_crs_gallery');
    $role->add_cap('read_private_crs_galleries');
    $role->add_cap('delete_crs_galleries');
    $role->add_cap('delete_private_crs_galleries');
    $role->add_cap('delete_published_crs_galleries');
    $role->add_cap('delete_others_crs_galleries');
    $role->add_cap('edit_private_crs_galleries');
    $role->add_cap('edit_published_crs_galleries');
}


// Funktion för att inkludera CSS och JavaScript för administrationsidan
function crs_gallery_admin_enqueue_scripts()
{
    wp_enqueue_style('crs-gallery-admin-styles', plugin_dir_url(__FILE__) . 'admin.css');
    wp_enqueue_script('crs-gallery-admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0', true);
}

function crs_save_gallery()
{
    // Verifiera nonce (skydd mot CSRF)
    if (!isset($_POST['crs_gallery_nonce']) || !wp_verify_nonce($_POST['crs_gallery_nonce'], 'crs_save_gallery')) {
        wp_die(__('Säkerhetskontrollen misslyckades. Försök igen.', 'crs-gallery'));
    }

    // Kontrollera behörighet
    if (!current_user_can('manage_options')) {
        wp_die(__('Du har inte tillräckliga behörigheter att spara gallerier.', 'crs-gallery'));
    }

    // Validera formulärdata
    $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
    $gallery_name = isset($_POST['gallery_name']) ? sanitize_text_field($_POST['gallery_name']) : '';
    $gallery_description = isset($_POST['gallery_description']) ? sanitize_textarea_field($_POST['gallery_description']) : '';

    // Lägg till ytterligare validering efter behov

    // Spara/uppdatera galleriet i databasen
    $gallery_data = array(
        'ID' => $gallery_id,
        'post_title' => $gallery_name,
        'post_content' => $gallery_description,
    );

    if ($gallery_id > 0) {
        // Uppdatera befintligt galleri
        $updated = wp_update_post($gallery_data);
        crs_upload_gallery_images($gallery_id);

        if ($updated) {
            // Visa meddelande om att galleriet har uppdaterats
            echo '<div class="updated"><p>Galleriet har uppdaterats.</p></div>';
        } else {
            // Visa felmeddelande om uppdatering misslyckades
            echo '<div class="error"><p>Det uppstod ett fel. Galleriet kunde inte uppdateras.</p></div>';
        }
    } else {
        // Skapa ett nytt galleri
        $gallery_post = array(
            'post_title' => $gallery_name,
            'post_content' => $gallery_description,
            'post_status' => 'publish',
            'post_type' => 'crs_gallery'
        );

        $gallery_id = wp_insert_post($gallery_post);

        if ($gallery_id) {
            // Spara galleridata som anpassad fältmeta
            add_post_meta($gallery_id, 'crs_gallery_data', $gallery_data, true);

            // Ladda upp och spara bilderna
            crs_upload_gallery_images($gallery_id);

            // Visa meddelande om att galleriet har sparats
            echo '<div class="updated"><p>Galleriet har sparats.</p></div>';
        } else {
            // Visa felmeddelande om sparandet misslyckades
            echo '<div class="error"><p>Det uppstod ett fel. Galleriet kunde inte sparas.</p></div>';
        }
    }
}

function crs_upload_gallery_images($gallery_id)
{
    if (!empty($_FILES['gallery_images']['name'])) {
        $attachment_ids = array();

        $gallery_images = $_FILES['gallery_images'];

        if (!empty($gallery_images['name'][0])) {
            $attachment_ids = [];

            foreach ($gallery_images['name'] as $index => $name) {
                $image_file = [
                    'name' => $gallery_images['name'][$index],
                    'type' => $gallery_images['type'][$index],
                    'tmp_name' => $gallery_images['tmp_name'][$index],
                    'error' => $gallery_images['error'][$index],
                    'size' => $gallery_images['size'][$index],
                ];

                $attachment_id = crs_upload_gallery_image($image_file);
                if ($attachment_id) {
                    $attachment_ids[] = $attachment_id;
                }
            }

            // Slå ihop med befintliga bilder så att redan uppladdade bilder inte skrivs över
            $existing_ids = get_post_meta($gallery_id, 'crs_gallery_images', true);
            if (!is_array($existing_ids)) {
                $existing_ids = array();
            }
            $attachment_ids = array_merge($existing_ids, $attachment_ids);

            // Spara bildernas ID i galleriets metadata
            update_post_meta($gallery_id, 'crs_gallery_images', $attachment_ids);
        }

    }
}

function crs_upload_gallery_image($image_file)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Tillåt endast bildfiler – wp_handle_upload validerar den faktiska filtypen
    $allowed_mimes = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'gif'          => 'image/gif',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
    );

    $upload = wp_handle_upload($image_file, array(
        'test_form' => false,
        'mimes'     => $allowed_mimes,
    ));

    if (!$upload || isset($upload['error'])) {
        return false;
    }

    $attachment = array(
        'guid'           => $upload['url'],
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    $attachment_id = wp_insert_attachment($attachment, $upload['file']);
    if (is_wp_error($attachment_id) || !$attachment_id) {
        return false;
    }

    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attachment_data);

    return $attachment_id;
}

function crs_display_gallery_images($gallery_id)
{
    $attachment_ids = get_post_meta($gallery_id, 'crs_gallery_images', true);

    $output = '';

    if (!empty($attachment_ids) && is_array($attachment_ids)) {
        foreach ($attachment_ids as $attachment_id) {
            $image_src = wp_get_attachment_image_src($attachment_id, 'thumbnail');
            if ($image_src) {
                $image_url = $image_src[0];
                $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                $output .= '<div class="crs-gallery-image">';
                $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '">';
                $output .= '</div>';
            }
        }
    }

    return $output;
}


// Registrera meny och undermeny för galleriadministration
function crs_gallery_register_admin_menu()
{
    add_menu_page(
        'CRS Gallery',
        // Sidtitel
        'CRS Gallery',
        // Menynamn
        'manage_options',
        // Tillstånd för att visa menyn
        'crs-gallery-admin',
        // Slug för menyn
        'crs_gallery_admin_page' // Funktion för att visa sidan
    );
}

?>