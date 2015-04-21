<?php
/**
 * Plugin Name: Peanut Butter Bar (smooth version)
 * Description: All the good stuff that sticks to the top of your site. 
 * Version: 1.2.1
 * Author: Andrew Couch
 * Author URI: http://andrew-couch.com
 * Plugin URI: http://peanutbutterplugin.com
 * Text Domain: pbb-textdomain
 * Copyright 2014  Andrew Couch  (email : info@andrew-couch.com)
*/
if ( ! defined( 'ABSPATH' ) ) exit;

require_once('pbb-admin.php');
require_once('pbb-bar-builder.php');

if ( !class_exists( 'PBB_Smooth' ))
{
	class PBB_Smooth
	{
		const DB_VER = 1;
		public $settingprefix = 'pbb_settings';
		public $settingpage = 'pbbsmooth';

		function __construct()
		{
			add_action( 'wp_enqueue_scripts', array($this, 'pbb_register_resources') );
			add_action( 'wp_footer', array($this,'pbb_show_bar') );
			add_action( 'pbb_showbar', array($this,'pbb_show_bar') );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this,'plugin_action_links' ));
			add_action( 'plugins_loaded', array($this, 'load_textdomain') );
			add_action( 'plugins_loaded', array($this, 'maybe_update' ), 1 );
			register_deactivation_hook(__FILE__, array($this,'pbb_smooth_plugin_deactivate'));
		}
		/**
		* Removes the action links on deactivate
		*/
		function pbb_smooth_plugin_deactivate(){
			//remove_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this,'plugin_action_links' ));
			global $wp_roles;
			$wp_roles->remove_cap('editor','manage_pbb');
			$wp_roles->remove_cap('administrator','manage_pbb');
			$wp_roles->remove_cap('super admin','manage_pbb');			
		}		
		/**
		 * Loads language files
		 */			
		function load_textdomain(){
			load_plugin_textdomain( 'pbb-textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' ); 
		}
		/**
		 * Add "settings" link to plugin page
		 */	
		function plugin_action_links( $links ) {
			if ( is_plugin_active( plugin_basename(__FILE__) ) ) {
				$settings_link = '<a href="'. get_admin_url(null, 'options-general.php?page='.$this->settingpage) .'">' . __('Settings') . '</a>';
				array_unshift( $links, $settings_link );
			}
			return $links;			
		}
		/**
		 * Register and queue scripts and styles for the bar
		 */
		function pbb_register_resources() {
			wp_register_style( 'pbb-style', plugins_url( '/pbb-style.min.css' , __FILE__  ) );
			wp_register_script( 'pbb-script', plugins_url( '/pbb-script.min.js' , __FILE__  ) ,array('jquery'),false,true );
			wp_enqueue_style( 'pbb-style' );
			wp_enqueue_script( 'pbb-script' );
		}
		/**
		 * Determines which bar to show and calls the show routine for it
		 * 
		 * @param boolean $return_value : Whether bar should be returned, otherwise echoed
		 */
		function pbb_show_bar($return_value = false) {

			$smooth_settings = get_option($this->settingprefix.'_smooth');
			$barhtml ='';
			if (isset($smooth_settings['barhtml']))
			{
				$barhtml = apply_filters('pbb_filter_bar_output', $smooth_settings['barhtml'], 0);
				$barhtml = do_shortcode($barhtml);
			}
			if ($return_value){
				return $barhtml;
			}else{
				echo $barhtml;
			}

		}
		/**
		 * Determines whether updates are required based on DB version
		 */
		function maybe_update() {
	        // bail if this plugin data doesn't need updating
	        if ( get_option( $this->settingprefix.'_smoothdbver', 0 ) >= self::DB_VER ) {
	            return;
	        }
	 
	        require_once( 'pbb-update.php' );
	        pbb_smooth_update( $this->settingprefix.'_smoothdbver' , self::DB_VER);
	    }

	}
}
$pbbs_class = new PBB_Smooth();
if (class_exists( 'PBBS_Admin' ))
{
	$pbbs_admin = new PBBS_Admin($pbbs_class->settingpage, $pbbs_class->settingprefix);
}
register_activation_hook(__FILE__, 'pbb_smooth_plugin_activate');
/**
 * Do activation tasks
 */
function pbb_smooth_plugin_activate(){
	if ( is_plugin_active( 'peanutbutterbar/peanutbutterbar.php' ) ) {
	  	//"smooth version" plugin is activated
		deactivate_plugins( __FILE__ );
		pbb_smooth_trigger_error('The crunchy version of Peanut Butter Bar is active. You already have access to all of the paid features and do not need to have smooth installed as well..', E_USER_ERROR);
	} else{
		global $wp_roles;
		$wp_roles->add_cap('editor','manage_pbb');
		$wp_roles->add_cap('administrator','manage_pbb');
		$wp_roles->add_cap('super admin','manage_pbb');
	}
}
/**
 * Custom error for activation
 */
function pbb_smooth_trigger_error($message, $errno) {
    if(isset($_GET['action']) && $_GET['action'] == 'error_scrape') {
        echo '<strong>' . $message . '</strong>';
        exit;
    } else {
        trigger_error($message, $errno);
    }
}
