<?php

namespace BitForm\Common;

class Shortcode
{

    public static function shortcode($atts)
    {
        $id = $atts['id'];
        $shortcode = '';
        if ($id) {
            $width = $atts['width'] . (is_numeric($atts['width']) ? 'px' : '');
            $widthStyle = $width ? "width:${width};" : 'width:100%;';
            $height = $atts['height'] . (is_numeric($atts['height']) ? 'px' : '');
            $heightStyle = $height ? "height:${height};" : '';
            $shortcode = '<iframe class="bf-sc-iframe" style="border:0;' . $widthStyle . $heightStyle . '" src="' . self::getFormUrl($id) . '"></iframe>';
        }
        return $shortcode;
    }

    public static function getFormUrl($id)
    {
        return home_url("/bitform/f/$id", 'relative');
    }
}
