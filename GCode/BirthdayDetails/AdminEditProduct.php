<?php 
namespace GCode\BirthdayDetails;

require_once OBF_PLUGIN_DIR . 'GCode/BirthdayDetails/WooProductFields.php';

/**
 * Adds birthday details options to the Edit Product page under the "General"
 * tab of the "Product Data" meta box.
 * 
 * @author gareth
 */
class AdminEditProduct extends WooProductFields
{
    /**
     * Adds hooks to render the options fields in the Product Data/General
     * tab and save the field values.
     */
    public function register()
    {
        add_action('woocommerce_product_options_general_product_data', array($this, 'create_custom_birthday_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_birthday_fields'));
    }
    
    /**
     * Callback function to be called by WordPress.
     *
     * Renders a checkbox to determine whether this product requires birthday
     * details to be captured in the order.
     * 
     * Called on the
     * <code>woocommerce_product_options_general_product_data</code> action. Is
     * not called with any arguments.
     */
    public function create_custom_birthday_fields()
    {
        // WordPress global - the ID of the post being viewed/edited
        global $thepostid;
        
        // This actually echos out the HTML field - always output this field
        woocommerce_wp_checkbox(array(
            'id' => self::REQUIRES_BIRTH_DETAILS_FIELD_ID,
            'label' => __('Requires birth details', OBF_TEXT),
            'desc_tip' => true,
            'description' => __('Select whether customer should be asked for birth details when purchasing this product.', OBF_TEXT),
            'value' => self::requires_birth_details($thepostid) ? 'yes' : 'no',
        ));
    }
    
    /**
     * Callback function to be called by WordPress.
     *
     * Save the values of our custom fields on the product config page.
     * Called on the <code>woocommerce_process_product_meta</code> action.
     *
     * @param int $post_id the Product ID
     */
    public function save_custom_birthday_fields($post_id)
    {
        // save our data
        $product = wc_get_product($post_id);
        if (!empty($product))
        {
            $requires_details = $_POST[self::REQUIRES_BIRTH_DETAILS_FIELD_ID] ?? false;
            $product->update_meta_data(self::REQUIRES_BIRTH_DETAILS_PRODUCT_META, $requires_details);
            $product->save();
        }
    }
}
