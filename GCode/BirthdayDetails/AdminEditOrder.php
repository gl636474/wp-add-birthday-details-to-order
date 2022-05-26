<?php 
namespace GCode\BirthdayDetails;

require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/Config.php';
require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/WooOrderFields.php';

/**
 * Outputs fields to an admin page WooCommerce Order for birthday date, time and
 * place.
 *
 * Create an instance then call <code>register()</code> to activate it.
 *
 * @author gareth
 */
class AdminEditOrder extends WooOrderFields
{
    /**
     * Registers all necessary actions/filters to display the fields and parse
     * and process their input.
     */
    public function register()
    {
        add_action('admin_enqueue_scripts', array($this, 'setup_admin_scripts'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_birthday_fields'), 10, 1);
        add_action('user_profile_update_errors', array($this, 'validate_birthday_fields'), 10, 1);
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_birthday_fields'), 10, 1);
    }
    
    /**
     * Gets the specified meta data from the specified order and formats the
     * date for display.
     * 
     * @param \WC_Order $order the order from which to get the date meta data
     * @param string $key the key of the meta data to extract and format
     */
    protected function get_display_date_meta($order, $key)
    {
        $date = $order->get_meta($key);
        if ($date instanceof \DateTimeInterface)
        {
            $format = get_option('date_format') . " " . get_option('time_format');
            return $date->format($format);
        }
        else
        {
            return __("No date/time set", OBF_TEXT);
        }
    }
        
    /**
     * Callback function to be called by WordPress.
     *
     * Output the birthday details heading and fields. What is displayed and
     * how and whether required or optional is determined by settings from the
     * Config class. Called on the 
     * <code>woocommerce_admin_order_data_after_billing_address</code> action.
     * 
     * @param \WC_Order $order
     */
    public function display_birthday_fields($order)
    {
        if (!$this->order_requires_birthday_details($order))
        {
            return;
        }
        
        $date_enabled = Config::instance()->date_enabled();
        $time_enabled = Config::instance()->time_enabled();
        $place_enabled = Config::instance()->place_enabled();
        
        if (!$date_enabled && !$time_enabled && !$place_enabled)
        {
            // Nothing to do
            return;
        }
        ?>
            <div class="address">
        		<?php if ($date_enabled || $time_enabled): ?>
            		<p>
                		<strong><?php echo __("Birth Date", OBF_TEXT); ?>:</strong>
                		<span><?php echo $this->get_display_date_meta($order, self::BIRTHDATETIME_ORDER_META); ?></span>
                	</p>
            	<?php endif; ?>
            	<?php if ($place_enabled): ?>
					<p>
                		<strong><?php echo __("Birth Place", OBF_TEXT); ?>:</strong>
                		<span><?php echo $order->get_meta(self::BIRTHPLACE_ORDER_META); ?></span>
					</p>
				<?php endif; ?>
            </div>
			<div class="edit_address">
				<?php
				if ($date_enabled || $time_enabled)
				{
				    $date = $order->get_meta(self::BIRTHDATETIME_ORDER_META);
				    if ($date instanceof \DateTimeInterface)
				    {
				        $birthdate_edit_string = $date->format('Y-m-dTH:i');
				    }
				    else 
				    {
				        $birthdate_edit_string = '';
				    }
				    woocommerce_wp_text_input(array(
    				    'id'         => self::BIRTHDATETIME_ORDER_META,
    				    'name'       => self::BIRTHDATETIME_ORDER_META,
    				    'value'      => $birthdate_edit_string,
    				    'label'      => __("Birth Date", OBF_TEXT),
    				    'placeholder'=> __("Click to pick date", OBF_TEXT),
    				));
				    ?>
				    <script>
					jQuery('#<?php echo self::BIRTHDATETIME_ORDER_META; ?>').datetimepicker({
						changeMonth: true,
						changeYear: true,
						dateFormat: 'yy-mm-dd',
						numberOfMonths: 1,
						showButtonPanel: true,
						controlType: 'select',
						oneLine: true,
						timeFormat: 'hh:mm tt',
						yearRange: '<?php echo self::MIN_YEAR; ?>:<?php echo self::MAX_YEAR; ?>',
					}).datepicker('refresh');
				    </script>
				    <?php 
				}
				
				if ($place_enabled)
				{
    				woocommerce_wp_text_input(array(
    				    'id'         => self::BIRTHPLACE_ORDER_META,
    				    'name'       => self::BIRTHPLACE_ORDER_META,
    				    'value'      => $order->get_meta(self::BIRTHPLACE_ORDER_META),
    				    'label'      => __("Birth Place", OBF_TEXT),
    				    'placeholder'=> __("Town and country", OBF_TEXT),
    				));
				}
    			?>
			</div>
        <?php
    }

    /**
     * Callback function to be called by WordPress.
     *
     * Saves the custom birthday details in to the order metadata. Called on the 
     * <code>woocommerce_process_shop_order_meta</code> action.
     * 
     * @param integer $order_id ID of the order being saved
     */
    public function save_birthday_fields($order_id)
    {
        $order = wc_get_order( $order_id );
        if (!$this->order_requires_birthday_details($order))
        {
            return;
        }
        
        $birthdate_string = $_POST[self::BIRTHDATETIME_ORDER_META] ?? null;
        try 
        {
            if (!empty($birthdate_string))
            {
                $birthdate = new \DateTime($birthdate_string, new \DateTimeZone('UTC'));
            }
            else
            {
                $birthdate = '';
            }
            
            // update_meta_data() will add meta if it does not already exist
            $order->update_meta_data(self::BIRTHDATETIME_ORDER_META, $birthdate);
        }
        catch (\Exception $e)
        {
            // Don't update/save a bum date/time
            \WC_Admin_Meta_Boxes::add_error(sprintf(__("%s is an invalid birth date/time - ignored", OBF_TEXT), $birthdate_string));
        }
        
        // update_meta_data() will add meta if it does not already exist
        $birthplace = $_POST[self::BIRTHPLACE_ORDER_META] ?? null;
        $order->update_meta_data(self::BIRTHPLACE_ORDER_META, $birthplace);
        
        $order->save();
    }
    
    /**
     * Callback function to be called by WordPress.
     *
     * Tell WordPress about the CSS and JS scripts we use for rendering the
     * birthday fields. Called on the <code>wp_enqueue_scripts</code> action.
     */
    public function setup_admin_scripts()
    {
        if (is_admin() && get_post_type()=='shop_order')
        {
            wp_register_script('jquery-ui-timepicker-addon',
                OBF_PLUGIN_URL . 'web/jquery-ui-timepicker-addon.js',
                ['jquery-ui-slider','jquery-ui-datepicker']);
            
            wp_register_style('jquery-ui-timepicker-addon',
                OBF_PLUGIN_URL . 'web/jquery-ui-timepicker-addon.css');
            
            wp_enqueue_script('jquery-ui-timepicker-addon');
            wp_enqueue_style('jquery-ui-timepicker-addon');
        }
    }
}