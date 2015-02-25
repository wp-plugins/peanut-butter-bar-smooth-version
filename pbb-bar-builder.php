<?php 
/**
 * Class for building the bar.
 * Encapsulates reading and cleaning of options
 *
 * @version 0.1
 *
 * @author Andrew Couch
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'PBBS_Bar_Builder' ))
{
	class PBBS_Bar_Builder{
		private $options;
		private $linktext ;

		function __construct($name) {
			$this->options = array();
			$this->settingprefix = $name;
		}
		/**
		 * Builds HTML for bar for use as Post Excerpt
		 * 
		 * @param array $theme
		 * @param array $link_module
		 * @param array $tracking
		 * @param array $behavior
		 */
		public function make_bar_as_excerpt($theme, $link_module, $tracking, $behavior){
			$foundmodule = false;
 
			$bar_classes= array();
			$bar_data = array();
			if ($this->ischecked($behavior,'do_bounce'))
			{
				$bar_classes[]= 'dobounce';
				$bar_data['bounce_delay'] = $this->value($behavior,'delay_bounce_secs',5) * 1000;
			}
			if ($this->ischecked($behavior,'attach_bottom'))
			{
				$bar_classes[]= 'pbb-gravity';
			}
			$themeclass = $this->clean($this->value($theme,'theme','pb'), 'attr');

			$bar_classes[]= $themeclass;
			if ($themeclass == 'co' || $themeclass == 'lm')
			{
				$bar_classes[]= "pbb-light";
			}
			if ($themeclass == 'li')
			{
				$bar_classes[]= "pbb-dark";
			}
			
			$bar_data_string = '';
			foreach ($bar_data as $key => $value) {
				$bar_data_string .= sprintf(' data-pbb_%s="%s"', $key, $value);
			}
			$brandinglink = '';
			if ($this->ischecked($behavior,'show_branding')){
				$brandinglink = '<a id="pbb-logo" target="_blank" href="http://peanutbutterplugin.com/?utm_source=creditlink&utm_medium=bar"></a>';
			}
			$output = sprintf('<div id="pbb" class="%s"%s><div id="pbb-bar">%s<div class="pbb-modules">',implode(' ', $bar_classes),$bar_data_string, $brandinglink);
			if ($this->ischecked($link_module,'use_linkmodule', false))
			{
				$foundmodule = true;
				$link_html = '';
				if ($this->text($link_module,'linktext', '', '', '') != '')
				{
					$link_html = sprintf('<a href="%s"%s%s%s>%s</a>'
						,$this->text($link_module,'linkurl', '', '', ' ', 'attr')
						,$this->checkbox($link_module,'link_newwindow', false, ' target="_blank"', '')
						,$this->checkbox($link_module,'link_as_button', false, ' class="pbb-linkbutton"', '')
						,$this->ischecked($tracking,'ga_tracking', false) ? $this->pbb_generate_ga_tracking($tracking) : ''
						,$this->text($link_module,'linktext', '', '', ''));
				}
				$linkmodule_html = sprintf('<span class="pbb-text pbb-module%s">%s%s%s</span>'
					,$this->text($link_module,'linkclass', '', ' ', '')
					,$this->text($link_module,'pretitle', '', '', ' ')
					,$link_html
					,$this->text($link_module,'posttitle', '', ' ', ''));
				$output .= $linkmodule_html;
			}
			$output .= sprintf('</div><div id="pbb-closer"></div></div><div id="pbb-opener"></div></div>');
			if ($foundmodule && $this->ischecked($behavior,'show_bar'))
			{
				return $output;
			}else{
				return '';
			}
		}
		/**
		 * Builds tracking call for GA
		 * 
		 * @param array $options
		 */
		private function pbb_generate_ga_tracking($options)
		{
			$category = $this->text($options,'ga_category', 'PBB', '', '', 'js');
			$action = $this->text($options,'ga_action', 'LinkModule', '', '', 'js');
			$label = $this->text($options,'ga_label', 'null', '', '', 'js');
			$trackingstring = sprintf("pbb_dynamic_tracking('%s','%s','%s');",
						$category,
						$action,
						$label);

			return ' onclick="'. $trackingstring .'" ';
		}
		/**
		 * Reads (and caches) a DB option
		 * 
		 * @param string $section
		 * @param string $name
		 * @param string $default
		 */
		private function option($section, $name, $default = null){
			$sectionid = $this->settingprefix . '_' . $section;
			//Check if the options are already loaded
			if (!isset($options[$sectionid]))
			{
				//Load it from the store
				$this->options[$sectionid] = get_option( $sectionid );
			}
			$option = $this->options[$sectionid];
			return $this->value($option,$name,$default);
		}
		/**
		 * Reads option from array, applies $default if not set
		 *
		 * @param array $array
		 * @param string $name
		 * @param string $default
		 */		
		private function value($array, $name, $default = null){
			if (isset($array[$name]))
			{
				return $array[$name];
			}
			return $default;
		}
		/**
		 * Reads option from array, formats and escapes per arguments
		 *
		 * @param array $array
		 * @param string $name
		 * @param string $default : No formatting done if the default is given
		 * @param string $prefix : Text to add before
		 * @param string $postfix : Text to add after
		 * @param string $escape_type : 'attr','js' or none
		 */
		public function text($array, $name, $default='', $prefix='', $postfix='', $escape_type='')
		{
			$output = $this->value($array, $name ,$default);
			if ($output==$default){
				//Default was used, so don't add anything else to it
				return $output;
			}
			$output = $this->clean($output, $escape_type);
			return sprintf("%s%s%s", $prefix, $output, $postfix);
		}
		/**
		 * Cleans $var
		 *
		 * @param string $var
		 * @param string $escape_type : 'attr','js' or none
		 */		
		private function clean($var, $escape_type){
			$var = stripslashes($var);
			if ($escape_type =='js'){
				$var = esc_js($var);
			}else if ($escape_type =='attr'){
				$var = esc_attr($var);
			}
			return $var;
		}
		/**
		 * Interprets setting as checkbox, returns either on_value or off_value. 
		 *
		 * @param array $array
		 * @param string $name
		 * @param boolean $default 
		 * @param string $on_value : Text value if "on"
		 * @param string $off_Value : Teext value if "off"
		 */			
		public function checkbox($array, $name, $default=false, $on_value='', $off_value='')
		{
			return $this->ischecked($array, $name, $default) ? $on_value : $off_value;
		}
		/**
		 * Determines if setting represents a checked value or not
		 *
		 * @param array $array
		 * @param string $name
		 * @param boolean $default 
		 */			
		public function ischecked($array, $name, $default=false)
		{
			if (!$default || $default == 0){
				$default = 'off';
			} else{
				$default = 'on';
			}
			return $this->value($array, $name, $default) == 'on';		
		}

	}
}
?>