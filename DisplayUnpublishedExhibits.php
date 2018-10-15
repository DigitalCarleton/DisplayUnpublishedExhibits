<?php
class MyPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array('initialize');

    public function hookInitialize()
    {
        add_shortcode('unpublished_exhibits', 'display_unpublished_exhibits_shortcode');
    }

    public function display_unpublished_exhibits_shortcode($args, $view)
    {
        return 'This is a very simple shortcode.';
    }
}

?>
