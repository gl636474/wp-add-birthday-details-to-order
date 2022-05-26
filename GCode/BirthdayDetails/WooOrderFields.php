<?php 
namespace GCode\BirthdayDetails;

/**
 * Abstract base class for adding birthday details to a WooCommerce Order.
 * 
 * Contains logic common to frontend and admin.
 * @author gareth
 */
abstract class WooOrderFields
{
    /**
     * The minimum value for the year of birth.
     * @var integer
     */
    public const MIN_YEAR = 1900;
    
    /**
     * The maximum value for the year of birth.
     * @var integer
     */
    public const MAX_YEAR = 2200;
    
    /**
     * The name of the WC_Order metadata which holds the custom birth date
     * and/or time. Because this starts with an underscore, WordPress considers
     * it protected and will not show it in the Custom Fields meta-box for the
     * user to see or admin to edit.
     * @var string
     */
    public const BIRTHPLACE_ORDER_META = '_gcobf_birthplace';
    
    /**
     * The name of the WC_Order metadata which holds the custom birth date
     * and/or time. Because this starts with an underscore, WordPress considers
     * it protected and will not show it in the Custom Fields meta-box for the
     * user to see or admin to edit.
     * @var string
     */
    public const BIRTHDATETIME_ORDER_META = '_gcobf_birthdate';
    
    /**
     * The name of the HTML field containing the customer's birth day of month.
     * Also used for HTML ID.
     * @var string
     */
    public const BIRTH_DAY_FIELD_ID = 'gcobf_birthday';
    
    /**
     * The name of the HTML field containing the customer's birth month. Also
     * used for HTML ID.
     * @var string
     */
    public const BIRTH_MONTH_FIELD_ID = 'gcobf_birthmonth';
    
    /**
     * The name of the HTML field containing the customer's birth year. Also
     * used for HTML ID.
     * @var string
     */
    public const BIRTH_YEAR_FIELD_ID = 'gcobf_birthyear';
    
    /**
     * The name of the HTML field containing the customer's birth hour. Also
     * used for HTML ID.
     * @var string
     */
    public const BIRTH_HOUR_FIELD_ID = 'gcobf_birthhour';
    
    /**
     * The name of the HTML field containing the customer's birth minute. Also
     * used for HTML ID.
     * @var string
     */
    public const BIRTH_MINUTE_FIELD_ID = 'gcobf_birthmin';
    
    /**
     * The name of the order metadata containing the customer's birth place. Also
     * used for HTML INPUT name and other HTML IDs.
     * @var string
     */
    public const BIRTH_PLACE_FIELD_ID = 'gcobf_birthplace';
    
    /**
     * The suffix WP_Error 'code' suffix that denotes field validation failure.
     * @var string
     */
    protected const VALIDATION_SUFFIX = '_validation';
    
    /**
     * Returns an array with keys in the specified range and values being the
     * string version with leading zeros. The start can be greater than the end
     * in which case the range will count down.
     * 
     * @param int $start the start of the range
     * @param int $end the end of the range
     * @param string $placeholder the text for the blank 'placeholder' option. If
     *               omitted or null then no placeholder option will be output.
     * @param int $step step through the range in jumps of this amount
     * @return array with the keys being each integer in the range and the values
     *               being the stringified integer with leading zero(s) if
     *               necessary to make all value strings the same length.
     */
    protected function get_number_options($start, $end, $placeholder=null, $step=1)
    {
        if ($start < $end)
        {
            // Ensure $step is positive
            $step = abs(intval($step));
            $min = $start;
            $max = $end;
        }
        else
        {
            // Ensure $step is negative
            $step = abs(intval($step)) * -1;
            $min = $end;
            $max = $start;
        }
        
        // Initialise with placeholder if required
        $options = array();
        if (!empty($placeholder))
        {
            $options[''] = $placeholder;
        }
        
        $length = strlen(strval(max($start, $end)));
        $format = '%0'.$length.'u';
        for ($i=$start; $i>=$min && $i<=$max; $i+=$step)
        {
            $options[$i] = sprintf($format, $i);
        }
        return $options;
    }

    /**
     * Returns an array containing the twelve month names in the web users locale.
     * @return string[] the twelve month names in the web users locale.
     */
    protected function get_birth_months($placeholder=null)
    {
        $locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        if ($locale === false)
        {
            $locale = 'en_GB';
        }
        
        $format = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE, NULL, NULL, "MMMM");
        
        $months = array();
        if (!empty($placeholder))
        {
            $months[''] = $placeholder;
        }
        foreach (range(1, 12) as $month_number)
        {
            $months[$month_number] = datefmt_format($format, mktime(0, 0, 0, $month_number));
        }
        return $months;
    }
    
    /**
     * Determines whether an order requires birthday details to be captured.
     * This is true if one or more products in the order items requires birth
     * details to be captured or if birthday details should always be captured.
     * 
     * @param \WC_Order|int $order the order to test
     * @return boolean whether birthday details need to be captured for the
     *              supplied order.
     */
    protected function order_requires_birthday_details($order)
    {
        if (Config::instance()->show_always())
        {
            return true;
        }
        
        $order = wc_get_order($order);
        if ($order)
        {
            foreach ($order->get_items() as $order_item)
            {
                if ($order_item instanceof \WC_Order_Item_Product)
                {
                    $product_id = $order_item->get_product_id();
                    if (WooProductFields::requires_birth_details($product_id))
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}