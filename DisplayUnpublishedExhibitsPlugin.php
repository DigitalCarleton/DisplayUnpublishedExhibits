<?php
class DisplayUnpublishedExhibitsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array('initialize');

    public function hookInitialize()
    {
      add_shortcode('unpublished_exhibits', array($this, 'display_unpublished_exhibits_shortcode'));
    }

    public function display_unpublished_exhibits_shortcode($args, $view)
    {
        $db = get_db();
        $table = $db->getTable('Exhibit');
        $privateExhibits = $table->fetchObjects('SELECT * FROM omeka_exhibits WHERE public = 0');
        $content = '<h2> Unpublished Exhibits </h2>';;
        $titles = '';
        $images = '';
        $descriptions = '';
        foreach ($privateExhibits as $exhibit) {
            $content .= $view->partial('private_exhibit.php', array('exhibit' => $exhibit));
            release_object($exhibit);
        }
        return $content;
    }
}
?>
