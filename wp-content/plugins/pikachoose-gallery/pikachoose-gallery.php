<?php
/*
Plugin Name: Pikachoose Gallery
Plugin URI: https://github.com/hadriagh/pikachoose-gallery
Description: Allows you to display your WordPress galleries using the PikaChoose jQuery slideshow
Version: 0.1
Author: David Moore
Author URI: http://github.com/hadriagh/
*/

define('PIKACHOOSE_GALLERY_VERSION', '0.1');
define('PIKACHOOSE_GALLERY_URL', WP_PLUGIN_URL . '/pikachoose-gallery');

wp_enqueue_style('pikachoose-whiteout', PIKACHOOSE_GALLERY_URL.'/css/pikachoose-whiteout-theme.css', array(), PIKACHOOSE_GALLERY_VERSION);
wp_enqueue_script('jquery');
wp_enqueue_script('jcarousel', PIKACHOOSE_GALLERY_URL.'/js/jquery.jcarousel.min.js', array(), '', true);
wp_enqueue_script('pikachoose', PIKACHOOSE_GALLERY_URL.'/js/jquery.pikachoose.min.js', array(), '4.5.0', true);
wp_enqueue_script('pikachoose-gallery', PIKACHOOSE_GALLERY_URL.'/js/pikachoose-gallery.js', array(), PIKACHOOSE_GALLERY_VERSION, true);

remove_shortcode('pikachoose-gallery');
add_shortcode('pikachoose-gallery', 'parseGalleryShortcode');

function parseGalleryShortcode($specifiedAttributes)
{
    $post = get_post();

    if (!empty($specifiedAttributes['ids'])) {
        // 'ids' is explicitly ordered, unless you specify otherwise.
        if (empty($specifiedAttributes['orderby'])) {
            $specifiedAttributes['orderby'] = 'post__in';
        }
        
        $specifiedAttributes['include'] = $specifiedAttributes['ids'];
    }

    // These are strings because they are used as HTML5 data objects later on!
    if($specifiedAttributes['carousel'] == 'false') {
        $carousel = 'false';
    } else {
        $carousel = 'true';
    }

    if($specifiedAttributes['slideshow'] == 'false') {
        $slideshow = false;
    } else {
        $slideshow = true;
    }

    // We're trusting author input, so let's at least make sure it looks like a valid orderby statement
    if (isset($specifiedAttributes['orderby'])) {
        $specifiedAttributes['orderby'] = sanitize_sql_orderby($specifiedAttributes['orderby']);
        
        if (!$specifiedAttributes['orderby']) {
            unset($specifiedAttributes['orderby']);
        }
    }
    
    $defaultAttributes = array('order'      => 'ASC',
                               'orderby'    => 'menu_order ID',
                               'id'         => $post->ID,
                               'columns'    => 3,
                               'size'       => 'large',
                               'include'    => '',
                               'exclude'    => '');

    extract(shortcode_atts($defaultAttributes, $specifiedAttributes, 'gallery'));

    $id = intval($id);
    if ( 'RAND' == $order )
        $orderby = 'none';

    if ( !empty($include) ) {
        $_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

        $attachments = array();
        foreach ( $_attachments as $key => $val ) {
            $attachments[$val->ID] = $_attachments[$key];
        }
    } elseif ( !empty($exclude) ) {
        $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
    } else {
        $attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
    }

    if ( empty($attachments) )
        return '';

    if ( is_feed() ) {
        $output = "\n";
        foreach ( $attachments as $att_id => $attachment )
            $output .= wp_get_attachment_link($att_id, $size, true) . "\n";
        return $output;
    }


    if($slideshow) {
        $output = "<ul class='pikachoose-gallery clear' data-carousel='" . $carousel . "'>";
        foreach($attachments as $attachment) {
            $output .= '<li><img src="' . $attachment->guid . '" class="img-responsive" />';

            if(trim($attachment->post_excerpt)) {
                $output .= "<span>" . wptexturize($attachment->post_excerpt) . "</span>";
            }

            $output .= "</li>";
        }

        $output .= "</ul>";
    } else {
        $output = "<div class='gallery clearfix'>";
        foreach($attachments as $id => $attachment) {
            $output .= '<div class="thumbnail">';
            $output .= wp_get_attachment_link($id, array(100,65), true);

            if(trim($attachment->post_excerpt)) {
                $output .= "<p class='caption'>" . wptexturize($attachment->post_excerpt) . "</p>";
            }

            $output .= "</div>";
        }

        $output .= "</div>";
    }


    return $output;
}
