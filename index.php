<?php
/*
  Plugin Name: BitForm
  Description: BitForm
  Version: 1.1.0
  Author: BitForm
  Author URI: https://bitform.bitorre.net
  Plugin URI: https://bitform.bitorre.net/wordpress
  License: GPLv2 or later
  Text Domain: bitform
  Domain Path: /languages

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BitForm;

use BitForm\Common\Permission;
use BitForm\Utils\StringUtils;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
define('BITFORM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BITFORM_VERSION', '1.1.0');

spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $baseDir = __DIR__;
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
add_action('plugins_loaded', array('BitForm\\Context', 'init'));
register_activation_hook(__FILE__, array('BitForm\\Context', 'install'));
register_uninstall_hook(__FILE__, array('BitForm\\Context', 'uninstall'));
add_shortcode('bitform', array('BitForm\\Common\\Shortcode', 'shortcode'));
add_action('widgets_init', array('BitForm\\Common\\Widget', 'register'));
add_filter('rest_pre_serve_request', array('BitForm\\Common\\JsonResponse', 'serve'), 10, 4);


function bitform_init_languages()
{
    load_plugin_textdomain('bitform', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'BitForm\\bitform_init_languages');


function bitform_customize_menu()
{
    add_menu_page(
        'BitForm',
        'BitForm',
        'edit_theme_options',
        'bitform',
        'BitForm\\bitform_site_setting_page',
        plugins_url('bitform/assets/img/icon.png'),
        '50'
    );
}
add_action('admin_menu', 'BitForm\\bitform_customize_menu');
function bitform_site_setting_page()
{
    $url = home_url('', 'relative') . '/bitform/';
?>
    <style>
        #wpcontent, #wpbody, #wpbody-content {
            padding: 0 !important;
        }
        #wpfooter {
            display: none;
        }
        #bf-iframe {
            position: absolute;
            left: 0;
            top: -32px;
            padding-top: 32px;
            width: 100%;
            height: 100vh;
            box-sizing: border-box;
        }
        @media screen and (max-width: 480px) {
            #bf-iframe {
                top: 0;
                padding: 0;
                z-index: 100000;
            }
        }
    </style>
    <iframe id="bf-iframe" src="<?php echo $url ?>"></iframe>
<?php
}


add_action('template_redirect', 'BitForm\\bitform_index');
function bitform_index()
{
    $requestUri = $_SERVER["REQUEST_URI"];
    $siteUrl = home_url('/bitform/', 'relative');
    if (!StringUtils::startsWith($requestUri, $siteUrl)) {
        return false;
    }
    if (Permission::hasAllCapabilities()) {
        return Context::$publicController->view();
    }
    $anonymousUrl = $siteUrl . 'f/';
    if (StringUtils::startsWith($requestUri, $anonymousUrl)) {
        $formId = substr($requestUri, strlen($anonymousUrl));
        if (ctype_digit($formId)) {
            $formId = intval($formId);
            return Context::$publicController->anonymousForm($formId);
        }
    }
    return false;
}
