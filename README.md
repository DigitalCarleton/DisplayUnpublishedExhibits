# DisplayUnpublishedExhibits
This Omeka plugin adds the [unpublished_exhibits] shortcode to Omeka.
When you add this shortcode to the text body of a simple page, it will display all private exhibits on that page.
These private exhibits will have images that appear grayed-out and do not have clickable links. One can specify exhibits using tags.
The shortcode is [unpublished_exhibits]
Specify tags by writing [unpublished_exhibits tags="tag1, tag2, tag3"]

## Troubleshooting:
### Warning: imagepng()
You might encounter problems with the images on your server. You might get the warning Warning: imagepng(): Unable to open 'plugins/DisplayUnpublishedExhibits/views/shared/images/gray_fdedf91aed8f0a0e06c3eaac9f516e89.jpg' for writing: Permission denied in /var/www/omeka/plugins/DisplayUnpublishedExhibits/views/shared/private_exhibit.php on line 20.
If this is the case, then change the permissions of the directory DisplayUnpublishedExhibits/views/shared/images/ to 777.
Use the unix command: chmod -R 777 images/
This will let private_exhibit.php create gray thumbnail images and place them in that folder.
