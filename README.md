# Add Birthday Details to Order
## Overview
This is a WordPress plugin to add birthday details to a WooCommerce Order. Fields are displayed to capture:
 * birth date
 * birth time
 * birth place

This information can be entered by the customer on the front end "Checkout" page and by the admin in the admin "Edit order" page. All of the above fields can are configurable to be:
 * disabled
 * optional (can be empty)
 * required (must contain a valid value)

Additionally these fields can be configured to be displayed as:
 * dropdowns (HTML 'select')
 * number (HTML text 'input')

## Installation
 1. First install WooCommerce (version 3.6 or above) and create one or more products (see WooCommerce documentation).
 1. Install the "WP Add Birthday Details" plugin.
 1. In the admin "Settings" menu, select the "Birthday Order Fields" menu item.
 1. Follow on screen prompts.

## Notes
If you choose to display birthday fields "only for configures products" then you must ensure that you configure at least one product with "Require birthday details" ticked. See the "Edit Product" page, "Product Details" box, "General" tab. At least one such product must be in the basket (in the front end) or added as an item (in the admin "Edit Order" page) for the fields to be displayed.

