<?php
/**
 * Class for managing data structure of plugin
 *
 * @version 0.1
 *
 * @author Andrew Couch
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'PBBS_Bar_Helper' ) &&  is_admin() ){

	class PBBS_Bar_Helper  {
		/**
		 * Structure used in rest of class for the fields of a Bar
		 */
		private static $structure = array(
			'metas'=>array(
				'theme'=>array(
					'name'=>'_pbb_theme',
					'fields'=>array(
						'theme'=>'pb',
						),
					),
				'link'=>array(
					'name'=>'_pbb_link',
					'fields'=>array(
						'use_linkmodule'=>'on',
						'pretitle'=>'',
						'linktext'=>'',
						'posttitle'=>'',
						'linkurl'=>'',
						'link_newwindow'=>false,
						'link_as_button'=>false,
						'linkclass'=>'',
						),
					),
				'tracking'=>array(
					'name'=>'_pbb_tracking',
					'fields'=>array(
						'ga_tracking'=>false,
						'ga_category'=>'PBB',
						'ga_action'=>'Click',
						'ga_label'=>'',
						),
					),
				'behavior'=>array(
					'name'=>'_pbb_behavior',
					'fields'=>array(
						'do_bounce'=>false,
						'delay_bounce_secs'=>'5',
						'attach_bottom'=>false,
						'show_branding'=>'on',
						'show_bar'=>'on',
						),
					),
			),
		);
		/**
		 * Converts a Bar as WP_Post object into a flat array
		 */
		public static function flatten_bar($bar)
		{
			$bar_output = array();
			foreach(PBBS_Bar_Helper::$structure['metas'] as $name=>$meta)
			{
				foreach($meta['fields'] as $key=>$default)
				{
					if (isset($bar[$name]))
					{
						$meta_collection = $bar[$name];
						$value = isset($meta_collection[$key]) ? $meta_collection[$key] : $default;
					}else{
						$value = $default;
					}
					$bar_output[$key]=stripslashes($value);
				}
			}
			return $bar_output;
		}
        /**
         * Nests a flat array of values(frex a POST) into their meta collections
         * 
         * @param array $bar_post
         */
        public static function array_to_bar($bar_post)
        {
            $bar_output = array();
            foreach(PBBS_Bar_Helper::$structure['metas'] as $name=>$meta)
            {
                foreach($meta['fields'] as $key=>$default)
                {
                    $value = isset($bar_post[$key]) ? $bar_post[$key] : $default;
                    if (!isset($bar_output[$name])){
                        $bar_output[$name]=array();
                    }
                    if (in_array($key,array('pretitle', 'linktext', 'posttitle',)))
                    {
						$args = array(
						    //formatting
						    'strong' => array(),
						    'em'     => array(),
						    'b'      => array(),
						    'i'      => array(),
						);                    	
                    	$value = wp_kses($value, $args);
                    }else{
	                    $value = sanitize_text_field($value);
	                }
                    $bar_output[$name][$key]=$value;
                }
            }
            return $bar_output;
		}		
	}
}
?>