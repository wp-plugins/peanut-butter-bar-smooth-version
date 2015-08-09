<?php
/**
 * Class for managing admin screens
 *
 * @version 0.1
 *
 * @author Andrew Couch
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'pbb-settings-screenbuilder.php';
	require_once plugin_dir_path( __FILE__ ) . 'pbb-bar-helper.php';
}

if ( !class_exists( 'PBBS_Admin' ) &&  is_admin() ){

	class PBBS_Admin  {

		private $pagename;
		private $settingprefix;
		private $all_bars;
		private $general_settings;
		private $page_hook;
		private $info_page_link;

		function __construct($pagename, $settingprefix) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			$this->pagename = $pagename;
			$this->settingprefix = $settingprefix;
			if ( !class_exists( 'PBBS_Settings_ScreenBuilder' ) )
				wp_die('Problem');
			$this->info_page_link = admin_url("options-general.php?page=$this->pagename&tab=info");
		}

		/**
		 * Add menu to admin screen
		 */
		function admin_menu() {
			if ( is_plugin_active( plugin_basename(plugin_dir_path( __FILE__ ).'pbb-smooth.php') ) ) {
				// Adds the plugin admin menu page (in wp-admin > Settings)
				// sets the page hook for this settings page.
		        $this->page_hook = add_options_page(
		            __('Peanut Butter Bar Admin','pbb-textdomain'),
		            __('Peanut Butter Bar Settings','pbb-textdomain'),
		            'manage_pbb',
		            $this->pagename,
		            array( $this, 'admin_page' )
		        );
		    }
		}
		/**
		 * Loads admin screen
		 */
		function load_admin_page(){
			wp_register_style( 'pbb-style', plugins_url( '/pbb-style.min.css' , __FILE__  ) );
			wp_register_script( 'pbb-admin-script', plugins_url( '/pbb-admin.min.js' , __FILE__  ),array('jquery'),false, true );
			add_action( 'admin_enqueue_scripts', array($this,'enqueue_admin_js') );
		}
		/**
		 * Queue admin script and style
		 */		
		function enqueue_admin_js(){
			wp_enqueue_script( 'pbb-admin-script' );
			wp_enqueue_style( 'pbb-style' );
		}
		/**
		 * Adds actions for admin 
		 */		
		function admin_init(){
			add_action( 'load-' . $this->page_hook, array($this,'load_admin_page') );
	        add_action('admin_action_pbb-savebar',array($this,'save_bar'));
		}

		/**
		 * Saves a posted bar.
		 */		
		function save_bar(){			
			$this->check_nonce('savebar' );

			if ( isset( $_REQUEST['pbb_bar'] ) ){

				$bar = PBBS_Bar_Helper::array_to_bar($_REQUEST['pbb_bar']);

				$bar['behavior']['show_branding'] = isset( $_REQUEST['pbb_bar']['show_branding'] ) ? 'on' : false ;
				$bar['behavior']['show_bar'] = isset( $_REQUEST['pbb_bar']['show_bar'] ) ? 'on' : false ;
				$theme = $bar['theme'];
				$link_module = $bar['link'];
				$tracking  = $bar['tracking'];
				$behavior = $bar['behavior'];

				if ($this->validate_bar($link_module, $theme, $behavior))
				{			
					$builder = new PBBS_Bar_Builder($this->settingprefix);
					$barhtml = $builder->make_bar_as_excerpt($theme, $link_module, $tracking, $behavior);
					$bar_settings = array('barhtml'=>$barhtml, 'theme'=>$theme, 'link'=>$link_module, 'tracking'=>$tracking, 'behavior'=>$behavior);
					update_option($this->settingprefix.'_smooth', $bar_settings);

				}

	        	if ( !count( get_settings_errors() ) )
	        	{
					add_settings_error('general', 'settings_updated', __('Bar saved.','pbb-textdomain'), 'updated');
					if (function_exists('w3tc_pgcache_flush')) {
						w3tc_pgcache_flush();
					} else if (function_exists('wp_cache_clear_cache')) {
						wp_cache_clear_cache();
					}				
				}else{
					set_transient('pbb-error-values',$bar,30);
				}
				set_transient('settings_errors', get_settings_errors(), 30);
			}

			$goback = wp_get_referer();
			$goback = esc_url_raw(add_query_arg( 'settings-updated', 'true',  $goback ));
			wp_safe_redirect( $goback );			 
		}
		/**
		 * Validates whether a bar is complete
		 * 
		 * @param string $post_title
		 * @param array $link_module
		 * @param array $theme
		 * @param array $behavior
		 * @param array $showerror
		 */		
		function validate_bar($link_module, $theme, $behavior, $showerrors=true){
			$flag = true;
			if($link_module['use_linkmodule']=="on"){
				if (($link_module['linkurl']=='' || $link_module['linktext']=='') 
					&& $link_module['linkurl'] != $link_module['linktext']){
					if ($showerrors){
						$error_field = 'linktext';
						if ($link_module['linkurl']==''){
							$error_field = 'linkurl';
						}
						add_settings_error($error_field,'empty',__('Link URL and Link Text must either both be filled or both left empty.','pbb-textdomain'),'error');
					}
					$flag = false;					
				}
			}
			if ($behavior['do_bounce']=='on'){
				if (!is_numeric($behavior['delay_bounce_secs'])){
					if ($showerrors)
						add_settings_error('delay_bounce_secs','invalid',__('Bounce delay must be a number.','pbb-textdomain'),'error');
					$flag = false;						
				}
			}
			return $flag;
		}

		/**
		 * Checks Nonce field. Not using pure SettingsAPI, so need to do it "manually"
		 */
		function check_nonce($action){
			$option_group=$this->page_hook;
			check_admin_referer( "$option_group-$action-options" );
		}
		/**
		 * Display Admin Screen
		 */
		function admin_page() {
			//Get all bar names from the general settings

			echo '<div class="wrap">';

			echo $this->render_header( __('Peanut Butter Bar Settings','pbb-textdomain') );
			
			$this->render_form();
			echo '</div>';
		}		
		/**
		 * Renders the header and navigation part of the admin screen
		 * 
		 * @param string $headertext
		 * @return string 
		 */		
		function render_header($headertext){
			$output = '<h2>'.$headertext.'</h2>';
			$tab = '';
			$tabhighlighttext = ' nav-tab-active';
			if (isset($_GET['tab']))
			{
				$tab = $_GET['tab'];
			}

			//Output header
			$output .= '<h3 class="nav-tab-wrapper">';
			$output .=  '<a href="'.admin_url("options-general.php?page=$this->pagename").'" class="nav-tab'. ($tab=='' ? $tabhighlighttext : '').'">';
			$output .= __('Manage bar','pbb-textdomain');
			$output .=  '</a>';		
			$output .=  '<a href="'.$this->info_page_link.'" class="nav-tab'. ($tab=='info' ? $tabhighlighttext : '').'">';
			$output .= __('Info &amp; Help','pbb-textdomain');
			$output .=  '</a>';
			
			$output .=  '</h3>';
			return $output;
		}
		/**
		 * Renders proper form based on current location
		 */		
		function render_form(){
			$end_form = true;
			if (isset($_GET['tab']) && $_GET['tab'] == 'info')
			{					
				$this->build_info_screen();
				$end_form = false;
			}
			else
			{
				$bar = $this->get_current_bar();
				$this->build_bar_screen($bar);
			}
			if ($end_form){
				echo '</form>';
			}
		}
		/**
		 * Info screen
		 */
		function build_info_screen(){
			echo '<h2>'.__("About Peanut Butter Bar",'pbb-textdomain').'</h2>';
			echo '<div class="postbox-container" style="width:60%;margin: 10px 10px 0 0;">';
			echo '<div class="postbox">';
			echo '<h3 style="padding:12px; cursor:auto;">'.__("All the good stuff that sticks to the roof of your site.",'pbb-textdomain').'</h3>';
			echo '<div class="inside">';
			echo '<img src="'. plugins_url( 'pbb-icon.png' , __FILE__ ) .'" style="margin:0 10px 0 0; width:100px;height:100px;float:left;"/>';
			echo '<p>The Peanut Butter Bar is a highly customizable sticky bar for the top of your site. It is a low profile, but highly visible way to market to your visitors. ';
			echo 'Customize your message to the exact visitors you are trying to reach.';
			echo '</p><p>';
			echo '<b>Need Help?</b> Our contact info is in the box to the right. Check out the FAQ on the website, tweet at us or drop us an email with your concern.';
			echo '</p>';
			echo '</div></div>';			
			echo '<div class="postbox">';
			echo '<h3 style="padding:12px; cursor:auto;">'.__("Help For Configuration Screens",'pbb-textdomain').'</h3>';
			echo '<div class="inside">';
			echo '<h4 id="events">'.__('Tracking events').'</h4>';
			echo '<p>'.__("PBB tracks events to your Google Analytics account directly. We piggyback on the code you probably already use. Read this <a href='http://peanutbutterplugin.com/help-topics/google-events-with-peanut-butter-bar/' target='_blank'>article</a> for more about the power of Google Tracking Events and how to use them in Peanut Butter Bar. Google Analytics always tracks the page URL along with the event. Each of these are visible in the Google Analytics account screens.",'pbb-textdomain').'</p>';
			echo '<ul style="list-style: disc outside none;padding: 0 0 0 20px;">';
			echo '<li>'.__("<b>Event Category</b> -  The category tells you what type of link you are tracking. This could be as generic as 'PBB' to show that a link came from the bar, or as specific as which type of click you are tracking, like 'book' or 'mail signup'.",'pbb-textdomain').'</p>';
			echo '<li>'.__("<b>Event Action</b> - What action a user performed. 'Click' makes the most sense here,though if you already have a scheme for your sie, you can change it.",'pbb-textdomain').'</p>';
			echo '<li>'.__("<b>Event Label</b> - (optional) More info for which link was clicked. Can be used to differeniate between bars.",'pbb-textdomain').'</p>';
			echo '</ul>';
			echo '<p>'.__("Currently the Peanut Butter Bar tracks only with Google Analytics. Check out this <a href='http://peanutbutterplugin.com/help-topics/google-events-with-peanut-butter-bar/' target='_blank'>article</a> for additional tracking support.",'pbb-textdomain').'</p>';
			echo '</div></div></div>';
			echo '<div class="postbox-container" style="margin: 10px 0 0 0;width:255px">';
			echo '<div class="postbox">';
			echo '<h3 style="padding:12px; cursor:auto;">'.__("Did you like this plugin?",'pbb-textdomain').'</h3>';
			echo '<div class="inside">';
			echo 'If you liked this plugin and want to support the author...';
			echo '<ul>';
			echo '<li class="pbbcheck">Check "Works" on <a href="http://wordpress.org/plugins/peanut-butter-bar-smooth-version/" target="_blank">WordPress.org</a></li>';
			echo '<li class="pbbcheck">Rate it on <a href="http://wordpress.org/plugins/peanut-butter-bar-smooth-version/" target="_blank">WordPress.org</a></li>';
			echo '<li class="pbbcheck">Check out the (paid) <a href="http://peanutbutterplugin.com/pbb/features/#crunchy?utm_source=adminlink-buy&utm_medium=bar" target="_blank">Chunky version</a> of Peanut Butter Bar with more features.</li>';
			echo '<li class="pbbcheck">Tweet us (<a href="http://twitter.com/pbplugin" target="_blank">@pbplugin</a>) and tell us what you liked.</li>';
			echo '</ul>';
			echo '</div></div>';
			echo '<div class="postbox">';
			echo '<h3 style="padding:12px; cursor:auto;">'.__("Contact Us",'pbb-textdomain').'</h3>';
			echo '<div class="inside">';
			echo '<ul>';
			echo '<li>Visit the <a target="_blank" href="http://peanutbutterplugin.com/?utm_source=adminlink-visit&utm_medium=bar">plugin page</a></li>';
			echo '<li>Send a tweet to <a target="_blank" href="https://twitter.com/pbplugins">@pbplugins</a></li>';
			echo '<li>Send an <a target="_blank" href="mailto:info@peanutbutterplugin.com">email</a></li>';
			echo '</ul>';
			echo '</div></div></div>';	
		}
		/**
		 * Bar edit/add screen
		 */		
		function build_bar_screen($bar_data){
			$this->settings_fields( 'savebar');
			$tracking_desc = __('Activate tracking and configure the event labels.','pbb-textdomain'). " <a target='_blank' href='$this->info_page_link#events'>".__('More info','pbb-textdomain').'</a>';
			
			$fields = array(				
					array(
						'id'	=> 'preview',
						'type'	=> 'extra',
						'label'	=> __('Color Preview','pbb-textdomain'),
						'content'=>'<div class="pbb-admin"><div id="pbb"><div id="pbb-bar"><span class="pbb-text pbb-module">' . __('This is a <a href="#">link</a> preview.','pbb-textdomain').'</span></div><div id="pbb-closer"></div></div></div>',
						),
					array(
						'id'    => 'theme', 
						'type'  => 'select', 
						'label' => __('Choose a color scheme','pbb-textdomain'),
						'default' => 'pb', 
						'options' => array( 
								'pb' => __('Peanut Butter','pbb-textdomain'), 
								'li' => __('Licorice','pbb-textdomain'),
								'lm' => __('Lemon','pbb-textdomain'),
								'gj' => __('Grape Jelly','pbb-textdomain'),
								'rs' => __('Raspberry','pbb-textdomain'),
								'bb' => __('Blueberry','pbb-textdomain'),
								'ch' => __('Cherry','pbb-textdomain'),
								'le' => __('Lime','pbb-textdomain'),
								'co' => __('Coconut','pbb-textdomain'),
							),
					),					
					array(
						'id' => 'link_creation',
						'type' => 'row',
						'label' => __( 'Define text link','pbb-textdomain' ),

						'fields' => array(
							array(
							'id' => 'pretitle', // required
							'type' => 'text', // required
							'label' => 'text before the link', // space as seperator
							'desc' => __(' &lt;b&gt; and &lt;i&gt; tags allowed.','pbb-textdomain'),
							),
							array(
							'id' => 'linktext', // required
							'type' => 'text', // required
							'desc' => __('clickable text','pbb-textdomain'),
							'label' => __('link text','pbb-textdomain'),
							),
							array(
							'id' => 'posttitle', // required
							'type' => 'text', // required
							'desc' => __(' &lt;b&gt; and &lt;i&gt; tags allowed.','pbb-textdomain'),
							'label' => __('text after the link','pbb-textdomain'),
							),
						)
					), // end of field
					array(
						'id' => 'linkurl',
						'type' => 'text',
						'label' => __('Link URL','pbb-textdomain'),
						'desc' => __('Don\'t forget the http:// if linking outside of your site.','pbb-textdomain'),
					),
					array(
						'id' => 'link_newwindow',
						'type' => 'checkbox',
						'label' => __('Open link in new window','pbb-textdomain'),
					),
					array(
						'id' => 'link_as_button',
						'type' => 'checkbox',
						'label' => __('Show link as button','pbb-textdomain'),
					),
					array(
						'id' => 'linkclass',
						'type' => 'text',
						'label' => __('Link CSS Class','pbb-textdomain'),
						'desc' => __('Advanced Setting: Adds class to Span','pbb-textdomain'),
					),					
					array(
						'id' => 'ga_tracking',
						'type' => 'checkbox',
						'label' => __('Enable Google tracking events','pbb-textdomain'),
						'activates' =>'trackingsection',
						'activate_value' =>'on',
						'desc' => $tracking_desc,						
					),
					array(
						'section' => 'trackingsection',
						'id' => 'tracking_fields',
						'type' => 'row',
						'label' => __( 'Values for tracking','pbb-textdomain' ),

						'fields' => array(
							array(
							'id' => 'ga_category', // required
							'type' => 'text', // required
							'label' => '', // space as seperator
							'desc' => __('Event Category','pbb-textdomain'),
							'default'=> 'PBB',
							),
							array(
							'id' => 'ga_action', // required
							'type' => 'text', // required
							'label' => '', // space as seperator
							'desc' => __('Event Action','pbb-textdomain'),
							'default'=> 'LinkModule',
							),
							array(
							'id' => 'ga_label', // required
							'type' => 'text', // required
							'label' => '', // space as seperator
							'desc' => __('Event Label (optional)','pbb-textdomain'),
							),
						)
					), // end of field
					array(
						'id' => 'do_bounce',
						'type' => 'checkbox',
						'label' => __('Use bounce','pbb-textdomain'),
						'activates' =>'bouncesection',
						'activate_value' =>'on',						
					),
					array(
						'section' => 'bouncesection',
						'id' => 'delay_bounce_secs',
						'type' => 'text',
						'label' => __('Delay bouncing by time (seconds)','pbb-textdomain'),
					),					
					array(
						'id' => 'attach_bottom',
						'type' => 'checkbox',
						'label' => __('Attach bar to bottom instead of top','pbb-textdomain'),
					),
					array(
						'id' => 'show_branding',
						'type' => 'checkbox',
						'label' => __('Show the PBB Branding link','pbb-textdomain'),
					),	
					array(
						'id' => 'show_bar',
						'type' => 'checkbox',
						'label' => __('Show Peanut Butter Bar','pbb-textdomain'),
						'desc' => __('Uncheck this to temporarily hide the bar from your site.','pbb-textdomain'),
					),
				);
			$error_bar_values = get_transient('pbb-error-values');

			if (false!==$error_bar_values)
			{
				$bar_data = array_merge($error_bar_values['link'],$error_bar_values['theme'],$error_bar_values['tracking'],$error_bar_values['behavior']);			
			}
			delete_transient('pbb-error-values');
			$form_fields =  new PBBS_Settings_ScreenBuilder( get_settings_errors() );
			$form_fields->do_screen('pbb_bar',$fields, $bar_data, false);

			echo '<tr><td>';
			submit_button(  __('Save bar','pbb-textdomain'), 'primary', 'submit', true );
			echo "</td></tr>\n";
			echo "</table>\n";			
			
		}
		/**
		 * Builds hidden inputs. Not using pure SettingsAPI, so have to call manually
		 * 
		 * @param string $action 
		 */		
		function settings_fields($action){
			$option_group=$this->page_hook;
			echo '<form method="post" action="admin.php">';
			echo "<input type='hidden' name='option_page' value='" . esc_attr($option_group."_".$action). "' />";
			echo "<input type='hidden' name='action' value='pbb-$action' />";
			wp_nonce_field("$option_group-$action-options");
		}

		/**
		 * Returns the current bar
		 *
		 * @param array $bars. Array of bars.
		 * @return array Current bar.
		 */
		public function get_current_bar() {
			$bar = get_option($this->settingprefix.'_smooth');

			$bar_data = PBBS_Bar_Helper::flatten_bar($bar);
			return $bar_data;
		}
	}


}
?>