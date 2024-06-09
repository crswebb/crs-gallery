<?php
require_once(plugin_dir_path(__FILE__) . 'admin-functions.php');


// Funktion för att visa administrationsidan för galleriet
function crs_gallery_admin_page()
{
    // Kolla behörighet
    if (!current_user_can('manage_options')) {
        return;
    }

    // Hantera formuläret för att lägga till/redigera galleri
    if (isset($_POST['submit_gallery'])) {
        crs_save_gallery();
    }

    // Hämta befintligt galleri från databasen för redigering, om en redigerings-ID är angiven
    $gallery_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    // Kontrollera om användaren har tillräckliga behörigheter för att redigera gallerier
    if ($gallery_id > 0 && !current_user_can('edit_crs_gallery', $gallery_id)) {
        wp_die(__('Du har inte tillräckliga behörigheter att redigera detta galleri.', 'crs-gallery'));
    }
    $gallery = null;
    if ($gallery_id > 0) {
        $gallery = crs_get_gallery_by_id($gallery_id);
    }

    // Visa formulär för att lägga till/redigera galleri
    ?>
    <div class="wrap">
        <h1>
            <?php echo ($gallery_id > 0) ? 'Redigera Galleri' : 'Lägg till Galleri'; ?>
        </h1>

        <form method="post" action="" enctype="multipart/form-data">
            <label for="gallery-name">Gallerinamn:</label>
            <input type="text" name="gallery_name" id="gallery-name"
                value="<?php echo ($gallery) ? esc_attr($gallery->post_title) : ''; ?>" required>

            <label for="gallery-description">Beskrivning:</label>
            <textarea name="gallery_description" id="gallery-description"
                rows="4"><?php echo ($gallery) ? esc_textarea($gallery->post_content) : ''; ?></textarea>

            <!-- Lägg till andra inställningar och fält efter behov -->

            <label for="gallery-images">Bilder:</label>
            <input type="file" name="gallery_images[]" id="gallery-images" multiple>


            <input type="hidden" name="gallery_id" value="<?php echo $gallery_id; ?>">
            <input type="submit" name="submit_gallery" class="button button-primary"
                value="<?php echo ($gallery_id > 0) ? 'Uppdatera' : 'Spara'; ?>">
        </form>
    </div>

    <div class="galleries-list">
        <h2>Befintliga gallerier</h2>
        <?php crs_display_galleries_list(); ?>
    </div>

    <?php
}
?>