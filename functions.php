<?php

function add_confirmation_woocommerce_email( $email_classes ) {

    // include our custom email class
    require( 'includes/class-wc-confirmation-email.php' );
  
    // add the email class to the list of email classes that WooCommerce loads
    $email_classes['WC_Confirmation_Email'] = new WC_Confirmation_Email();
  
    return $email_classes;
  
}
add_filter( 'woocommerce_email_classes', 'add_confirmation_woocommerce_email' );
  
// Customise Product Availability Text
add_filter( 'woocommerce_get_availability', 'woocommerce_get_availability_custom', 1, 2);
function woocommerce_get_availability_custom( $availability, $_product ) {
	// 	Change In Stock Text
	    if ( $_product->is_in_stock() ) {
		$availability['availability'] = __('Available', 'woocommerce');
	}
	
	// 	Change Out of Stock Text
	    if ( ! $_product->is_in_stock() ) {
		$availability['availability'] = __('Sold Out', 'woocommerce');
	}
	return $availability;
}

// Customise Health Text on Checkout
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
  unset($fields['billing']['billing_company']);

  $fields['order']['order_comments']['required'] = true;
  $fields['order']['order_comments']['label'] = 'Injuries / Pre-existing Health Conditions';
  $fields['order']['order_comments']['placeholder'] = 'Please list any injuries and health conditions (or "None").';

  return $fields;
}

// Add Field to Checkout Page
add_action( 'woocommerce_after_order_notes', 'mailing_list_checkout_field' );
function mailing_list_checkout_field( $checkout ) {
  echo '<div id="add_to_mailing_list"><h2>' . __('Mailing List') . '</h2>';
  woocommerce_form_field( 'add_to_mailing_list', array(
      'type'          => 'select',
      'class'         => array('form-row-wide'),
      'label'         => __('Would you like to be added to the Kirsty Innes Yoga mailing list?'),
      'options'       => array(
        'yes' => 'Yes please',
        'no'  => 'No thanks'
      )
      ), $checkout->get_value( 'add_to_mailing_list' ));

  echo '</div>';
}

// Add field to Order Metadata
add_action( 'woocommerce_checkout_update_order_meta', 'mailing_list_checkout_field_update_order_meta' );
function mailing_list_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['add_to_mailing_list'] ) ) {
        update_post_meta( $order_id, 'Add to Mailing List', sanitize_text_field( $_POST['add_to_mailing_list'] ) );
    }
}

// Add field to Admin backend
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'mailing_list_custom_checkout_field_display_admin_order_meta', 10, 1 );
function mailing_list_custom_checkout_field_display_admin_order_meta($order){
    echo '<p><strong>'.__('Add to Mailing List').':</strong> ' . get_post_meta( $order->get_id(), 'Add to Mailing List', true ) . '</p>';
}

// Add field to Email
add_filter('woocommerce_email_order_meta_keys', 'mailing_list_custom_order_meta_keys');
function mailing_list_custom_order_meta_keys( $keys ) {
     $keys[] = 'Add to Mailing List'; // This will look for a custom field called 'Tracking Code' and add it to emails
     return $keys;
}

function wc_donna_farhi_category_is_in_the_cart() {
	// Add your special category slugs here
	$categories = array( 'donna-farhi' );

	// Products currently in the cart
	$cart_ids = array();

	// Categories currently in the cart
	$cart_categories = array();

	// Find each product in the cart and add it to the $cart_ids array
	foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
		$cart_product = $values['data'];
		$cart_ids[]   = $cart_product->id;
	}

	// Connect the products in the cart w/ their categories
	foreach( $cart_ids as $id ) {
		$products_categories = get_the_terms( $id, 'product_cat' );

		// Loop through each product category and add it to our $cart_categories array
		foreach ( $products_categories as $products_category ) {
			$cart_categories[] = $products_category->slug;
		}
	}

	// If one of the special categories are in the cart, return true.
	if ( ! empty( array_intersect( $categories, $cart_categories ) ) ) {
		return true;
	} else {
		return false;
	}
}

function wc_donna_farhi_category_is_in_the_order( $order ) {
	// Add your special category slugs here
	$categories = array( 'donna-farhi' );

	// Products currently in the cart
	$cart_ids = array();

	// Categories currently in the cart
	$cart_categories = array();

	foreach( $order->get_items() as $item_id => $item_product ){
		$cart_ids[]   = $item_product->get_product_id();
	}

	// Connect the products in the cart w/ their categories
	foreach( $cart_ids as $id ) {
		$products_categories = get_the_terms( $id, 'product_cat' );

		// Loop through each product category and add it to our $cart_categories array
		foreach ( $products_categories as $products_category ) {
			$cart_categories[] = $products_category->slug;
		}
	}

	// If one of the special categories are in the cart, return true.
	if ( ! empty( array_intersect( $categories, $cart_categories ) ) ) {
		return true;
	} else {
		return false;
	}
}

function sv_wc_add_order_meta_box_action( $actions ) {
    global $theorder;

    // bail if the order has been paid for or this action has been run
    if ( ! wc_donna_farhi_category_is_in_the_order( $theorder ) ) {
        return $actions;
    }

    // add "mark printed" custom action
    $actions['wc_custom_order_action'] = __( 'Resend confirmation to customer', 'my-textdomain' );
    return $actions;
}
add_action( 'woocommerce_order_actions', 'sv_wc_add_order_meta_box_action' );

/**
 * Add an order note when custom action is clicked
 * Add a flag on the order to show it's been run
 *
 * @param \WC_Order $order
 */
function sv_wc_process_order_meta_box_action( $order ) {    
	WC()->mailer()->emails['WC_Confirmation_Email']->trigger( $order->get_id(), $order );
}
add_action( 'woocommerce_order_action_wc_custom_order_action', 'sv_wc_process_order_meta_box_action' );

?>