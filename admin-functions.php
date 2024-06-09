<?php
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
    // Validera formulärdata
    $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
    $gallery_name = sanitize_text_field($_POST['gallery_name']);
    $gallery_description = sanitize_textarea_field($_POST['gallery_description']);

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

            // Spara bildernas ID i galleriets metadata
            update_post_meta($gallery_id, 'crs_gallery_images', $attachment_ids);
        }

    }
}

function crs_upload_gallery_image($image_file)
{
    $upload_dir = wp_upload_dir();
    $image_name = sanitize_file_name($image_file['name']);
    $image_path = $upload_dir['path'] . '/' . $image_name;

    if (move_uploaded_file($image_file['tmp_name'], $image_path)) {
        $attachment = [
            'guid' => $upload_dir['url'] . '/' . $image_name,
            'post_mime_type' => $image_file['type'],
            'post_title' => $image_name,
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $image_path);
        if (!is_wp_error($attachment_id)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $image_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);

            return $attachment_id;
        }
    }

    return false;
}

function crs_display_gallery_images($gallery_id)
{
    $attachment_ids = get_post_meta($gallery_id, 'crs_gallery_images', true);

    if (!empty($attachment_ids)) {
        foreach ($attachment_ids as $attachment_id) {
            $image_src = wp_get_attachment_image_src($attachment_id, 'thumbnail');
            if ($image_src) {
                $image_url = $image_src[0];
                $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                ?>
                <div class="crs-gallery-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>">
                </div>
                <?php
            }
        }
    }
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