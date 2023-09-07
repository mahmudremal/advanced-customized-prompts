<?php
/**
 * This plugin ordered by a client and done by Remal Mahmud (fiverr.com/mahmud_remal). Authority dedicated to that cient.
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced customized prompts
 * Plugin URI:        https://github.com/mahmudremal/
 * Description:       Customized Prompts to act on Search prompts, category prompts, and client idea-gathering prompts.
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Remal Mahmud
 * Author URI:        https://github.com/mahmudremal/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sospopsprompts
 * Domain Path:       /languages
 * 
 * @package SOSPopsProject
 * @author  Remal Mahmud (https://github.com/mahmudremal)
 * @version 1.0.2
 * @link https://github.com/mahmudremal/
 * @category	WooComerce Plugin
 * @copyright	Copyright (c) 2023-25
 * 
 */

/**
 * Bootstrap the plugin.
 */

defined('SOSPOPSPROJECT__FILE__') || define('SOSPOPSPROJECT__FILE__', untrailingslashit(__FILE__));
defined('SOSPOPSPROJECT_DIR_PATH') || define('SOSPOPSPROJECT_DIR_PATH', untrailingslashit(plugin_dir_path(SOSPOPSPROJECT__FILE__)));
defined('SOSPOPSPROJECT_DIR_URI') || define('SOSPOPSPROJECT_DIR_URI', untrailingslashit(plugin_dir_url(SOSPOPSPROJECT__FILE__)));
defined('SOSPOPSPROJECT_BUILD_URI') || define('SOSPOPSPROJECT_BUILD_URI', untrailingslashit(SOSPOPSPROJECT_DIR_URI ) . '/assets/build');
defined('SOSPOPSPROJECT_BUILD_PATH') || define('SOSPOPSPROJECT_BUILD_PATH', untrailingslashit(SOSPOPSPROJECT_DIR_PATH ) . '/assets/build');
defined('SOSPOPSPROJECT_BUILD_JS_URI') || define('SOSPOPSPROJECT_BUILD_JS_URI', untrailingslashit(SOSPOPSPROJECT_DIR_URI ) . '/assets/build/js');
defined('SOSPOPSPROJECT_BUILD_JS_DIR_PATH') || define('SOSPOPSPROJECT_BUILD_JS_DIR_PATH', untrailingslashit(SOSPOPSPROJECT_DIR_PATH ) . '/assets/build/js');
defined('SOSPOPSPROJECT_BUILD_IMG_URI') || define('SOSPOPSPROJECT_BUILD_IMG_URI', untrailingslashit(SOSPOPSPROJECT_DIR_URI ) . '/assets/build/src/img');
defined('SOSPOPSPROJECT_BUILD_CSS_URI') || define('SOSPOPSPROJECT_BUILD_CSS_URI', untrailingslashit(SOSPOPSPROJECT_DIR_URI ) . '/assets/build/css');
defined('SOSPOPSPROJECT_BUILD_CSS_DIR_PATH') || define('SOSPOPSPROJECT_BUILD_CSS_DIR_PATH', untrailingslashit(SOSPOPSPROJECT_DIR_PATH ) . '/assets/build/css');
defined('SOSPOPSPROJECT_BUILD_LIB_URI') || define('SOSPOPSPROJECT_BUILD_LIB_URI', untrailingslashit(SOSPOPSPROJECT_DIR_URI ) . '/assets/build/library');
defined('SOSPOPSPROJECT_ARCHIVE_POST_PER_PAGE') || define('SOSPOPSPROJECT_ARCHIVE_POST_PER_PAGE', 9);
defined('SOSPOPSPROJECT_SEARCH_RESULTS_POST_PER_PAGE') || define('SOSPOPSPROJECT_SEARCH_RESULTS_POST_PER_PAGE', 9);
defined('SOSPOPSPROJECT_OPTIONS') || define('SOSPOPSPROJECT_OPTIONS', (array) get_option('sospopsprompts'));
defined('SOSPOPSPROJECT_UPLOAD_DIR') || define('SOSPOPSPROJECT_UPLOAD_DIR', wp_upload_dir()['basedir'].'/custom_popup/');
defined('SOSPOPSPROJECT_AUDIO_DURATION') || define('SOSPOPSPROJECT_AUDIO_DURATION', 20);

require_once SOSPOPSPROJECT_DIR_PATH . '/inc/helpers/autoloader.php';
// require_once SOSPOPSPROJECT_DIR_PATH . '/inc/helpers/template-tags.php';

if( ! function_exists( 'sospops_get_plugin_instance' ) ) {
	function sospops_get_plugin_instance() {\SOSPOPSPROJECT\inc\Project::get_instance();}
	sospops_get_plugin_instance();
}




