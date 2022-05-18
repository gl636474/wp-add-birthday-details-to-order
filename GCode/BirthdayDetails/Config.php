<?php 
namespace GCode\BirthdayDetails;

/**
 * Provides configuration for the other classes in this plugin. Mostly
 * this is pre-defined constants or functions wrapping WordPress get_option()
 * and update_option().
 * 
 * @author gareth
 */
class Config
{
    /**
     * The name of the get_option() option which determines whether each of the
     * date, time and place fields are disabled/optional/required and 
     * select/number inputs.
     * @var string
     */
    public const FIELD_OPTIONS = 'obf_field_options';
    
    /**
     * The name of the grouping of all the field options. Since all settings
     * are in one get_option() option, this group only has one option within
     * it.
     * @var string
     */
    public const FIELD_OPTIONS_GROUP = 'obf_field_options_group';
    
    /**
     * Denotes a field should not be displayed or saved.
     * @var integer
     */
    public const FIELD_DISABLED = 0;
    
    /**
     * Denotes a field should be displayed and is an optional field.
     * @var integer
     */
    public const FIELD_OPTIONAL = 1;
    
    /**
     * Denotes a field should be displayed and a value is required.
     * @var integer
     */
    public const FIELD_REQUIRED = 2;
    
    /**
     * WordPress get_option() key which determines whether the date fields
     * should be displayed and whether optional or required. Value should be
     * one of the <code>Config::FIELD_*</code> constants.
     * @var string
     */
    public const DATE_ENABLED_KEY = 'gcobf_date_enabled';
    
    /**
     * WordPress get_option() key which determines whether the time fields
     * should be displayed and whether optional or required. Value should be
     * one of the <code>Config::FIELD_*</code> constants. 
     * @var string
     */
    public const TIME_ENABLED_KEY = 'gcobf_time_enabled';
    
    /**
     * WordPress get_option() key which determines whether the place fields
     * should be displayed and whether optional or required. Value should be
     * one of the <code>Config::FIELD_*</code> constants. 
     * @var string
     */
    public const PLACE_ENABLED_KEY = 'gcobf_place_enabled';
    
    /**
     * WordPress get_option() key which determines whether the date fields
     * should be displayed on the front end as HTML select dropdowns (true)
     * or number (false) fields.
     * @var string
     */
    public const DATE_AS_SELECT_KEY = 'gcobf_date_select';
    
    /**
     * WordPress get_option() key which determines whether the date fields
     * should be displayed on the front end as HTML select dropdowns (true)
     * or number (false) fields.
     * @var string
     */
    public const TIME_AS_SELECT_KEY = 'gcobf_time_select';
    
    /**
     * The singleton instance of this class.
     * @var Config
     */
    protected static $the_instance = null;
    
    /**
     * Gets the singleton instance of this class.
     * @return Config the singleton instance.
     */
    public static function instance()
    {
        if (is_null(self::$the_instance))
        {
            self::$the_instance = new Config();
        }
        return self::$the_instance;
    }
    
    /**
     * Protected constructor to prevent unintended instantiation. Use
     * <code>instance()</code> instead. 
     */
    protected function __construct()
    {
        // No-op
    }
    
    /**
     * Returns the value stored in the WordPRess options table for the
     * DATE_ENABLED_KEY key in the FIELD_OPTIONS option array. 
     * @return mixed the value from the options or null if none set
     */
    public function get_date_option()
    {
        return get_option(self::FIELD_OPTIONS)[self::DATE_ENABLED_KEY] ?? null;
    }
    
    /**
     * Determines whether the birth date fields should be displayed/saved.
     * @return boolean whether the birth date fields should be displayed/saved.
     */
    public function date_enabled()
    {
        return !empty($this->get_date_option());
    }

    /**
     * Determines whether the birth date fields must be filled out.
     * @return boolean whether the birth date fields must be filled out.
     */
    public function date_required()
    {
        return $this->get_date_option() == self::FIELD_REQUIRED;
    }
    
    /**
     * Determines whether the birth date fields should be displayed as HTML
     * SELECT elements or NUMBER elements.
     * @return mixed true for SELECT element, false for NUMBER element or null
     *           if no option set.
     */
    public function date_as_select()
    {
        return get_option(self::FIELD_OPTIONS)[self::DATE_AS_SELECT_KEY] ?? null;
    }
    
    /**
     * Returns the value stored in the WordPRess options table for the
     * TIME_ENABLED_KEY key in the FIELD_OPTIONS option array.
     * @return mixed the value from the options or null if none set
     */
    public function get_time_option()
    {
        return get_option(self::FIELD_OPTIONS)[self::TIME_ENABLED_KEY] ?? null;
    }
    
    /**
     * Determines whether the birth time fields should be displayed/saved.
     * @return boolean whether the birth time fields should be displayed/saved.
     */
    public function time_enabled()
    {
        return !empty($this->get_time_option());
    }
    
    /**
     * Determines whether the birth time fields must be filled out.
     * @return boolean whether the birth time fields must be filled out.
     */
    public function time_required()
    {
        return $this->get_time_option() == self::FIELD_REQUIRED;
    }
    
    
    /**
     * Determines whether the birth time fields should be displayed as HTML
     * SELECT elements or NUMBER elements.
     * @return boolean true for SELECT element, false for NUMBER element.
     */
    public function time_as_select()
    {
        return get_option(self::FIELD_OPTIONS)[self::TIME_AS_SELECT_KEY] ?? null;
    }

    /**
     * Returns the value stored in the WordPRess options table for the
     * PLACE_ENABLED_KEY key in the FIELD_OPTIONS option array.
     * @return mixed the value from the options or null if none set
     */
    public function get_place_option()
    {
        return get_option(self::FIELD_OPTIONS)[self::PLACE_ENABLED_KEY] ?? null;
    }
    
    /**
     * Determines whether the birth place fields should be displayed/saved.
     * @return boolean whether the birth place fields should be displayed/saved.
     */
    public function place_enabled()
    {
        return !empty($this->get_place_option());
    }
    
    /**
     * Determines whether the birth place fields must be filled out.
     * @return boolean whether the birth place fields must be filled out.
     */
    public function place_required()
    {
        return $this->get_place_option() == self::FIELD_REQUIRED;
    }
}
