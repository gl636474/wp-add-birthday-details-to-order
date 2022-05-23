<?php
namespace GCode\BirthdayDetails;

require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/Config.php';

/**
 * Registers and displays an admin menu item and page for editing the plugin
 * settings - as returned by methods in the Config class.
 *
 * @author gareth
 */
class AdminSettings
{
    /**
     * The menu slug (last part of URL) for our settings page.
     * @var string
     */
    public const MENU_SLUG = 'order-birthday-details';
    
    /**
     * The admin permissions required to show the menu item for our settings
     * page.
     * @var string
     */
    public const PERMISSIONS = 'manage_options';
    
    /**
     * ID for our settings page - contains one or more sections.
     * @var string
     */
    protected const PAGE_ID = 'obf-settings-page';
    
    /**
     * ID for the section of our settings page which deals with which fields
     * are enabled. 
     * @var string
     */
    protected const FIELDS_ENABLED_SECTION_ID = 'obf-fields-enabled-section';
    
    /**
     * ID for the section of our settings page which deals with what type of
     * input each field is
     * @var string
     */
    protected const FIELDS_TYPE_SECTION_ID = 'obf-fields-type-section';
    
    /**
     * ID for the section of our settings page which deals with what type of
     * input each field is
     * @var string
     */
    protected const FIELDS_WHEN_SHOWN_SECTION_ID = 'obf-fields-when-shown-section';
    
    /**
     * Cache of options for a 'field enabled' select input.
     * @see get_enabled_options()
     * @var array
     */
    protected $enabled_options = null;
    
    /**
     * Cache of options for a 'field type' select input.
     * @see get_input_type_options() 
     * @var array
     */
    protected $input_type_options = null;
    
    /**
     * Returns the array of options for a 'birthday field enabled' select input.
     * 
     * @return array Array key is the HTML field value and array value is the translated text for that option.
     */
    protected function get_enabled_options()
    {
        if (is_null($this->enabled_options))
        {
            $this->enabled_options = array(
                Config::FIELD_DISABLED => __("Disabled", OBF_TEXT),
                Config::FIELD_OPTIONAL => __("Enabled", OBF_TEXT),
                Config::FIELD_REQUIRED => __("Required", OBF_TEXT)
            );
        }
        return $this->enabled_options;
    }
    
    /**
     * Returns the array of options for a 'birthday field type' select input. 
     * 
     * @return array Array key is the HTML field value and array value is the translated text for that option.
     */
    protected function get_input_type_options()
    {
        if (is_null($this->input_type_options))
        {
            $this->input_type_options = array(
                false => __("Number Input", OBF_TEXT),
                true  => __("Dropdown", OBF_TEXT)
            );
        }
        return $this->input_type_options;
    }
    
    /**
     * Adds hooks to register the menu items, settings page renderers and
     * options.
     */
    public function register()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_menu', array($this, 'register_sections_and_fields'));
    }
    
    /*************************************************************************
     * WordPress get_option() related functions
     *************************************************************************/
    
    /**
     * Callback function to be called by WordPress.
     * 
     * Registers the get_option() settings and validation function. Called on
     * the <code>admin_init</code> action.
     */
    public function register_settings()
    {
        // One setting/database entry for all our field settings.
        register_setting(
            Config::FIELD_OPTIONS_GROUP,
            Config::FIELD_OPTIONS,
            array($this, 'validate_field_settings')
        );
    }
    
    /**
     * Validates and returns the supplied field options array.
     *
     * @param array $options
     *            key-value settings.
     * @return array the validated array with unexpected keys and values
     *            ignored.
     */
    public function validate_field_settings($options)
    {
        // Valid values for the enabled settings
        $valid_enabled_values = array(
            Config::FIELD_DISABLED,
            Config::FIELD_OPTIONAL,
            Config::FIELD_REQUIRED
        );
        
        // Default to existing values
        $validated = get_option(Config::FIELD_OPTIONS);
        
        // Field disabled/optional/required settings
        $date_enabled = $options[Config::DATE_ENABLED_KEY] ?? null;
        if (in_array($date_enabled, $valid_enabled_values))
        {
            $validated[Config::DATE_ENABLED_KEY] = $date_enabled;
        }
        $time_enabled = $options[Config::TIME_ENABLED_KEY] ?? null;
        if (in_array($date_enabled, $valid_enabled_values))
        {
            $validated[Config::TIME_ENABLED_KEY] = $time_enabled;
        }
        $place_enabled = $options[Config::PLACE_ENABLED_KEY] ?? null;
        if (in_array($date_enabled, $valid_enabled_values))
        {
            $validated[Config::PLACE_ENABLED_KEY] = $place_enabled;
        }
        
        // Show field as dropdown/number-input settings
        if (isset($options[Config::DATE_AS_SELECT_KEY]))
        {
            $validated[Config::DATE_AS_SELECT_KEY] = boolval($options[Config::DATE_AS_SELECT_KEY]);
        }
        if (isset($options[Config::TIME_AS_SELECT_KEY]))
        {
            $validated[Config::TIME_AS_SELECT_KEY] = boolval($options[Config::TIME_AS_SELECT_KEY]);
        }
        
        // When to show settings
        if (isset($options[Config::SHOW_ALWAYS_KEY]))
        {
            $validated[Config::SHOW_ALWAYS_KEY] = boolval($options[Config::SHOW_ALWAYS_KEY]);
        }
        
        return $validated;
    }
    
    /*************************************************************************
     * WordPress settings API related functions
     *************************************************************************/
    
    /**
     * Callback function to be called by WordPress.
     *
     * Registers the admin sub-menu and settings page renderer function. Called
     * on the <code>admin_menu</code> action.
     */
    public function add_menu()
    {
        // Add a "Settings" sub-menu for our settings page
        add_options_page(
            __('Additional WooCommerce Order Fields for Birthday Details', OBF_TEXT),
            __('Birthday Order Fields', OBF_TEXT), // Menu item text
            self::PERMISSIONS,
            self::MENU_SLUG,
            array ($this, 'render_settings_page')
        );
    }
    
    /**
     * Callback function to be called by WordPress.
     * 
     * Custom action to register our settings sections and fields. Called
     * on the <code>admin_menu</code> action.
     */
    public function register_sections_and_fields ()
    {
        add_settings_section(self::FIELDS_ENABLED_SECTION_ID,
            __("Which birthday fields to show", OBF_TEXT),
            array($this, 'render_fields_enabled_preamble'),
            self::PAGE_ID);
        
        add_settings_field('obf-date-enabled',
            __('Display Birth Date', OBF_TEXT),
            array($this, 'render_birth_date_enabled_field'),
            self::PAGE_ID,
            self::FIELDS_ENABLED_SECTION_ID);
        
        add_settings_field('obf-time-enabled',
            __('Display Birth Time', OBF_TEXT),
            array($this, 'render_birth_time_enabled_field'),
            self::PAGE_ID,
            self::FIELDS_ENABLED_SECTION_ID);
        
        add_settings_field('obf-place-enabled',
            __('Display Birth Place', OBF_TEXT),
            array($this, 'render_birth_place_enabled_field'),
            self::PAGE_ID,
            self::FIELDS_ENABLED_SECTION_ID);
        
        add_settings_section(self::FIELDS_TYPE_SECTION_ID,
            __("How to show birthday fields", OBF_TEXT),
            array($this, 'render_fields_type_preamble'),
            self::PAGE_ID);
        
        add_settings_field('obf-date-type',
            __('Birth Date Input Type', OBF_TEXT),
            array($this, 'render_birth_date_type_field'),
            self::PAGE_ID,
            self::FIELDS_TYPE_SECTION_ID);
        
        add_settings_field('obf-time-type',
            __('Birth Time Input Type', OBF_TEXT),
            array($this, 'render_birth_time_type_field'),
            self::PAGE_ID,
            self::FIELDS_TYPE_SECTION_ID);
        
        add_settings_section(self::FIELDS_WHEN_SHOWN_SECTION_ID,
            __("When to show birthday fields", OBF_TEXT),
            array($this, 'render_when_shown_preamble'),
            self::PAGE_ID);
        
        add_settings_field('obf-when-to-show',
            __('Show fields', OBF_TEXT),
            array($this, 'render_when_shown_field'),
            self::PAGE_ID,
            self::FIELDS_WHEN_SHOWN_SECTION_ID);
    }
     
    /**
     * Echos out the HTML snippet for our form on our settings page.
     */
    public function render_settings_page()
    {
        // Check user capabilities
        if (! current_user_can('manage_options'))
        {
            return;
        }
        
        ?>
        <div class="wrap">
        	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        	<form action="options.php" method="post">
            	<?php
                // Output security fields for our registered setting
                settings_fields(Config::FIELD_OPTIONS_GROUP);
            
                // Output setting sections and their fields for specified page
                do_settings_sections(self::PAGE_ID);
            
                // Output save settings button
                submit_button(__('Save Settings', OBF_TEXT));
                ?>
            </form>
        </div>
    	<?php
    }

    /**
     * Echos out the HTML snippet which is the preamble for the "Fields
     * enabled" section;
     */
    public function render_fields_enabled_preamble()
    {
        ?>
        <ul>
        	<li>Disabled - Do not show the field</li>
        	<li>Enabled - Show the field but allow it to be empty</li>
        	<li>Required - Show the field and require a value to be entered</li>
        </ul> 
        <?php 
    }
    
    /**
     * Echos out the HTML snippet which is the preamble for the "Fields
     * type" section;
     */
    public function render_fields_type_preamble()
    {
        ?>
        <ul>
        	<li>Dropdown - Value must be selected from a list</li>
        	<li>Number Input - A text box into which a number is typed</li> 
        </ul> 
        <?php 
    }
    
    /**
     * Echos out the HTML snippet which is the preamble for the "When to 
     * show" section;
     */
    public function render_when_shown_preamble()
    {
        ?>
        The enabled/required birthday details fields can optionally only be
        shown when one or more configured products are being purchased. To
        enable this feature, select "Product dependent" and for each product
        for which birthday details should be captured:
        <ol>
        	<li>Go to the "Edit Product" page for that product</li>
        	<li>Scroll down to the "Product data" box</li>
        	<li>Click "Requires birthday details" under the "General" tab</li>
      	</ol>
        <?php 
    }
    
    /**
     * Renders the select to determine whether the date fields are displayed.
     * @param array $args
     */
    public function render_birth_date_enabled_field($args)
    {
        $this->render_select(
            Config::FIELD_OPTIONS.'['.Config::DATE_ENABLED_KEY.']',
            $this->get_enabled_options(),
            Config::instance()->get_date_option(),
            $args['label_for'] ?? 'date-enabled',
        );
    }
    
    /**
     * Renders the select to determine whether the time fields are displayed.
     * @param array $args
     */
    public function render_birth_time_enabled_field($args)
    {
        $this->render_select(
            Config::FIELD_OPTIONS.'['.Config::TIME_ENABLED_KEY.']',
            $this->get_enabled_options(),
            Config::instance()->get_time_option(),
            $args['label_for'] ?? 'time-enabled',
        );
    }
    
    /**
     * Renders the select to determine whether the place field is displayed.
     * @param array $args
     */
    public function render_birth_place_enabled_field($args)
    {
        $this->render_select(
            Config::FIELD_OPTIONS.'['.Config::PLACE_ENABLED_KEY.']',
            $this->get_enabled_options(),
            Config::instance()->get_place_option(),
            $args['label_for'] ?? 'place-enabled',
        );
    }
        
    /**
     * Renders the select to determine how the date field is displayed.
     * @param array $args
     */
    public function render_birth_date_type_field($args)
    {
        $this->render_select(
            Config::FIELD_OPTIONS.'['.Config::DATE_AS_SELECT_KEY.']',
            $this->get_input_type_options(),
            Config::instance()->date_as_select(),
            $args['label_for'] ?? 'date-type',
            __('Month (if shown) will always be a dropdown even if day/year is a number input.', OBF_TEXT),
        );
    }
    
    /**
     * Renders the select to determine how the time field is displayed.
     * @param array $args
     */
    public function render_birth_time_type_field($args)
    {
        $this->render_select(
            Config::FIELD_OPTIONS.'['.Config::TIME_AS_SELECT_KEY.']',
            $this->get_input_type_options(),
            Config::instance()->time_as_select(),
            $args['label_for'] ?? 'time-type',
        );
    }
    
    /**
     * Renders the select to determine when birthday details fields are
     * displayed.
     * @param array $args
     */
    public function render_when_shown_field($args)
    {
        $show_options = array(
            true  => __("Always", OBF_TEXT),
            false => __("Product dependent", OBF_TEXT),
        );
        
        $this->render_select(
            Config::FIELD_OPTIONS.'['.Config::SHOW_ALWAYS_KEY.']',
            $show_options,
            Config::instance()->show_always(),
            $args['label_for'] ?? 'show-always'
        );
    }
    
    /**
     * Renders an HTML select input. Unlike <code>woocommerce_wp_select()</code>, this
     * does not render a label or wrapper element(s).
     *   
     * @param string $name The name of the select element
     * @param array $options Array of options for the select. The array key is the value to be saved as the field value, the array value is the text to display. 
     * @param string $selected the key within $options to display as selected. Supplying <code>null</code> will cause a 'Please Select' option to be generated.
     * @param string $html_id=null Optional. The HTML ID of the select element. If omitted, $name will be used.
     * @param string $description=null Optional. Explanatory text to be displayed under the select.
     */
    protected function render_select($name, $options, $selected, $html_id=null, $description=null)
    {
        $selected = intval($selected);
        $selected = array_key_exists($selected, $options) ? $selected : null;
        $html_id = !empty($html_id) ? $html_id : $name;
        
        ?>
        <select id="<?php echo $html_id; ?>" name="<?php echo $name; ?>">
        	<?php if (is_null($selected)): ?>
        		<option value="" selected="selected"><?php echo __("Select...", OBF_TEXT); ?></option>
        	<?php endif ?>
        	<?php foreach ($options as $value=>$text): ?>
        		<option value="<?php echo $value; ?>" <?php selected($selected, $value); ?>><?php echo $text; ?></option>
        	<?php endforeach ?>
        </select>
        <?php if (is_string($description)): ?>
        	<p class="description"><?php echo $description; ?></p>
        <?php endif ?>
        <?php
    }
}
