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
     * Outputs three fields for the birth date. These may be all SELECT
     * elements or may be a mixture of SELECT and NUMBER, depending upon
     * the setting in Config.
     */
    protected function display_birthdate_fields()
    {
        $as_select = Config::instance()->date_as_select();
        $required = Config::instance()->date_required();
        
        $day = $_REQUEST[self::BIRTH_DAY_FIELD_ID] ?? null;
        $min_day = 1;
        $max_day = 31;
        $day_field_args = array(
            'type'        => 'number',
            'label'       => __('Date', OBF_TEXT),
            'required'    => $required,
            'placeholder' => __('day', OBF_TEXT),
            'input_class' => array('gcodeobf_day'),
        );
        if ($as_select)
        {
            $day_field_args['type'] = 'select';
            $day_field_args['options'] = array(''=>'--') + range($min_day, $max_day);
        }
        else
        {
            $day_field_args['custom_attributes']['max'] = $max_day;
            $day_field_args['custom_attributes']['min'] = $min_day;
        }
        woocommerce_form_field(self::BIRTH_DAY_FIELD_ID, $day_field_args, $day);
        
        $this->display_divider('/'); 
    
        // Always a select, even if other sub-fields are text/numeric
        $month = $_REQUEST[self::BIRTH_MONTH_FIELD_ID] ?? null;
        woocommerce_form_field(self::BIRTH_MONTH_FIELD_ID,
            array(
                'type'        => 'select',
                'label'       => __('Month', OBF_TEXT),
                'options'     => $this->get_birth_months(__('--month--', OBF_TEXT)),
                'required'    => $required,
                'input_class' => array('gcodeobf_month'),
                'label_class' => array('hidden'),
            ),
            $month);
        
        $this->display_divider('/');
        
        $year = $_REQUEST[self::BIRTH_YEAR_FIELD_ID] ?? null;
        $min_year = 1900;
        $max_year = date('Y');
        $year_field_args = array(
            'type'        => 'number',
            'label'       => __('Year', OBF_TEXT),
            'required'    => true,
            'placeholder' => __('year', OBF_TEXT),
            'input_class' => array('gcodeobf_year'),
            'label_class' => array('hidden'),
        );
        if ($as_select)
        {
            $year_field_args['type'] = 'select';
            $year_field_args['options'] = array(''=>'----') + range($max_year, $min_year);
        }
        else 
        {
            $year_field_args['custom_attributes']['max'] = $max_year;
            $year_field_args['custom_attributes']['min'] = $min_year;
        }
        woocommerce_form_field(self::BIRTH_YEAR_FIELD_ID, $year_field_args, $year);
    }

    /**
     * Outputs two fields for the birth time. These may be both SELECT
     * elements or be both NUMBER, depending upon the setting in Config.
     */
    protected function display_birthtime_fields()
    {
        $as_select = Config::instance()->time_as_select();
        $required = Config::instance()->time_required();
        
        $hour = $_REQUEST[self::BIRTH_HOUR_FIELD_ID] ?? null;
        $min_hour = 0;
        $max_hour = 23;
        $hour_field_args = array(
            'type'        => 'number',
            'label'       => __('Time', OBF_TEXT),
            'required'    => $required,
            'placeholder' => __('hr', OBF_TEXT),
            'input_class' => array('gcodeobf_time'),
        );
        if ($as_select)
        {
            $hour_field_args['type'] = 'select';
            $hour_field_args['options'] = $this->get_number_options($min_hour, $max_hour, '--');
        }
        else
        {
            $hour_field_args['custom_attributes']['max'] = $max_hour;
            $hour_field_args['custom_attributes']['min'] = $min_hour;
        }
        woocommerce_form_field(self::BIRTH_HOUR_FIELD_ID, $hour_field_args, $hour);
        
        $this->display_divider(':');
        
        $minute = $_REQUEST[self::BIRTH_MINUTE_FIELD_ID] ?? null;
        $min_minute = 0;
        $max_minute = 59;
        $minute_field_args = array(
            'type'        => 'number',
            'label'       => __('Minute', OBF_TEXT),
            'required'    => $required,
            'placeholder' => __('min', OBF_TEXT),
            'input_class' => array('gcodeobf_time'),
            'label_class' => array('hidden'),
        );
        if ($as_select)
        {
            $minute_field_args['type'] = 'select';
            $minute_field_args['options'] = $this->get_number_options($min_minute, $max_minute, '--');
        }
        else
        {
            $minute_field_args['custom_attributes']['max'] = $max_minute;
            $minute_field_args['custom_attributes']['min'] = $min_minute;
        }
        woocommerce_form_field(self::BIRTH_MINUTE_FIELD_ID, $minute_field_args, $minute);
    }
    /**
     * Outputs a DIV element with the class "gcobf_divider" containing the
     * supplied text. Intended to output the slashes within a date or the
     * colon within a time.
     * 
     * @param string $text the text for the divider.
     */
    protected function display_divider($text)
    {
        ?>
        <span class="gcobf_divider"><?php echo esc_attr($text); ?></span>
        <?php 
    }
    
    /**
     * Outputs a DIV start tag with the specified attributes (e.g.
     * <code>array(id="my_id")</code> results in: 
     * <code><div id="my_id"></code>.
     * 
     * @param array $attributes the attributes of the DIV (e.g. id, class) as name=>value pairs.
     */
    protected function display_start_div($attributes=null)
    {
        $attribs_string = '';
        if (is_array($attributes) && !empty($attributes))
        {
            foreach ($attributes as $name=>$value)
            {
                $attribs_string .= ' '.$name.'="'.$value.'"';
            }
        }
        ?>
        <div<?php echo $attribs_string; ?>>
        <?php 
    }
    
    /**
     * Outputs a DIV end tag.
     */
    protected function display_end_div()
    {
        ?>
        </div>
        <?php 
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
        
        // If displaying both date and time, put them in an 'outer' flexbox
        if ($display_date && $display_time)
        {
            $this->display_start_div(array('id'=>'gcodeobf-birthday-details-wrapper'));
        }
        
        if ($display_date)
        {
            // Date subfields all need to be in an 'inner' flexbox
            $this->display_start_div(array('id'=>'gcodeobf-date-wrapper'));
            $this->display_birthdate_fields();
            $this->display_end_div();
        }
        
        if ($display_time)
        {
            // Time subfields all need to be in an 'inner' flexbox
            $this->display_start_div(array('id'=>'gcodeobf-time-wrapper'));
            $this->display_birthtime_fields();
            $this->display_end_div();
        }
        
        // If displaying both date and time, close the 'outer' flexbox
        if ($display_date && $display_time)
        {
            $this->display_end_div();
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
//                    'label_class' => array('gcodeobf_label'),
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