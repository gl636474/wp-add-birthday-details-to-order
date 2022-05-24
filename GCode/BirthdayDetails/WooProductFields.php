<?php 
namespace GCode\BirthdayDetails;

/**
 * Base/Utility class providing constants and util functions for working with
 * WooCommerce products.
 *  
 * @author gareth
 */
abstract class WooProductFields
{
    /**
     * The name of the HTML field containing the flag determining whether
     * birthday details should be captured for orders containing this product.
     * Also used for HTML ID.
     * @var string
     */
    public const REQUIRES_BIRTH_DETAILS_FIELD_ID = 'gcobf_birth_details_required';
    
    /**
     * The name of the WC_Product metadata which holds the flag determining
     * whether birthday details should be captured for orders containing this
     * product. Because this starts with an underscore, WordPress considers it
     * protected and will not show it in the frontend or in the admin Custom
     * Fields meta-box for admin to edit.
     * @var string
     */
    public const REQUIRES_BIRTH_DETAILS_PRODUCT_META = 'gcobf_requires_birth_details';
    
    /**
     * Determines whether the supplied product requires birthday details to be
     * captured in the order (if the product is in the basket/is an order
     * item).
     * @param \WP_Post|\WC_Product|int|string|false $post product to test as product, post or product/post ID or <code>false</code> to indicate current the product if editing/viewing a product.
     * @return bool whether the supplied product requires birthday details
     */
    public static function requires_birth_details($post)
    {
        $product = wc_get_product($post);
        if (!empty($product))
        {
            // get_meta can return false or empty array or null if no such key exists
            return boolval($product->get_meta( self::REQUIRES_BIRTH_DETAILS_PRODUCT_META ));
        }
        else 
        {
            return false;
        }
    }
}