<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @wordpress-plugin
 * Plugin Name:       SatoshiPay
 * Plugin URI:        https://wordpress.org/plugins/satoshipay/
 * Description:       Integrates SatoshiPay into WordPress. Quick start: 1) Select SatoshiPay from the left-hand admin menu, 2) add SatoshiPay API credentials, 3) edit a post, 4) activate "Paid Post" in SatoshiPay meta box, 5) set a price, 6) publish and view post. The SatoshiPay widget will appear and allow readers to pay for the post.
 * Version:           0.8
 * Author:            SatoshiPay
 * Author URI:        https://satoshipay.io
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       satoshipay
 * Domain Path:       /languages
 */

namespace SatoshiPay;

// Abort if this file is called directly.
if (!defined('WPINC')) {
    die("This file can not be executed as a stand-alone script.\n");
}

// Plugin version, used in user-agent string for API calls; keep in sync with
// version in plugin description above!
define('SATOSHIPAY_VERSION', '0.8');

// Plugin root file
define('SATOSHIPAY_PLUGIN_ROOT_FILE', plugin_basename(__FILE__));

// Load configuration, silently ignore missing config.php
@include_once __DIR__ . '/config.php';

// Read environment variables, will override config
if (getenv('SATOSHIPAY_API_URL')) {
    define('SATOSHIPAY_API_URL', getenv('SATOSHIPAY_API_URL'));
}
if (getenv('SATOSHIPAY_CLIENT_URL')) {
    define('SATOSHIPAY_CLIENT_URL', getenv('SATOSHIPAY_CLIENT_URL'));
}
if (getenv('SATOSHIPAY_USE_AD_BLOCKER_DETECTION')) {
    define('SATOSHIPAY_USE_AD_BLOCKER_DETECTION', getenv('SATOSHIPAY_USE_AD_BLOCKER_DETECTION') === 'true' ? true : false);
}

// Use defaults if no environment or config variables were set
if (!defined('SATOSHIPAY_STYLE_ADMIN')) {
    define('SATOSHIPAY_STYLE_ADMIN', plugins_url('assets/css/style_admin.css', __FILE__));
}
if (!defined('SATOSHIPAY_SCRIPT_ADMIN')) {
    define('SATOSHIPAY_SCRIPT_ADMIN', plugins_url('assets/js/script_admin.js', __FILE__));
}
if (!defined('SATOSHIPAY_SCRIPT_POST')) {
    define('SATOSHIPAY_SCRIPT_POST', plugins_url('assets/js/script_post.js', __FILE__));
}
if (!defined('SATOSHIPAY_API_URL')) {
    define('SATOSHIPAY_API_URL', 'https://api.satoshipay.io/v1');
}
if (!defined('SATOSHIPAY_CLIENT_URL')) {
    define('SATOSHIPAY_CLIENT_URL', 'https://wallet.satoshipay.io/satoshipay.js');
}
if (!defined('SATOSHIPAY_USE_AD_BLOCKER_DETECTION')) {
    define('SATOSHIPAY_USE_AD_BLOCKER_DETECTION', true);
}

require_once __DIR__ . '/src/SatoshiPay/SatoshiPayPlugin.php';
require_once __DIR__ . '/src/SatoshiPay/SatoshiPayAdminPlugin.php';

use SatoshiPay\SatoshiPayPlugin;
use SatoshiPay\SatoshiPayAdminPlugin;

if (is_admin()) {
    add_action('plugins_loaded', array(SatoshiPayAdminPlugin::getInstance(__FILE__), 'init'));
} else {
    add_action('plugins_loaded', array(SatoshiPayPlugin::getInstance(__FILE__), 'init'));
}
