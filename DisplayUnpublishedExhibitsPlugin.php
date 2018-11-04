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
      try {
        $db = get_db();
        $exhibits_table = $db->getTable('Exhibit');
        if (isset($args['tags'])) {
            $tags = $args['tags'];
            $tag_ids = $this->get_tag_ids($tags);
            $tagged_exhibits_ids = $this->get_ids_of_exhibit_records_with_tag_ids($tag_ids);
            $tagged_private_exhibits = $this->get_tagged_private_exhibits_from_ids($tagged_exhibits_ids);
            $private_exhibits = $tagged_private_exhibits;
        } else {
            $private_exhibits = $exhibits_table->fetchObjects('SELECT * FROM omeka_exhibits WHERE public = 0');
        }
        if(!(empty($private_exhibits))){
          $content = $this->get_html_content_using_view_and_exhibits($view, $private_exhibits);
          return $content;
        } else {
          return '';
        }
      }
      catch (Exception $e) {
        debug($e->getMessage());
        return 'TAG' . $args['tags'] . "DID NOT WORK :(" . $e;
      }
    }

    public function get_tag_ids($tags)
    {
      $db = get_db();
      $tag_ids_db_table = $db->getTable('Tag');
      $tags = explode(',', $tags);
      $tag_ids = array();
      foreach ($tags as $tag) {
        $tag_id = $this->get_tag_id($tag, $tag_ids_db_table);
        if ($tag_id){
            $tag_ids[] =$tag_id;
        }
      }
      return $tag_ids;
    }

    public function get_tag_id($tag, $tag_ids_db_table){
      $tag = $this->remove_invisible_characters_from_string($tag);
      $tag_record = $tag_ids_db_table->fetchObject("SELECT * FROM omeka_tags WHERE name = '$tag'");
      if (is_null($tag_record)){
        $tag_id = null;
      } else {
        $tag_id = $tag_record['id'];
      }
      return $tag_id;
    }

    public function remove_invisible_characters_from_string($string){
      $string_array = str_split($string);
      $new_string = array();
      foreach ($string_array as $char){
        if (in_array(strtolower($char), array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t',
        'u','v','w','x','y','z'))){
          $new_string[] = $char;
        }
      }
      $new_string = implode($new_string);
      return $new_string;
    }

    public function get_ids_of_exhibit_records_with_tag_ids($tag_ids)
    {
      if (empty($tag_ids)){
        return array();
      }
      $db = get_db();
      $records_tags_ids_table = $db->getTable('RecordsTags');
      $tagged_exhibits_ids = array();
      foreach ($tag_ids as $tag_id){
          $tagged_exhibit_records = $records_tags_ids_table->fetchObjects("SELECT * FROM omeka_records_tags WHERE tag_id = $tag_id AND record_type = 'Exhibit'");
          foreach ($tagged_exhibit_records as $tagged_exhibit_record){
            if (!(in_array($tagged_exhibit_record['record_id'], $tagged_exhibits_ids))){
              $tagged_exhibits_ids[] = $tagged_exhibit_record['record_id'];
            }
          }
      }
      return $tagged_exhibits_ids;
    }

    public function get_tagged_private_exhibits_from_ids($tagged_exhibits_ids)
    {
      if (empty($tagged_exhibits_ids)){
        return array();
      }
      $db = get_db();
      $exhibits_table = $db->getTable('Exhibit');
      $tagged_private_exhibits = array();
      foreach ($tagged_exhibits_ids as $tagged_exhibit_id){
          $tagged_exhibit = $exhibits_table->fetchObject("SELECT * FROM omeka_exhibits WHERE id = $tagged_exhibit_id");
          if ($tagged_exhibit['public'] == 0){
              $tagged_private_exhibits[] = $tagged_exhibit;
          }
      }
      return $tagged_private_exhibits;
    }

    public function get_private_exhibits_by_filtering_public_exhibits($tagged_exhibits)
    {
      $tagged_private_exhibits = array();
      foreach ($tagged_exhibits as $tagged_exhibit){
        if ($tagged_exhibit['public'] == 0) {
          $tagged_private_exhibits[] = $tagged_exhibit;
        }
      }
      return $tagged_private_exhibits;
    }

    public function get_html_content_using_view_and_exhibits($view, $private_exhibits){
      $content = '<h2> Unpublished Exhibits </h2>';;
      $titles = '';
      $images = '';
      $descriptions = '';
      foreach ($private_exhibits as $private_exhibit) {
          $private_exhibit_image_link = $this->get_first_public_image_address_if_one_exists($private_exhibit);
          $content .= $view->partial('private_exhibit.php', array('exhibit' => $private_exhibit, 'image_link' => $private_exhibit_image_link));
          release_object($private_exhibit);
      }
      return $content;
    }

    public function get_first_public_image_address_if_one_exists($exhibit){
      #"Omeka will use the first attached file as the cover image."
      $exhibit_pages_ids = $this->get_page_ids_for_exhibit($exhibit);
      $exhibit_pages_ids_keys = $this->get_sorted_array_keys($exhibit_pages_ids);
      #foreach page in exhibit (in order)
      foreach ($this->get_sorted_array_keys($exhibit_pages_ids) as $page_num) {
        $search_page_id = $exhibit_pages_ids[$page_num];
        $block_ids = $this->get_block_ids_from_a_page_id($search_page_id);
        #foreach block in page (in order)
        foreach ($this->get_sorted_array_keys($block_ids) as $block_num) {
          $block_id = $block_ids[$block_num];
          $file_ids = $this->get_file_ids_from_a_block_id($block_id);
          #foreach file(attachment) in block (in order)
          foreach ($this->get_sorted_array_keys($file_ids) as $file_num) {
            $file_id = $file_ids[$file_num];
            #if file is an image, return image -> returns first public image in exhibit if one exists
            if ($this->file_type_is_image($file_id) && $this->file_is_public($file_id)){
              $file_address = $this->get_image_link_from_file_id($file_id);
              return $file_address;
            }
          }
        }
      }
      return false;
    }

    public function get_sorted_array_keys($array){
      $keys = array_keys($array);
      sort($keys);
      return $keys;
    }

    public function get_page_ids_for_exhibit($exhibit){
      $db = get_db();
      $exhibit_id = $exhibit['id'];
      $exhibit_pages_table = $db->getTable('ExhibitPage');
      $exhibit_pages = $exhibit_pages_table->fetchObjects("SELECT * FROM omeka_exhibit_pages WHERE exhibit_id = $exhibit_id");
      $exhibit_pages_ids = array();
      foreach ($exhibit_pages as $exhibit_page){
        $exhibit_pages_ids[$exhibit_page['order']] = $exhibit_page['id'];
      }
      return $exhibit_pages_ids;
    }

    public function get_block_ids_from_a_page_id($page_id){
      $db = get_db();
      $exhibit_blocks_table = $db->getTable('ExhibitPageBlock');
      $exhibit_blocks = $exhibit_blocks_table->fetchObjects("SELECT * FROM omeka_exhibit_page_blocks WHERE page_id = $page_id");
      $exhibit_block_ids = array();
      foreach ($exhibit_blocks as $exhibit_block){
        $exhibit_block_ids[$exhibit_block['order']] = $exhibit_block['id'];
      }
      return $exhibit_block_ids;
    }

    public function get_file_ids_from_a_block_id($block_id){
      $db = get_db();
      $exhibit_attachment_table = $db->getTable('ExhibitBlockAttachment');
      $exhibit_attachments = $exhibit_attachment_table->fetchObjects("SELECT * FROM omeka_exhibit_block_attachments WHERE block_id = $block_id");
      $exhibit_attachment_ids = array();
      foreach ($exhibit_attachments as $exhibit_attachment){
        $exhibit_attachment_ids[$exhibit_attachment['order']] = $exhibit_attachment['file_id'];
      }
      return $exhibit_attachment_ids;
    }

    public function file_type_is_image($file_id){
      if($file_id==NULL){
        return False;
      }
      $db = get_db();
      $files_table = $db->getTable('File');
      $file = $files_table->fetchObject("SELECT * FROM omeka_files WHERE id = $file_id");
      $file_mime_type = $file['mime_type'];
      if (strpos($file_mime_type, 'image') !== false) {
        return True;
      } else {
        return False;
      }
    }

    public function file_is_public($file_id){
      if($file_id==NULL){
        return False;
      }
      $db = get_db();
      $files_table = $db->getTable('File');
      $file = $files_table->fetchObject("SELECT * FROM omeka_files WHERE id = $file_id");
      $item_id = $file['item_id'];
      $items_table = $db->getTable('Item');
      $item = $items_table->fetchObject("SELECT * FROM omeka_items WHERE id = $item_id");
      if ($item['public']==1) {
        return True;
      } else {
        return False;
      }
    }

    public function get_image_link_from_file_id($file_id){
      $db = get_db();
      $files_table = $db->getTable('File');
      $file = $files_table->fetchObject("SELECT * FROM omeka_files WHERE id = $file_id");
      $filename = $file['filename'];
      $filename_no_file_extension = substr($filename, 0, strlen($filename)-4);
      $file_address = 'files/square_thumbnails/' . $filename_no_file_extension . '.jpg';
      return $file_address;
    }
}
?>
