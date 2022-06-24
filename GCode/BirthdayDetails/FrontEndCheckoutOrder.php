<?php 
namespace GCode\BirthdayDetails;

require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/Config.php';
require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/WooOrderFields.php';
require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/WooProductFields.php';

/**
 * Outputs fields to a front end WooCommerce Order for birthday date, time and
 * place.
 * 
 * Create an instance then call <code>register()</code> to activate it.
 * 
 * @author gareth
 */
class FrontEndCheckoutOrder extends WooOrderFields
{
    /** 
     * Registers all necessary actions/filters to display the fields and parse
     * and process their input.
     */
    public function register()
    {
        add_action('wp_enqueue_scripts', array($this, 'setup_frontend_scripts'));
        add_action('woocommerce_before_order_notes', array($this, 'display_birthday_fields'));
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_birthday_fields'), 10, 2);
        add_action('woocommerce_checkout_create_order', array($this, 'save_birthday_details'), 10, 2);
    }
    
    /**
     * Outputs the heading before the birthday fields. The actual text can be
     * changed via the <code>woocommerce_order_birthday_fields_heading</code> filter.
     */
    protected function display_heading()
    {
        $text = __('Birth Details', OBF_TEXT);
        $text = apply_filters('woocommerce_order_birthday_fields_heading', $text);
        
        if (is_string($text) && !empty($text))
        {
            ?><h3><?php echo esc_attr($text); ?></h3><?php
        }
    }
    
    /**
     * Validates the specified $_POST argument and sets error(s) accordingly.
     * 
     * @param string $id the ID of the field to validate - this is the key in the $_POST array
     * @param bool $required whether the field is required
     * @param string $title text which identifies the field for use in error messages, e.g. 'year of birth'
     * @param \WP_Error $errors error object to add validation error to
     * @param integer $min optional, the minimum value for this numeric field
     * @param integer $max optional, the maximum value for this numeric field
     * @return integer|null|false the valid field data or <code>null</code> if
     *         the field is empty and optional or <code>false</code> if the
     *         field content is invalid.
     */
    protected function validate_number_in_post_data($id, $required, $title, $errors, $min=null, $max=null)
    {
        // ?? operator returns 2nd arg when value in 1st arg array is null
        // Note that empty(0)==true and empty("0")==true
        $value = $_POST[$id] ?? '';
        if ($value==='')
        {
            if ($required)
            {
                $errors->add($id . self::VALIDATION_SUFFIX,
                    sprintf(__("Please enter your %s", OBF_TEXT), $title));
                return false;
            }
            else
            {
                return null;
            }
        }
        else
        {
            if (!is_numeric($value))
            {
                $errors->add($id . self::VALIDATION_SUFFIX,
                    sprintf(__("'%d' is not a valid %s", OBF_TEXT), $value, $title));
                return false;
            }
            else 
            {
                $value = intval($value);
                if (!is_null($min) && !is_null($max))
                {
                    if ($value < $min || $value > $max)
                    {
                        $errors->add($id . self::VALIDATION_SUFFIX,
                            sprintf(__("'%d' is not a valid %s - must be between %d and %d", OBF_TEXT), $value, $title, $min, $max));
                        return false;
                    }
                }
                elseif (!is_null($min))
                {
                    if ($value < $min)
                    {
                        $errors->add($id . self::VALIDATION_SUFFIX,
                            sprintf(__("'%d' is not a valid %s - must be %d or greater", OBF_TEXT), $value, $title, $min));
                        return false;
                    }
                }
                elseif (!is_null($max))
                {
                    if ($value > $max)
                    {
                        $errors->add($id . self::VALIDATION_SUFFIX,
                            sprintf(__("'%d' is not a valid %s - must be %d or less", OBF_TEXT), $value, $title, $max));
                        return false;
                    }
                }
                else 
                {
                    // No min or max specified - nothing to do
                }
                
                // If we got here then $value is non-empty, numeric and within
                // the min and/or max
                return $value;
            }
        }
    }
    
    /**
     * Determines whether the current cart requires birthday details to be
     * captured. This is true if either:
     * <ul>
     * <li>birthday details should always be captured</li>
     * <li>one or more products in the cart items requires birth details</li>
     * </ul>
     *
     * @return boolean whether birthday details need to be captured.
     */
    protected function cart_requires_birthday_details()
    {
        if (Config::instance()->show_always())
        {
            return true;
        }
        
        /* @var array $cart_item */
        foreach (WC()->cart->get_cart_contents() as $cart_item)
        {
            $product = $cart_item['data'] ?? null;
            if ($product instanceof \WC_Product && WooProductFields::requires_birth_details($product))
            {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Outputs or returns an HTML field in a WooCOmmerce compatible way. This
     * behaves exactly the same as <code>woocommerce_form_field()</code> with
     * the following exceptions:
     * 
     *   1. No <code>&lt;p&gt;</code> wrapper is output.
     *   2. No description is output (this should be on the wrapping multi-field)
     *   3. A hidden (CSS style <code>display: none;</code>) label is output (for screen readers to identify individual HTML fields) unless a checkbox or radio group is being output.
     * 
     * The field HTML is returned as a string if $args['return'] is present and
     * true, otherwise the HTML is output.
     * 
     * The first part of this function (the majority) is copy-paste from
     * WooCommerce wc-template-functions.php lines 2719 to 2912 (the majority
     * of the woocommerce_form_field() function). The one warning is due to an
     * copy/pasted variable which is unused as we create our own wrapper. This
     * variable has been left in to make diffs easier.
     * 
     * @param string $key the name of the HTML field 
     * @param array $args details of the field, exactly the same as for the WooCommerce woocommerce_form_field() function (with the aforementioned exceptions).
     * @param mixed $value=null the initial value to place in the field.
     */
    protected function woocommerce_form_subfield($key, $args, $value=null)
    {
        // Start copy-paste from wc-template-functions.php
        // NB: description and class keys are not used by us here.
        
        $defaults = array(
            'type'              => 'text',
            'label'             => '',
            'description'       => '',
            'placeholder'       => '',
            'maxlength'         => false,
            'required'          => false,
            'autocomplete'      => false,
            'id'                => $key,
            'class'             => array(),
            'label_class'       => array(),
            'input_class'       => array(),
            'return'            => false,
            'options'           => array(),
            'custom_attributes' => array(),
            'validate'          => array(),
            'default'           => '',
            'autofocus'         => '',
            'priority'          => '',
        );
        
        $args = wp_parse_args( $args, $defaults );
        $args = apply_filters( 'woocommerce_form_field_args', $args, $key, $value );
        
        if ( $args['required'] ) {
            $args['class'][] = 'validate-required';
            $required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
        } else {
            $required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
        }
        
        if ( is_string( $args['label_class'] ) ) {
            $args['label_class'] = array( $args['label_class'] );
        }
        
        if ( is_null( $value ) ) {
            $value = $args['default'];
        }
        
        // Custom attribute handling.
        $custom_attributes         = array();
        $args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );
        
        if ( $args['maxlength'] ) {
            $args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
        }
        
        if ( ! empty( $args['autocomplete'] ) ) {
            $args['custom_attributes']['autocomplete'] = $args['autocomplete'];
        }
        
        if ( true === $args['autofocus'] ) {
            $args['custom_attributes']['autofocus'] = 'autofocus';
        }
        
        if ( $args['description'] ) {
            $args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
        }
        
        if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
            foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
                $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
            }
        }
        
        if ( ! empty( $args['validate'] ) ) {
            foreach ( $args['validate'] as $validate ) {
                $args['class'][] = 'validate-' . $validate;
            }
        }
        
        $field           = '';
        $label_id        = $args['id'];
        $sort            = $args['priority'] ? $args['priority'] : '';
        $field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';
        
        switch ( $args['type'] ) {
            case 'country':
                $countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();
                
                if ( 1 === count( $countries ) ) {
                    
                    $field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';
                    
                    $field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';
                    
                } else {
                    $data_label = ! empty( $args['label'] ) ? 'data-label="' . esc_attr( $args['label'] ) . '"' : '';
                    
                    $field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ? $args['placeholder'] : esc_attr__( 'Select a country / region&hellip;', 'woocommerce' ) ) . '" ' . $data_label . '><option value="">' . esc_html__( 'Select a country / region&hellip;', 'woocommerce' ) . '</option>';
                    
                    foreach ( $countries as $ckey => $cvalue ) {
                        $field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
                    }
                    
                    $field .= '</select>';
                    
                    $field .= '<noscript><button type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update country / region', 'woocommerce' ) . '">' . esc_html__( 'Update country / region', 'woocommerce' ) . '</button></noscript>';
                    
                }
                
                break;
            case 'state':
                /* Get country this state field is representing */
                $for_country = isset( $args['country'] ) ? $args['country'] : WC()->checkout->get_value( 'billing_state' === $key ? 'billing_country' : 'shipping_country' );
                $states      = WC()->countries->get_states( $for_country );
                
                if ( is_array( $states ) && empty( $states ) ) {
                    
                    $field_container = '<p class="form-row %1$s" id="%2$s" style="display: none">%3$s</p>';
                    
                    $field .= '<input type="hidden" class="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '" readonly="readonly" data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"/>';
                    
                } elseif ( ! is_null( $for_country ) && is_array( $states ) ) {
                    $data_label = ! empty( $args['label'] ) ? 'data-label="' . esc_attr( $args['label'] ) . '"' : '';
                    
                    $field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="state_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ? $args['placeholder'] : esc_html__( 'Select an option&hellip;', 'woocommerce' ) ) . '"  data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . $data_label . '>
						<option value="">' . esc_html__( 'Select an option&hellip;', 'woocommerce' ) . '</option>';
                    
                    foreach ( $states as $ckey => $cvalue ) {
                        $field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
                    }
                    
                    $field .= '</select>';
                    
                } else {
                    
                    $field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"/>';
                    
                }
                
                break;
            case 'textarea':
                $field .= '<textarea name="' . esc_attr( $key ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) . ( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $value ) . '</textarea>';
                
                break;
            case 'checkbox':
                $field = '<label class="checkbox ' . implode( ' ', $args['label_class'] ) . '" ' . implode( ' ', $custom_attributes ) . '>
						<input type="' . esc_attr( $args['type'] ) . '" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ' . $args['label'] . $required . '</label>';
                
                break;
            case 'text':
            case 'password':
            case 'datetime':
            case 'datetime-local':
            case 'date':
            case 'month':
            case 'time':
            case 'week':
            case 'number':
            case 'email':
            case 'url':
            case 'tel':
                $field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';
                
                break;
            case 'hidden':
                $field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-hidden ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';
                
                break;
            case 'select':
                $field   = '';
                $options = '';
                
                if ( ! empty( $args['options'] ) ) {
                    foreach ( $args['options'] as $option_key => $option_text ) {
                        if ( '' === $option_key ) {
                            // If we have a blank option, select2 needs a placeholder.
                            if ( empty( $args['placeholder'] ) ) {
                                $args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'woocommerce' );
                            }
                            $custom_attributes[] = 'data-allow_clear="true"';
                        }
                        $options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_html( $option_text ) . '</option>';
                    }
                    
                    $field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
							' . $options . '
						</select>';
                }
                
                break;
            case 'radio':
                $label_id .= '_' . current( array_keys( $args['options'] ) );
                
                if ( ! empty( $args['options'] ) ) {
                    foreach ( $args['options'] as $option_key => $option_text ) {
                        $field .= '<input type="radio" class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" ' . implode( ' ', $custom_attributes ) . ' id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
                        $field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="radio ' . implode( ' ', $args['label_class'] ) . '">' . esc_html( $option_text ) . '</label>';
                    }
                }
                
                break;
        }
        
        // End copy-paste from wc-template-functions.php
        
        // Create and prepend label (unless label created above for radio buttons)
        if ( $args['label'] && 'checkbox' !== $args['type'] ) 
        {
            $label = '<label id="' . $args['id'] . '_label" for="' . esc_attr( $label_id ) . '" class="hidden">' . wp_kses_post( $args['label'] ) . $required . '</label>';
            $field = $label . $field;
        }
        
        if ($args['return'])
        {
            return $field;
        }
        else 
        {
            echo $field;
        }
    }
    
    /**
     * Outputs a field comprised of multiple HTML inputs or selects or
     * combination thereof.
     * 
     * There is no description parameter as this does not work with multi-
     * input fields.
     * 
     * @param array $field array containing the following keys:
     *                 * name - String. Mandatory. Name for the composite field
     *                 * id - String. Optional. HTML ID for the composite field
     *                 * data-priority - String. Optional.
     *                 * class - Array. Optional. CSS classes to be applied to the \Container
     *                 * required - Boolean. Optional. Whether the subfields must have values entered.
     *                 * label - String. Optional. Name for the label for the composite field
     * @param array $subfields an array  of sub-field arrays. See doc for <code>woocommerce_form_subfield</code> for content of the sub-field array.
     * @see woocommerce_form_subfield()
     */
    protected function woocommerce_form_multifield($field, $subfields)
    {
        $field_defaults = array(
            'id'            => $field['name'],
            'data_priority' => '',
            'class'         => array(),
            'required'      => false,
            'label'         => '',
            'divider'       => '',
        );        

        $field = wp_parse_args( $field, $field_defaults );
        
        $classes = array('form-row');
        if (is_array($field['class']))
        {
            $classes = array_merge($classes, $field['class']);
        }
        elseif (!empty($field['class'])) 
        {
            $classes[] = strval($field['class']);
        }
        
        if ($field['required'])
        {
            $classes[] = 'validate-required';
        }
        $classes = join(' ', $classes);

        $label_suffix = $field['required'] ? '<abbr class="required" title="required">*</abbr>' : '('.__('optional', 'woocommerce').')';
        
        echo "<p class=\"{$classes}\" id=\"{$field['id']}_field\" data-priority=\"{$field['data_priority']}\">";
        echo "<label class=\"gcobf_field_label\">{$field['label']}&nbsp;{$label_suffix}</label>";
        echo '<span class="woocommerce-input-wrapper">';
        
        $first_time = true;
        foreach ($subfields as $subfield)
        {
            if (!$first_time && !empty($field['divider']))
            {
                echo '<span class="gcobf_divider">' . $field['divider'] . '</span>';
            }
            $this->woocommerce_form_subfield($subfield['name'], $subfield, $subfield['value'] ?? null);
            $first_time = false;
        }
        
        echo '</span></p>';
    }
    
    /**
     * Callback function to be called by WordPress.
     * 
     * Output the birthday details heading and fields. What is displayed and
     * how and whether required or optional is determined by settings from the
     * Config class. Called on the <code>woocommerce_before_order_notes</code>
     * action.
     */
    public function display_birthday_fields()
    {
        if (!$this->cart_requires_birthday_details())
        {
            return;
        }
        
        $display_date = Config::instance()->date_enabled();
        $display_time = Config::instance()->time_enabled();
        $display_place = Config::instance()->place_enabled();
        
        if ($display_date || $display_time || $display_place)
        {
            $this->display_heading();
        }
        
        if ($display_date)
        {
            $required = Config::instance()->date_required();
            $type = Config::instance()->date_as_select() ? 'select' : 'number';
            $this->woocommerce_form_multifield(
                array(
                    'name'     => 'gcobf_birthdate',
                    'required' => $required,
                    'label'    => __('Date', OBF_TEXT),
                    'divider'  => '/',
                    'class'    => array('gcobf_birth_details'),
                    'description' => 'The date of your birth',
                ), 
                array(
                    array(
                        'name'        => 'gcobf_birthday',
                        'type'        => $type,
                        'options'     => $this->get_number_options(1, 31, __('-day-', OBF_TEXT)),
                        'required'    => $required,
                        'label'       => __('Birth Day', OBF_TEXT),
                        'value'       => $_REQUEST[self::BIRTH_DAY_FIELD_ID] ?? null,
                        'placeholder' => __('day', OBF_TEXT),
                    ),
                    array(
                        'name'     => 'gcobf_birthmonth',
                        'type'     => 'select',
                        'options'  => $this->get_birth_months(__('-month-', OBF_TEXT)),
                        'required' => $required,
                        'label'    => __('Birth Month', OBF_TEXT),
                        'value'    => $_REQUEST[self::BIRTH_MONTH_FIELD_ID] ?? null,
                    ),
                    array(
                        'name'        => 'gcobf_birthyear',
                        'type'        => $type,
                        'options'     => $this->get_number_options(self::MIN_YEAR, self::MAX_YEAR, __('-year-', OBF_TEXT)),
                        'required'    => $required,
                        'label'       => __('Birth Year', OBF_TEXT),
                        'value'       => $_REQUEST[self::BIRTH_YEAR_FIELD_ID] ?? null,
                        'placeholder' => __('year', OBF_TEXT),
                    ),
                )
            );
        }
        
        if ($display_time)
        {
            $required = Config::instance()->time_required();
            $type = Config::instance()->time_as_select() ? 'select' : 'number';
            $this->woocommerce_form_multifield(
                array(
                    'name'     => 'gcobf_birthtime',
                    'required' => $required,
                    'label'    => __('Time', OBF_TEXT),
                    'divider'  => ':',
                    'class'    => array('gcobf_birth_details'),
                ),
                array(
                    array(
                        'name'        => 'gcobf_birthhour',
                        'type'        => $type,
                        'options'     => $this->get_number_options(0, 23, __('-hr-', OBF_TEXT)),
                        'required'    => $required,
                        'label'       => __('Birth Hour', OBF_TEXT),
                        'value'       => $_REQUEST[self::BIRTH_HOUR_FIELD_ID] ?? null,
                        'placeholder' => __('hr', OBF_TEXT),
                    ),
                    array(
                        'name'        => 'gcobf_birthminute',
                        'type'        => $type,
                        'options'     => $this->get_number_options(0, 59, __('-min-', OBF_TEXT)),
                        'required'    => $required,
                        'label'       => __('Birth Minite', OBF_TEXT),
                        'value'       => $_REQUEST[self::BIRTH_MINUTE_FIELD_ID] ?? null,
                        'placeholder' => __('min', OBF_TEXT),
                    ),
                )
                );
        }

        if ($display_place)
        {
            // Always a text field and never in a wrapper.
            woocommerce_form_field(self::BIRTH_PLACE_FIELD_ID,
                array(
                'type'        => 'text',
                'label'       => __('Location', OBF_TEXT),
                'required'    => Config::instance()->place_required(),
                'placeholder' => __('town, county and country', OBF_TEXT),
                ),
                $_REQUEST[self::BIRTH_PLACE_FIELD_ID] ?? null
            );
            
        }
    }
    
    /**
     * Callback function to be called by WordPress.
     * 
     * Called to validate the birthday fields in the posted form.
     * 
	 * @param  array    $data   An array of WooCommerce specific posted data.
	 * @param  \WP_Error $errors Validation errors.
     */
    public function validate_birthday_fields($data, $errors)
    {
        if (!$this->cart_requires_birthday_details())
        {
            return;
        }
        
        if (Config::instance()->date_enabled())
        {
            $required = Config::instance()->date_required();

            $year = $this->validate_number_in_post_data(
                self::BIRTH_YEAR_FIELD_ID,
                $required,
                __('year of birth'),
                $errors,
                self::MIN_YEAR,
                self::MAX_YEAR
            );
            
            $month = $this->validate_number_in_post_data(
                self::BIRTH_MONTH_FIELD_ID,
                $required,
                __('month of birth'),
                $errors,
                1,
                12
            );
            
            // Validation of day depends upon validation of year and month
            $max_day = (is_numeric($year) && is_numeric($month)) ? cal_days_in_month(CAL_GREGORIAN, $month, $year) : 31;
            $day = $this->validate_number_in_post_data(
                self::BIRTH_DAY_FIELD_ID,
                $required,
                __('day of birth'),
                $errors,
                1,
                $max_day
            );
            
            $all_fields_filled = !is_null($day) && !is_null($month) && !is_null($year);
            $all_fields_empty = is_null($day) && is_null($month) && is_null($year);

            // If optional, ensure all fields are either filled and valid or
            // all fields are empty (invalid fields are handled above)
            if (!$required && !$all_fields_empty && !$all_fields_filled)
            {
                // Note $day/$month/$year are: integer|null|false (valid|empty|invalid)
                if (is_null($day))
                {
                    $errors->add(self::BIRTH_DAY_FIELD_ID . self::VALIDATION_SUFFIX,
                        __("Enter birth day, or leave all birth date fields blank", OBF_TEXT));
                }
                if (is_null($month))
                {
                    $errors->add(self::BIRTH_MONTH_FIELD_ID . self::VALIDATION_SUFFIX,
                        __("Enter birth month, or leave all birth date fields blank", OBF_TEXT));
                }
                if (is_null($year))
                {
                    $errors->add(self::BIRTH_YEAR_FIELD_ID . self::VALIDATION_SUFFIX,
                        __("Enter birth year, or leave all birth date fields blank", OBF_TEXT));
                }
            }
        }
        
        if (Config::instance()->time_enabled())
        {
            $required = Config::instance()->time_required();
            $hour = $this->validate_number_in_post_data(
                self::BIRTH_HOUR_FIELD_ID,
                $required,
                __('hour of birth'),
                $errors,
                0,
                23
                );
            $minute = $this->validate_number_in_post_data(
                self::BIRTH_MINUTE_FIELD_ID,
                $required,
                __('minute of birth'),
                $errors,
                0,
                59
                );
            
            $all_fields_filled = !is_null($hour) && !is_null($minute);
            $all_fields_empty = is_null($hour) && is_null($minute);
            
            // If optional, ensure all fields are either filled and valid or
            // all fields are empty (invalid fields are handled above)
            if (!$required && !$all_fields_empty && !$all_fields_filled)
            {
                // Note $hour/$minute are: integer|null|false (valid|empty|invalid)
                if (is_null($hour))
                {
                    $errors->add(self::BIRTH_HOUR_FIELD_ID . self::VALIDATION_SUFFIX,
                        __("Enter birth hour, or leave birth minute blank", OBF_TEXT));
                }
                if (is_null($minute))
                {
                    $errors->add(self::BIRTH_MINUTE_FIELD_ID . self::VALIDATION_SUFFIX,
                        __("Enter birth minute, or leave birth hour blank", OBF_TEXT));
                }
            }
        }
        
        if (Config::instance()->place_enabled() && Config::instance()->place_required())
        {
            $place = $_POST[self::BIRTH_PLACE_FIELD_ID] ?? null;
            if (empty($place))
            {
                $errors->add(self::BIRTH_PLACE_FIELD_ID . self::VALIDATION_SUFFIX,
                    __("Please enter your place of birth", OBF_TEXT));
            }
        }
    }
    
    /**
     * Callback function to be called by WordPress.
     * 
     * Save the birthday details from the submitted HTML fields (in $_POST)
     * into the WooCommere order object. Called on the 
     * <code>woocommerce_checkout_create_order</code> action.
     * 
     * @param \WC_Order $order
     * @param array $data Posted data from WooCommerce fields (does not include
     *                    our custom fields)
     */
    public function save_birthday_details($order, $data)
    {
        if (!$this->cart_requires_birthday_details())
        {
            return;
        }
        
        if (Config::instance()->place_enabled())
        {
            $place = $_POST[self::BIRTH_PLACE_FIELD_ID] ?? '';
            if (!empty($place))
            {
                $order->add_meta_data(self::BIRTHPLACE_ORDER_META, $place, true);
            }
        }
        
        $day = 0;
        $month = 0;
        $year = 0;
        $hour = 0;
        $minute = 0;
        
        if(Config::instance()->date_enabled())
        {
            // NB: empty string is valid by not caught by ?? operator
            $day = $_POST[self::BIRTH_DAY_FIELD_ID] ?? 0;
            $day = is_numeric($day) ? intval($day) : 0;
            
            $month = $_POST[self::BIRTH_MONTH_FIELD_ID] ?? 0;
            $month = is_numeric($month) ? intval($month) : 0;
            
            $year = $_POST[self::BIRTH_YEAR_FIELD_ID] ?? 0;
            $year = is_numeric($year) ? intval($year) : 0;
        }

        if (Config::instance()->time_enabled())
        {
            // NB: empty string is valid by not caught by ?? operator
            $hour = $_POST[self::BIRTH_HOUR_FIELD_ID] ?? 0;
            $hour = is_numeric($hour) ? intval($hour) : 0;

            $minute = $_POST[self::BIRTH_MINUTE_FIELD_ID] ?? 0;
            $minute = is_numeric($minute) ? intval($minute) : 0;
            
        }

        if (Config::instance()->date_enabled() || Config::instance()->time_enabled())
        {
            $datetime = new \DateTime("{$year}-{$month}-{$day}T{$hour}:{$minute}+00:00");
            $order->add_meta_data(self::BIRTHDATETIME_ORDER_META, $datetime);
        }
    }
    
    /**
     * Callback function to be called by WordPress.
     * 
     * Tell WordPress about the CSS scripts we use for rendering the birthday
     * fields. Does nothing unless the WooCommerce checkout page is being
     * displayed. Called on the <code>wp_enqueue_scripts</code> action.
     */
    public function setup_frontend_scripts()
    {
        if (is_checkout())
        {
            $css_script_url = OBF_PLUGIN_URL . 'web/birthday-details.css';
            wp_register_style( 'obf-birthday-details', $css_script_url );
            wp_enqueue_style( 'obf-birthday-details' );
        }
    }
}