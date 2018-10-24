<?php 


	class Form{
	/**
	 * Valid types for input tags (including HTML5)
	 */
	protected static $_valid_inputs = array(
		'button', 'checkbox', 'color', 'date', 'datetime',
		'datetime-local', 'email', 'file', 'hidden', 'image',
		'month', 'number', 'password', 'radio', 'range',
		'reset', 'search', 'submit', 'tel', 'text', 'time',
		'url', 'week'
	);

	/**
	 * @var  Fieldset
	 */
	protected $fieldset;

	public function __construct(array $config = array()){
		foreach ($config as $key => $val){
			self::set_config($key, $val);
		}
	}

	/**
	 * Set form attribute
	 *
	 * @param  string
	 * @param  mixed
	 */
	public static function set_attribute($key, $value)
	{
		$attributes = C('form_attributes', array());
		$attributes[$key] = $value;
		self::set_config('form_attributes', $attributes);

		return $this;
	}

	/**
	 * Get form attribute
	 *
	 * @param  string
	 * @param  mixed
	 */
	public static function get_attribute($key, $default = null){
		$attributes = C('form_attributes', array());

		return array_key_exists($key, $attributes) ? $attributes[$key] : $default;
	}

		/**
	 * Magic method toString that will build this as a form
	 *
	 * @return  string
	 */
	public function __toString(){
		return self::build();
	}

	/**
	 * Create a form open tag
	 *
	 * @param   string|array  action string or array with more tag attribute settings
	 * @return  string
	 */
	public static function open($attributes = array(), array $hidden = array()){
		$attributes = ! is_array($attributes) ? array('action' => $attributes) : $attributes;

		// If there is still no action set, Form-post
		if( ! array_key_exists('action', $attributes) or $attributes['action'] === null)
		{
			$attributes['action'] = URL('');
		}


		// If not a full URL, create one
		elseif ( ! strpos($attributes['action'], '://'))
		{
			$attributes['action'] = URL($attributes['action']);
		}

		if (empty($attributes['accept-charset']))
		{
			$attributes['accept-charset'] = strtolower('UTF-8');
		}

		// If method is empty, use POST
		! empty($attributes['method']) || $attributes['method'] = C('form_method', 'post');

		$form = '<form';
		foreach ($attributes as $prop => $value)
		{
			$form .= ' '.$prop.'="'.$value.'"';
		}
		$form .= '>';

		// Add hidden fields when given
		foreach ($hidden as $field => $value)
		{
			$form .= PHP_EOL.self::hidden($field, $value);
		}

		return $form;
	}

	/**
	 * Create a form close tag
	 *
	 * @return  string
	 */
	public static function close(){
		return '</form>';
	}	

	/**
	 * Create a fieldset open tag
	 *
	 * @param   array   array with tag attribute settings
	 * @param   string  string for the fieldset legend
	 * @return  string
	 */
	public static function fieldset_open($attributes = array(), $legend = null){
		$fieldset_open = '<fieldset ' . array_to_attr($attributes) . ' >';

		! is_null($legend) and $attributes['legend'] = $legend;
		if ( ! empty($attributes['legend']))
		{
			$fieldset_open.= "\n<legend>".$attributes['legend']."</legend>";
		}

		return $fieldset_open;
	}

	/**
	 * Create a fieldset close tag
	 *
	 * @return string
	 */
	public static function fieldset_close()
	{
		return '</fieldset>';
	}


	/**
	 * Create a form input
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function input($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
			! array_key_exists('value', $attributes) and $attributes['value'] = '';
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}

		$attributes['type'] = empty($attributes['type']) ? 'text' : $attributes['type'];

		if ( ! in_array($attributes['type'], static::$_valid_inputs))
		{
			throw new \InvalidArgumentException(sprintf('"%s" is not a valid input type.', $attributes['type']));
		}

		if (C('prep_value', true) && empty($attributes['dont_prep']))
		{
			$attributes['value'] = self::prep_value($attributes['value']);
		}
		unset($attributes['dont_prep']);

		if (empty($attributes['id']) && C('auto_id', false) == true)
		{
			$attributes['id'] = C('auto_id_prefix', 'form_').$attributes['name'];
		}

		$tag = ! empty($attributes['tag']) ? $attributes['tag'] : 'input';
		unset($attributes['tag']);

		return html_tag($tag, self::attr_to_string($attributes));
	}

	/**
	 * Create a hidden field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function hidden($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'hidden';

		return self::input($attributes);
	}


	/**
	 * Create a password input field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function password($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'password';

		return self::input($attributes);
	}

	/**
	 * Create a radio button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   mixed         either attributes (array) or bool/string to set checked status
	 * @param   array
	 * @return  string
	 */
	public static function radio($field, $value = null, $checked = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			is_array($checked) and $attributes = $checked;
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;

			# Added for 1.2 to allow checked true/false. in 3rd argument, used to be attributes
			if ( ! is_array($checked))
			{
				// If it's true, then go for it
				if (is_bool($checked))
				{
					if($checked === true)
					{
						$attributes['checked'] = 'checked';
					}
				}

				// Otherwise, if the string/number/whatever matches then do it
				elseif (is_scalar($checked) and $checked == $value)
				{
					$attributes['checked'] = 'checked';
				}
			}
		}
		$attributes['type'] = 'radio';

		return self::input($attributes);
	}

	/**
	 * Create a checkbox
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   mixed         either attributes (array) or bool/string to set checked status
	 * @param   array
	 * @return  string
	 */
	public static function checkbox($field, $value = null, $checked = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			is_array($checked) and $attributes = $checked;
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;

			# Added for 1.2 to allow checked true/false. in 3rd argument, used to be attributes
			if ( ! is_array($checked))
			{
				// If it's true, then go for it
				if (is_bool($checked))
				{
					if($checked === true)
					{
						$attributes['checked'] = 'checked';
					}
				}

				// Otherwise, if the string/number/whatever matches then do it
				elseif (is_scalar($checked) and $checked == $value)
				{
					$attributes['checked'] = 'checked';
				}
			}
		}
		$attributes['type'] = 'checkbox';

		return self::input($attributes);
	}

	/**
	 * Create a file upload input field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   array
	 * @return  string
	 */
	public static function file($field, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
		}
		$attributes['type'] = 'file';

		return self::input($attributes);
	}

	/**
	 * Create a button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function button($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
			$value = isset($attributes['value']) ? $attributes['value'] : $value;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$value = isset($value) ? $value :  $attributes['name'];
		}

		return html_tag('button', self::attr_to_string($attributes), $value);
	}

	/**
	 * Create a reset button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function reset($field = 'reset', $value = 'Reset', array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'reset';

		return self::input($attributes);
	}

	/**
	 * Create a submit button
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function submit($field = 'submit', $value = 'Submit', array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$attributes['type'] = 'submit';

		return self::input($attributes);
	}

	/**
	 * Create a textarea field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function textarea($field, $value = null, array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['value'] = (string) $value;
		}
		$value = is_scalar($attributes['value']) ? $attributes['value'] : '';
		unset($attributes['value']);

		if (C('prep_value', true) && empty($attributes['dont_prep']))
		{
			$value = self::prep_value($value);
		}
		unset($attributes['dont_prep']);

		if (empty($attributes['id']) && C('auto_id', false) == true)
		{
			$attributes['id'] = C('auto_id_prefix', '').$attributes['name'];
		}

		return html_tag('textarea', self::attr_to_string($attributes), $value);
	}

	/**
	 * Select
	 *
	 * Generates a html select element based on the given parameters
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string  selected value(s)
	 * @param   array   array of options and option groups
	 * @param   array
	 * @return  string
	 */
	public static function select($field, $values = null, array $options = array(), array $attributes = array())
	{
		if (is_array($field))
		{
			$attributes = $field;

			if ( ! isset($attributes['selected']))
			{
				$attributes['selected'] = ! isset($attributes['value']) ? (isset($attributes['default']) ? $attributes['default'] : null) : $attributes['value'];
			}
		}
		else
		{
			$attributes['name'] = (string) $field;
			$attributes['selected'] = ($values === null or $values === array()) ? (isset($attributes['default']) ? $attributes['default'] : $values) : $values;
			$attributes['options'] = $options;
		}
		unset($attributes['value']);
		unset($attributes['default']);

		if ( ! isset($attributes['options']) || ! is_array($attributes['options']))
		{
			throw new \InvalidArgumentException(sprintf('Select element "%s" is either missing the "options" or "options" is not array.', $attributes['name']));
		}
		// Get the options then unset them from the array
		$options = $attributes['options'];
		unset($attributes['options']);

		// Get the selected options then unset it from the array
		// and make sure they're all strings to avoid type conversions
		$selected = ! isset($attributes['selected']) ? array() : array_map(function($a) { return (string) $a; }, array_values((array) $attributes['selected']));

		unset($attributes['selected']);

		// workaround to access the current object context in the closure
		$current_obj =& $this;

		// closure to recusively process the options array
		$listoptions = function (array $options, $selected, $level = 1) use (&$listoptions, &$current_obj, &$attributes)
		{
			$input = PHP_EOL;
			foreach ($options as $key => $val)
			{
				if (is_array($val))
				{
					$optgroup = $listoptions($val, $selected, $level + 1);
					$optgroup .= str_repeat("\t", $level);
					$input .= str_repeat("\t", $level).html_tag('optgroup', array('label' => $key , 'style' => 'text-indent: '.(20+10*($level-1)).'px;'), $optgroup).PHP_EOL;
				}
				else
				{
					$opt_attr = array('value' => $key);
					$level > 1 and $opt_attr['style'] = 'text-indent: '.(10*($level-1)).'px;';
					(in_array((string)$key, $selected, true)) && $opt_attr[] = 'selected';
					$input .= str_repeat("\t", $level);
					$opt_attr['value'] = (C('prep_value', true) && empty($attributes['dont_prep'])) ?
						Form::prep_value($opt_attr['value']) : $opt_attr['value'];
					$val = (C('prep_value', true) && empty($attributes['dont_prep'])) ?
						Form::prep_value($val) : $val;
					$input .= html_tag('option', $opt_attr, $val).PHP_EOL;
				}
			}
			unset($attributes['dont_prep']);

			return $input;
		};

		// generate the select options list
		$input = $listoptions($options, $selected).str_repeat("\t", 0);

		if (empty($attributes['id']) && C('auto_id', false) == true)
		{
			$attributes['id'] = C('auto_id_prefix', '').$attributes['name'];
		}

		// if it's a multiselect, make sure the name is an array
		if (isset($attributes['multiple']) and substr($attributes['name'],-2) != '[]')
		{
			$attributes['name'] .= '[]';
		}

		return html_tag('select', self::attr_to_string($attributes), $input);
	}

	/**
	 * Create a label field
	 *
	 * @param   string|array  either fieldname or full attributes array (when array other params are ignored)
	 * @param   string
	 * @param   array
	 * @return  string
	 */
	public static function label($label, $id = null, array $attributes = array())
	{
		if (is_array($label))
		{
			$attributes = $label;
			$label = $attributes['label'];
			isset($attributes['id']) and $id = $attributes['id'];
		}

		if (empty($attributes['for']) and C('auto_id', false) == true)
		{
			empty($id) or $attributes['for'] = C('auto_id_prefix', 'form_').$id;
		}

		unset($attributes['label']);

		return html_tag('label', $attributes, T($label) ?: $label);
	}

	/**
	 * Prep Value
	 *
	 * Prepares the value for display in the form
	 *
	 * @param   string
	 * @return  string
	 */
	public static function prep_value($value)
	{
		$value = htmlentities($value, ENT_QUOTES);

		return $value;
	}

	/**
	 * Attr to String
	 *
	 * Wraps the global attributes function and does some form specific work
	 *
	 * @param   array  $attr
	 * @return  string
	 */
	public static function attr_to_string($attr)
	{
		unset($attr['label']);
		return array_to_attr($attr);
	}

	// fieldset related methods

	/**
	 * Returns the related fieldset
	 *
	 * @return  Fieldset
	 */
	public static function fieldset()
	{
		return self::fieldset;
	}

}
