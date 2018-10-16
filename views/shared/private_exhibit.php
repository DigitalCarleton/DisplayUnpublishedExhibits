<div class="exhibit record">
    <?php
    if (!$exhibit) {
        $exhibit = get_current_record('exhibit');
    }
    ?> <h3> <?php echo html_escape($exhibit->title); ?> </h3>

    <?php if ($exhibitImage = record_image($exhibit)):
        $first_pos = strpos($exhibitImage, 'omeka/');
        $second_pos = strpos($exhibitImage, "\"", $first_pos);
        $length = $second_pos - $first_pos;
        $image_link = substr($exhibitImage, $first_pos + 6, $length - 6);
        $image_resource_id = imagecreatefromstring(file_get_contents($image_link));
        $filtered_id = imagefilter($image_resource_id, IMG_FILTER_GRAYSCALE);
        $second_slash_pos = strpos($image_link, "/", strpos($image_link, "/") + 1);
        $gray_filename_path = 'plugins/DisplayUnpublishedExhibits/views/shared/images/' . 'gray_'
          . substr($image_link, $second_slash_pos + 1);
        if(!(file_exists($gray_filename_path))){
          header('Content-Type: image/png');
          imagepng($image_resource_id, $gray_filename_path, 9);
        }
        echo '<div class="image" >' . '<img src=' . $gray_filename_path . ' />' . '</div>';
    endif; ?>
    <p><?php echo snippet_by_word_count(metadata($exhibit, 'description', array('no_escape' => true))); ?></p>
</div>
