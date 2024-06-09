<?php
function crs_register_gallery_endpoint()
{
    register_rest_route(
        'wp/v1',
        '/crs_gallery',
        array(
            'methods' => 'GET',
            'callback' => 'crs_get_galleries',
            'permission_callback' => '__return_true',
        )
    );
}
function crs_get_galleries()
{
    $args = array(
        'post_type' => 'crs_gallery',
        'posts_per_page' => -1,
    );

    $galleries = get_posts($args);

    $data = array();

    foreach ($galleries as $gallery) {
        $data[] = array(
            'id' => $gallery->ID,
            'title' => $gallery->post_title,
        );
    }

    return $data;
}


// Register Gutenberg block
function crs_register_gallery_block()
{
    wp_register_script(
        'crs-gallery-block',
        plugins_url('block.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-components', 'wp-data']
    );

    wp_enqueue_style(
        'crs-gallery-block-style',
        plugins_url('block.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'block.css')
    );

    wp_enqueue_script(
        'crs-lightbox-script',
        plugins_url('lightbox.js', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'lightbox.js'),
        true
    );

    register_block_type('crs/gallery', [
        'editor_script' => 'crs-gallery-block',
        'render_callback' => 'crs_render_gallery_block',
    ]);
}

function crs_render_gallery_block($attributes)
{
    $gallery_id = isset($attributes['galleryId']) ? intval($attributes['galleryId']) : 0;
    $attachment_ids = get_post_meta($gallery_id, 'crs_gallery_images', true);

    if (!empty($attachment_ids)) {
        $images = '';
        foreach ($attachment_ids as $attachment_id) {
            $image_url_full = wp_get_attachment_image_src($attachment_id, 'full');
            $image_url_thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail');
            if ($image_url_full && $image_url_thumbnail) {
                $image_url_full = $image_url_full[0];
                $image_url_thumbnail = $image_url_thumbnail[0];
                $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                $images .= '<div class="crs-gallery-thumbnail">';
                $images .= '<a href="' . esc_url($image_url_full) . '" data-lightbox="gallery">';
                $images .= '<img src="' . esc_url($image_url_thumbnail) . '" alt="' . esc_attr($image_alt) . '" data-large-src="' . esc_url($image_url_full) . '">';
                $images .= '</a>';
                $images .= '</div>';
            }
        }
        $gallery_title = get_the_title($gallery_id); // Hämta galleriets titel
        // Wrapper-div med klassen "gallery-wrapper"
        $output = '<div class="gallery-wrapper">';
        $output .= '<h2>' . esc_html($gallery_title) . '</h2>'; // Lägg till galleriets titel
        $output .= '<div class="crs-gallery-thumbnails">' . $images . '</div>';
        $output .= '</div>';
        return $output;
    }

    return '';
}

?>