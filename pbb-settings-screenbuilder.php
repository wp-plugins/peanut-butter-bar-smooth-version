<?php
/**
 * Class for displaying setting screen including form fields
 *
 * @version 0.1
 * @author Andrew Couch
 * 
 * Based on https://github.com/keesiemeijer/WP-Settings
 * No longer require the DB Options directly (data passed in)
 * Added activator logic and color field
 * Added screen rendering logik in class
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'PBBS_Settings_ScreenBuilder' ) ) {
	class PBBS_Settings_ScreenBuilder {

		public $settings_errors;

		public $version = 1.0;

		public function __construct( $errors ) {
			$this->settings_errors = (array) $errors;
		}
		/**
		 * Shows a full screen
		 * 
		 * @param string $settingname : base name for the array in which the values will be posted
		 * @param array $fields
		 * @param array $data
		 * @param boolean $end_table : whether the "</table>" gets rendered. Useful for extra fields
		 */
		public function do_screen($settingname, $fields, $data, $end_table=true)
		{
			echo '<table class="form-table">';
			//Build a list of the section activators and their values
			$activators = array();
			if (isset($data))
			{			
				foreach ($fields as $field) 
				{
					if (isset($field['activates']) && isset($data[$field['id']]))
					{
						$activate_value = 'on';
						if (isset($field['activate_value'])){
							$activate_value = $field['activate_value'];
						}
						$activators[$field['activates']]=($data[$field['id']]==$activate_value)?true:false;
					}
				}
			}
			foreach ( $fields as $field ) {
 				$this->do_field($settingname, $field, $activators, $data);

			}
			if ($end_table)
				echo "</table>\n";
		}
		/**
		 * Shows a single field
		 *
		 * @param string $settingname : base name for the array in which the values will be posted
		 * @param array $field
		 * @param array $activators : list of activators on the screen and their values
		 * @param array $data : data for the whole screen
		 */
		private function do_field($settingname, $field, $activators, $data)
		{
			$defaults = array(
				'settingname'  => $settingname,
				'section'      => '',
				'id'           => '',
				'type'         => '',
				'label'        => '',
				'desc'         => '',
				'size'         => false,
				'options'      => '',
				'default'      => '',
				'content'      => '',
				'attr'         => false,
				'before'       => '',
				'after'        => '',
				'activates'    => '',
				'activate_value'    => '',
				'hide'         => false,
			);

			//Try to add the data to the individual fields
			if (isset($data))
			{
				if (isset($data[$field['id']]))
				{
					$defaults['value'] = $data[$field['id']];
				}
				if ($field['type']=="row")
				{
					//Add data to the nested fields of a row
					foreach ($field['fields'] as &$subfield) {
						if (isset($data[$subfield['id']]))
						{
							$subfield['value'] = $data[$subfield['id']];
						}
					}
				}
			}

			$args = wp_parse_args( $field, $defaults );
			if (isset($args['section']))
			{
				if (isset($activators[$args['section']])){
					if (!$activators[$args['section']])
					{
						$args['hide']='true';
					}
				}
			}

			if ( method_exists( $this, 'field_' . $args['type']  ) ) {
				$callback = 'field_' . $args['type'];
				$settingname = $args['settingname'];
				$id = $args['id'];
				$label = $args['label'];
				if ($args['type']!='hidden')
				{
					$section = $args['section'];
					$hidetext = '';
					if ($args['hide'])
					{
						$hidetext = ' style="display:none;"';
					}
					printf('<tr class="%s"%s><th>',$section,$hidetext);
					if (isset($label))
						printf ('<label for="%1$s[%2$s]">%3$s</label>',$settingname, $id, $label);
					echo '</th><td>';
				}
				$this->$callback($args);
				if ($args['type']!='hidden')
					echo "</td></tr>\n";
			}
		}		
		/**
		 * Displays type 'extra' content.
		 *
		 * @param array $args
		 */
		public function field_extra( $args ) {
			if ( isset( $args['content'] ) )
				echo $args['content'];
			if ( isset( $args['desc'] ) )
				echo $this->description( $args['desc'] );
		}


		/**
		 * Displays a hidden input setting field.
		 *
		 * @param array $args
		 */
		public function field_hidden( $args ) {
			$size  = ( isset( $args['size'] ) && $args['size'] !='' ) ? $args['size'] : 'regular';
			$args  = $this->get_arguments( $args, 'text', $size ); // escapes all attributes
			$value = (string) esc_attr( $this->get_option( $args ) );
			$html  = sprintf( '<input type="hidden" id="%1$s[%2$s]" name="%1$s[%2$s]" value="%3$s"/>',  $args['settingname'], $args['id'], $value );

			echo $html ."\n";
		}

		/**
		 * Displays a text input setting field.
		 *
		 * @param array $args
		 */
		public function field_text( $args ) {
			$size  = ( isset( $args['size'] ) && $args['size'] !='' ) ? $args['size'] : 'regular';
			$args  = $this->get_arguments( $args, 'text', $size ); // escapes all attributes
			$value = (string) esc_attr( $this->get_option( $args ) );
			$error = $this->get_setting_error( $args['id'] );
			$html  = sprintf( '<input type="text" id="%1$s[%2$s]" name="%1$s[%2$s]" value="%3$s"%4$s%5$s/>',  $args['settingname'], $args['id'], $value, $args['attr'], $error );

			echo $args['before'] . $html . $args['after'] . $this->description( $args['desc'] ) ."\n";
		}
		
		/**
		 * Displays a field label with a text value from data
		 *
		 * @param array $args
		 */
		public function field_display( $args ) {
			$args  = $this->get_arguments( $args, 'text' ); // escapes all attributes
			$value = (string) esc_attr( $this->get_option( $args ) );
			$html  = sprintf( '<span id="%1$s[%2$s]"4$s>%3$s</span>',  $args['settingname'], $args['id'], $value, $args['attr'] );

			echo $args['before'] . $html . $args['after'] . $this->description( $args['desc'] ) ."\n";
		}
		
		/**
		 * Displays a color input setting field.
		 *
		 * @param array $args
		 */
		public function field_color( $args ) {
			$size  = ( isset( $args['size'] ) && $args['size'] !='' ) ? $args['size'] : 'regular';
			$args  = $this->get_arguments( $args, 'color-picker' ); // escapes all attributes
			$value = (string) esc_attr( $this->get_option( $args ) );
			$error = $this->get_setting_error( $args['id'] );
			$html  = sprintf( '<input type="text" id="%1$s[%2$s]" name="%1$s[%2$s]" value="%3$s"%4$s%5$s/>',  $args['settingname'], $args['id'], $value, $args['attr'], $error );

			echo $args['before'] . $html . $args['after'] . $this->description( $args['desc'] )."\n";
		}

		/**
		 * Displays a single checkbox.
		 *
		 * @param array $args
		 */
		public function field_checkbox( $args ) {
			$args  = $this->get_arguments( $args, 'checkbox' ); // escapes all attributes
			$value = (string) esc_attr( $this->get_option( $args ) );
			$error = $this->get_setting_error( $args['id'], ' style="border: 1px solid red; padding: 2px 1em 2px 0; "' );
			$activator ='';

			$html  = '';
			$input = sprintf( '<input type="checkbox" id="%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s%5$s%6$s />', $args['settingname'], $args['id'], $value, checked( $value, 'on', false ), $args['attr'], $activator );
			$html .= sprintf( '<label for="%1$s[%2$s]"%4$s>%3$s</label>', $args['settingname'], $args['id'], $input, $error ). $this->description( $args['desc'] );

			echo $html ."\n";
		}


		/**
		 * Displays multiple checkboxes.
		 *
		 * @param array $args
		 */
		public function field_multicheckbox( $args ) {
			$args  = $this->get_arguments( $args, 'checkbox' ); // escapes all attributes
			$value = array_map( 'esc_attr', array_values( (array) $this->get_option( $args ) ) );
			$count = count( $args['options'] );
			$html  = '<fieldset>';
			$i = 0;

			foreach ( (array) $args['options'] as $opt => $label ) {
				$error = $this->get_setting_error( $opt, ' style="border: 1px solid red; padding: 2px 1em 2px 0; "'  );
				$checked = ( in_array( $opt , $value ) ) ? ' checked="checked" ' : '';
				$input = sprintf( '<input type="checkbox" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s%5$s />', $args['settingname'], $args['id'], $opt, $checked, $args['attr'] );
				$html .= sprintf( '<label for="%1$s[%2$s][%4$s]"%6$s>%3$s %5$s</label>', $args['settingname'], $args['id'], $input, $opt, $label, $error );
				$html .= ( isset( $args['row_after'][$opt] ) && $args['row_after'][$opt] ) ? $args['row_after'][$opt] : '';
				$html .= ( ++$i < $count ) ? '<br/>' : '';
			}

			echo $html . '</fieldset>' . $this->description( $args['desc'] )."\n";
		}


		/**
		 * Displays radio buttons.
		 *
		 * @param array $args
		 */
		public function field_radio( $args ) {
			$args = $this->get_arguments( $args, 'radio' ); // escapes all attributes
			$value = (string) esc_attr( $this->get_option( $args ) );
			$options = array_keys( (array) $args['options'] );
			// make sure one radio button is checked
			if ( empty( $value ) && ( isset( $options[0] ) && $options[0] ) ) {
				$value = $options[0];
			} elseif ( !empty( $value ) && ( isset( $options[0] ) && $options[0] ) ) {
				if ( !in_array( $value, $options ) )
					$value = $options[0];
			}
			$html = '<fieldset>';
			$i=0;
			$count = count( $args['options'] );
			foreach ( (array) $args['options'] as $opt => $label ) {
				$input = sprintf( '<input type="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s%5$s />', $args['settingname'], $args['id'], $opt, checked( $value, $opt, false ), $args['attr'] );
				$html .= sprintf( '<label for="%1$s[%2$s][%4$s]">%3$s%5$s</label>', $args['settingname'], $args['id'], $input, $opt, ' <span>'.$label.'</span>' );
				$html .= ( isset( $args['row_after'][$opt] ) && $args['row_after'][$opt] ) ? $args['row_after'][$opt] : '';
				$html .= ( ++$i < $count ) ? '<br/>' : '';
			}

			echo '</fieldset>' . $html . $this->description( $args['desc'] )."\n";
		}

		/**
		 * Displays a select dropdown.
		 *
		 * @param array $args
		 */
		public function field_select( $args ) {
			$size  = ( isset( $args['size'] ) && $args['size'] ) ? $args['size'] : 'regular';
			$args  = $this->get_arguments( $args, $size ); // escapes all attributes
			$value = array_map( 'esc_attr', array_values( (array) $this->get_option( $args ) ) );
			$multiple = ( preg_match( '/multiple="multiple"/', strtolower( $args['attr'] ) ) ) ? '[]' : '';
			$value = ( $multiple == '[]' ) ? $value : $value[0];
			$html  = sprintf( '<select id="%1$s[%2$s]" name="%1$s[%2$s]%4$s"%3$s>', $args['settingname'], $args['id'], $args['attr'], $multiple );
			foreach ( (array) $args['options'] as $opt => $label ) {
				$selected = selected( $value, $opt, false );
				if ( $multiple == '[]' )
					$selected = ( in_array( $opt , $value ) ) ? ' selected="selected" ' : '';
				$html .= sprintf( '<option value="%s"%s>%s</option>'."\n", $opt, $selected, $label );
			}
			$html .= sprintf( '</select>' );
			echo $args['before'] . $html . $args['after'] . $this->description( $args['desc'] )."\n";
		}

		/**
		 * Displays a textarea.
		 *
		 * @param array $args
		 */
		public function field_textarea( $args ) {
			$size  = ( isset( $args['size'] ) && $args['size'] ) ? $args['size'] : 'regular';
			$args  = $this->get_arguments( $args, 'textarea', $size ); // escapes all attributes
			$value = (string) esc_textarea( $this->get_option( $args ) );
			$error = $this->get_setting_error( $args['id'] );
			$html  = sprintf( '<textarea id="%1$s[%2$s]" name="%1$s[%2$s]"%4$s%5$s>%3$s</textarea>', $args['settingname'], $args['id'], $value, $args['attr'], $error );

			echo $args['before'] . $html . $args['after'] . $this->description( $args['desc'] )."\n";
		}


		/**
		 * Displays a multiple fields in a row.
		 *
		 * @param array $args
		 */
		public function field_row( $args ) {
			$multi =  false;
			$fields = array();

			foreach ( $args['fields'] as $key => $field ) {

				$defaults = array(
					'settingname' => $args['settingname'], 'id' => '', 'type' => '',
					'label' => '', 'desc' => '', 'size' => false, 'options' => '',
					'default' => '', 'content' => '', 'attr' => false,
					'before' => '', 'after' => '', 'field_row' => true
				);

				$args = wp_parse_args( $field, $defaults );

				if ( $args['type'] == 'multicheckbox' || $args['type'] == 'radio' ) {
					$multi = $args; // last field is used
					foreach ( $field['options'] as $key => $option ) {
						$multi['row_after'][$key] = '';
					}
				}

				// add a label to the extra fields
				if ( in_array( $field['type'], array( 'text', 'select', 'checkbox', 'textarea' ) ) ) {
					$args['before'] =  $args['before'] . sprintf( '<label for="%1$s[%2$s]">',  $args['settingname'], $args['id'] );
					$args['after'] =  sprintf( '%s</label>',  $args['label'] ) . $args['after'];
				}

				$fields[] = $args;
			}

			if ( $multi ) {
				// get the extra fields for 'multicheckbox' or 'radio'
				foreach ( $fields as $field ) {
					if ( $field['id'] != $multi['id'] ) {
						$options = array_keys( $multi['options'] );
						if ( isset( $field['option_id'] ) && in_array( $field['option_id'], $options ) ) {
							ob_start();
							$callback = 'field_' . $field['type'];
							if ( method_exists( $this, $callback  ) )
								$this->$callback( $field );
							$field_string = ob_get_contents();
							ob_end_clean();
							if ( !empty( $field_string ) )
								$multi['row_after'][$field['option_id']] .= $field_string;
						}
					}
				}
				// print the 'multicheckbox' or 'radio' field with (or without) it's extra fields
				$callback = 'field_' . $multi['type'];
				$this->$callback( $multi );
			} else {
				foreach ( $fields as $field ) {
					$callback = 'field_' . $field['type'];
					if ( method_exists( $this, $callback  ) )
						$this->$callback( $field );
				}
			}
		}


		/**
		 * Returns a field description.
		 *
		 * @param string $desc  Description of field.
		 */
		public function description( $desc = '' ) {
			if ( $desc )
				return sprintf( "\n".'<p class="description">%s</p>', $desc );
		}


		/**
		 * Returns validation errors for a settings field.
		 *
		 * @param string $setting_id Settings field ID.
		 * @param string $style Style to override the default error style.
		 * @return string Empty string or inline style attribute.
		 */
		private function get_setting_error( $setting_id, $attr = '' ) {
			$display_error = '';

			if ( !empty( $this->settings_errors ) ) {
				foreach ( $this->settings_errors as $error ) {
					if ( isset( $error['setting'] ) && $error['setting'] == $setting_id ) {
						if ( $attr == '' ) {
							$display_error = ' style="border: 1px solid red;"';
						} else {
							$display_error = $attr;
						}
					}
				}
			}

			return $display_error;
		}


		/**
		 * Escapes and creates additional attributes for a setting field.
		 *
		 * @param string|array $args Arguments of a setting field.
		 * @param string $input Type of field.
		 * @param string $size Size of field (class name).
		 * @return array All arguments and attributes
		 */
		private function get_arguments( $args = '', $type = false, $size = false ) {

			$args['settingname'] = esc_attr( $args['settingname'] );
			$args['id'] = esc_attr( $args['id'] );

			if ( isset( $args['options'] ) && $args['options'] ) {
				$options = array();
				foreach ( (array) $args['options'] as $key => $value ) {
					$options[ esc_attr( $key ) ] = $value;
				}
				$args['options'] = $options;
			}

			$attr_string = '';
			$defaults = array();
	
			if (isset($args['activates']) && $args['activates'] ) {
				if ( !isset( $args['attr']['class'] ) )
				{
					$args['attr']['class'] = '';
				}				
				$args['attr']['class'] .= ' pbb-panelactivator';
				$args['attr']['data-pbb-panel'] = $args['activates'];
				$args['attr']['data-pbb-activevalue'] = $args['activate_value'];
			}
			
			if ( isset( $args['attr'] ) && $args['attr'] )
				$attr = $args['attr'];
			else
				$attr = array();

			// set defaults for a textarea field
			if ( $type == 'textarea' ) {
				$type = 'text';
				$defaults = array( 'rows' => '5', 'cols' => '55' );
			}

			$attr = wp_parse_args( $attr, $defaults );

			if ( !isset( $attr['class'] ) )
				$attr['class'] = '';

			if ( $size && $type ) {
				if ( !isset( $attr['size'] ) )
					$attr['class'] .= sprintf( ' %1$s-%2$s', $size, $type );
			} else {
				if ( $type ) {
					$attr['class'] .= ' ' . $type;
				}
			}

			if ( $attr['class'] == '' )
				unset( $attr['class'] );

			// create attribute string
			foreach ( $attr as $key => $arg ) {
				$attr_string .= ' '. trim( $key ) . '="' . esc_attr( trim( $arg ) ) . '"';
			}

			$args['attr'] = $attr_string;
			return $args;
		}


		/**
		 * Returns the value of a setting field.
		 *
		 * @param array $args Arguments of setting field
		 * @return string
		 */
		public function get_option( $args ) {
			if ( isset( $args['value'] ) )
				return $args['value'];

			// return the default value
			return ( isset( $args['default'] ) ) ? $args['default'] : '';
		}


	} // class
} // class exists