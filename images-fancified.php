<?php

/**
 * Images Fancified
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author Julius Juurmaa
 * @version 0.2
 * @license http://www.gnu.org/licenses/gpl.html
 */

/*
Plugin Name: Images Fancified
Plugin URI: http://wordpress.org/extend/plugins/images-fancified/
Description: Simplifies the way WordPress handles images and galleries.
Version: 0.2
Author: Julius Juurmaa
*/

define(FANCIFIED_URL, WP_PLUGIN_URL .'/images-fancified');

add_filter('post_gallery', 'fancified_gallery_shortcode', 10, 2);
add_filter('attachment_fields_to_edit', 'fancified_attachment_fields', 20, 2);
add_filter('image_send_to_editor', 'fancified_image_link', -10);

add_action('parse_query', 'fancified_get_attachment_page');
add_action('wp_head', 'fancified_head');

if(!is_admin()) {
  wp_enqueue_style(
    'images-fancified',
    FANCIFIED_URL .'/images-fancified.css',
    false, false, 'all'
  );
  wp_enqueue_style(
    'fancybox',
    FANCIFIED_URL .'/fancybox/jquery.fancybox-1.2.6.css',
    false, false, 'screen'
  );
  wp_enqueue_script(
    'fancybox',
    FANCIFIED_URL .'/fancybox/jquery.fancybox-1.2.6.pack.js',
    array('jquery')
  );
}

/**
 * Overloads the default implementation for gallery shortcode to provide
 * clearer syntax, also ensures Fancybox knows about our gallery images.
 *
 * @param string XHTML source thus far, blank by default
 * @param array Gallery shortcode attributes
 *
 * @return string XHTML source for the gallery
 */
function fancified_gallery_shortcode($output, $attributes) {
  global $post;

  static $instance = 0;
  $instance++;

  // Sanitize the orderby attribute
  if(isset($attributes['orderby'])) {
    $attributes['orderby'] = sanitize_sql_orderby($attributes['orderby']);
    if(empty($attributes['orderby'])) {
      unset($attributes['orderby']);
    }
  }

  // Extract attributes
  extract(shortcode_atts(array(
    'order' => 'ASC',
    'orderby' => 'menu_order ID',
    'id' => $post->ID,
    'itemtag' => 'dl',
    'icontag' => 'dt',
    'captiontag' => 'dd',
    'columns' => 3,
    'size' => 'thumbnail',
    'include' => '',
    'exclude' => '',
    'numberposts' => -1,
  ), $attributes));

  // Fetch attachments
  $id = intval($id);
  $attachments = get_children(array(
    'post_parent' => $id,
    'post_status' => 'inherit',
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'order' => $order,
    'orderby' => $orderby,
    'include' => $include,
    'exclude' => $exclude,
    'numberposts' => $numberposts
  ));

  $output = "\n";
  if(!empty($attachments)) {
    if(is_feed()) {

      // Feeds are handled just like in the default implementation
      foreach($attachments as $att_id => $attachment) {
        $output .= "\n". wp_get_attachment_link($attachment_id, $size, true);
      }
    } else {

      // Initialize
      $itemtag = tag_escape($itemtag);
      $captiontag = tag_escape($captiontag);
      $columns = intval($columns);
      $itemwidth = $columns > 0 ? floor(100 / $columns) : 100;
      $current = 0;

      // Let us identify this very set of images
      $selector = 'gallery-'. $instance;
      $output .= "\n<div id=\"{$selector}\" class=\"gallery gallery-in-{$id}\">";

      foreach($attachments as $att_id => $attachment) {

        // Determine caption and title
        $caption = wp_specialchars($attachment->post_excerpt, 1);
        $title = wp_specialchars($attachment->post_title, 1);
        if(empty($caption)) {
          $caption = $title;
        }

        // Determine image addresses
        $thumbnail = wp_get_attachment_image_src($att_id, $size);
        $large = wp_get_attachment_image_src($att_id, 'large');

        // If required, open gallery row
        if($columns > 0 && $current % $columns == 0) {
          $output .= "\n  <div class=\"gallery-row\">\n";
        }

        // Open wrapper, output image and caption, then close wrapper
        $output .= <<<XHTML

    <{$itemtag} class="gallery-item col-{$columns}">
      <{$icontag} class="gallery-icon">
        <a class="image-link" href="{$large[0]}" title="{$title}" rel="{$selector}">
          <img src="{$thumbnail[0]}" alt="{$title}" title="{$title}" />
        </a>
      </{$icontag}>
      <{$captiontag} class="gallery-caption">{$caption}</{$captiontag}>
    </{$itemtag}>

XHTML;

        // If required, close gallery row
        if($columns > 0 && ++$current % $columns == 0) {
          $output .= "\n  </div>";
        }
      }

      // If a row was left unclosed, close it. Then close everything
      if($columns > 0 && $current % $columns !== 0) {
        $output .= "\n  </div>";
      }
      $output .= "\n</div>";
    }

    $output .= "\n";
  }

  $output .= "\n";
  return $output;
}

/**
 * If WordPress is about to display an attachment page, forces a 404.
 *
 * @param WP_Query Query object
 *
 * @return void
 */
function fancified_get_attachment_page(&$query) {
  if($query->is_attachment || $query->queried_object->post_type == 'attachment') {
    $query->is_404 = true;
  }
}

/**
 * Initializes FancyBox in page header.
 *
 * @return void
 */
function fancified_head() {
  echo <<<XHTML

<script type="text/javascript">
  jQuery(document).ready(function() {
    jQuery("a.image-link").fancybox({
      'hideOnContentClick': true
    });
  })
</script>


XHTML;
}

/**
 * Replaces the default implementation for link target selection.
 *
 * @param object Attachment data
 * @param string Selected link target
 *
 * @return string XHTML source for the media manager
 */
function fancified_attachment_link_select($post, $type = '') {

  // If this is an image, replace the value for file URL
  if(substr($post->post_mime_type, 0, 5) == 'image') {
    $file = wp_get_attachment_image_src($post->ID, 'large');
    $file = $file[0];
    $label = __('Link to image');

  // Otherwise change nothing
  } else {
    $file = wp_get_attachment_url($post->ID);
    $label = __('File URL');
  }

  if(empty($type)) {
    $type = get_user_setting('urlbutton', 'file');
  }

  $none = __('None');
  $file = esc_attr($file);
  $url = empty($type) ? '' : $file;

  return <<<XHTML

<input type="text" class="urlfield" name="attachments[{$post->ID}][url]" value="{$url}" /><br />
<button type="button" class="button urlnone" title="">{$none}</button>
<button type="button" class="button urlfile" title="{$file}">{$label}</button>


XHTML;
}

/**
 * Alters the default implementation for attachment editing fields.
 *
 * @param array Form fields
 * @param object Attachment data
 *
 * @return string XHTML source for the media manager
 */
function fancified_attachment_fields($fields, $post) {
  $fields['url']['html'] = fancified_attachment_link_select(
    $post, get_option('image_default_link_type')
  );
  return $fields;
}

/**
 * Marks linked images so FancyBox will know about them. Links do not get
 * classes by default, therefore, if this gets executed early enough,
 * we can get away without clever regular expressions.
 *
 * @param string XHTML source
 *
 * @return string XHTML source
 */
function fancified_image_link($source) {
  return str_replace('<a', '<a class="image-link"', $source);
}
