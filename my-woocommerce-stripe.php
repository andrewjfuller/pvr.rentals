<?php
/*
Plugin Name: My WooCommerce Stripe
Description: Adds Stripe checkout to WooCommerce
Version: 1.0.0
*/

// Initialize the plugin
add_action( 'plugins_loaded', 'my_woocommerce_stripe_init' );
function my_woocommerce_stripe_init() {
  if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  }

  // Define the Stripe gateway class
  class WC_Gateway_My_Stripe extends WC_Payment_Gateway {

    public function __construct() {
      $this->id = 'my_stripe';
      $this->method_title = 'Stripe';
      $this->method_description = 'Pay with Stripe';
      $this->supports = array( 'products' );

      $this->init_form_fields();
      $this->init_settings();

      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => 'Enable/Disable',
          'type' => 'checkbox',
          'label' => 'Enable Stripe payments',
          'default' => 'yes'
        ),
        'testmode' => array(
          'title' => 'Test mode',
          'type' => 'checkbox',
          'label' => 'Enable test mode',
          'default' => 'yes'
        ),
        'secret_key' => array(
          'title' => 'Secret key',
          'type' => 'password',
          'description' => 'Enter your Stripe secret key here.'
        ),
        'public_key' => array(
          'title' => 'Public key',
          'type' => 'password',
          'description' => 'Enter your Stripe public key here.'
        )
      );
    }

    public function process_payment( $order_id ) {
      global $woocommerce;

      $order = new WC_Order( $order_id );

      // Set the API keys based on whether test mode is enabled
      if ( $this->get_option( 'testmode' ) == 'yes' ) {
        $secret_key = $this->get_option( 'test_secret_key' );
        $public_key = $this->get_option( 'test_public_key' );
      } else {
        $secret_key = $this->get_option( 'live_secret_key' );
        $public_key = $this->get_option( 'live_public_key' );
      }

      \Stripe\Stripe::setApiKey( $secret_key );

      // Create the Stripe payment intent
      $intent = \Stripe\PaymentIntent::create([
        'amount' => $order->get_total() * 100,
        'currency' => $order->get_currency(),
      ]);

      // Set the payment intent ID on the order
      $order->update_meta_data( '_stripe_intent_id', $intent->id );
      $order->save();

      // Build the payment form
      $args = array(
        'intent_secret' => $intent->client_secret,
        'public_key' => $public_key,
        'amount' => $

// Add the payment form to the checkout page
return array(
'result' => 'success',
'redirect' => $this->get_return_url( $order )
);
}

public function receipt_page( $order_id ) {
  $order = wc_get_order( $order_id );

  // Get the payment intent ID from the order
  $intent_id = $order->get_meta( '_stripe_intent_id' );

  // Load the Stripe checkout script
  wp_enqueue_script( 'stripe-checkout', 'https://checkout.stripe.com/checkout.js', array( 'jquery' ) );

  // Add the payment form to the receipt page
  echo '<div id="stripe-checkout-form"></div>';
  echo '<script>
    var handler = StripeCheckout.configure({
      key: "' . esc_attr( $this->get_option( 'public_key' ) ) . '",
      locale: "auto",
      token: function(token) {
        jQuery("#stripe-token").val(token.id);
        jQuery("#place_order").trigger("click");
      }
    });
    jQuery("#stripe-checkout-form").click(function(e) {
      handler.open({
        name: "' . esc_attr( get_bloginfo( 'name' ) ) . '",
        amount: ' . esc_attr( $order->get_total() * 100 ) . ',
        currency: "' . esc_attr( $order->get_currency() ) . '",
        description: "Order #' . esc_attr( $order->get_id() ) . '",
        email: "' . esc_attr( $order->get_billing_email() ) . '",
        allowRememberMe: false,
        payment_intent: "' . esc_attr( $intent_id ) . '"
      });
      e.preventDefault();
    });
    jQuery(window).on("popstate", function() {
      handler.close();
    });
  </script>';
}

}

// Register the Stripe gateway with WooCommerce
function add_my_stripe_gateway( $methods ) {
$methods[] = 'WC_Gateway_My_Stripe';
return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_my_stripe_gateway' );

// Load the Stripe PHP library
require_once( plugin_dir_path( FILE ) . 'stripe-php/init.php' );
}
