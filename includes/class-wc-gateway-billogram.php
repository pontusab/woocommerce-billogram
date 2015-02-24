<?php if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * WooCommerce Billogram Gateway.
 *
 * @class   WC_Billogram_Gateway
 * @extends WC_Payment_Gateway
 * @version 1.0
 * @package WooCommerce Billogram Gateway/Includes
 * @author  Pontus Abrahamsson
*/

use Billogram\Api;

class WC_Gateway_Billogram extends WC_Payment_Gateway {


	/**
	 * Billogram Api
	 *
	 * @access public
	 * @var    object
	*/
	public $billogram_api;


	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	*/
	public function __construct() 
	{
		$this->id                 = 'billogram';
		$this->method_title       = __( 'Billogram', WC_Billogram::$textdomain );
		$this->method_description = __( 'Have your customers pay with cash (or by other means) upon delivery.', WC_Billogram::$textdomain );
		$this->has_fields         = false;

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		// Get settings
		$this->liveurl            = 'https://billogram.com/api/v2';
		$this->testurl            = 'https://sandbox.billogram.com/api/v2';

		$this->supports           = [
			'products',
		];

		// Hooks.
		if( is_admin() ) 
		{
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ &$this, 'process_admin_options' ] );
			add_action( 'woocommerce_admin_order_data_after_billing_address', [ &$this, 'add_org_no_to_order_page' ], 10, 1 );
		}

		add_action( 'woocommerce_thankyou_' . $this->id, [ &$this, 'thankyou_page' ] );
		
		$this->billogram_api = new Billogram\Api( 
	        $this->api_username, 
	        $this->api_password, 
	        'WooCommerce', 
	        $this->testmode == 'yes' ? $this->testurl : $this->liveurl
	    );
	}


	/**
	 * Check if this gateway can be enabled on checkout.
	 *
	 * @access public
	*/
	public function is_available() 
	{
		if( $this->enabled == 'yes' ) 
		{
			return true;
		}
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * The standard gateway options have already been applied.
	 * Change the fields to match what the payment gateway your building requires.
	 *
	 * @access public
	*/
	public function init_form_fields() 
	{
		$this->form_fields = include WC_Billogram::plugin_path( '/views/settings-fields.php' );
	}


	/**
	 * Init the based settings array and our own properties.
	 *
	 * @access public
	*/
	public function init_settings() 
	{
		// Load the settings.
		parent::init_settings();

		// Get setting values.
		$this->enabled         = $this->get_option( 'enabled' );
		$this->debug           = $this->get_option( 'debug' );
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->api_username    = $this->get_option( 'api_username' );
		$this->api_password    = $this->get_option( 'api_password' );
		$this->testmode		   = $this->get_option( 'testmode' );
		$this->due_days 	   = $this->get_option( 'due_days' );
		$this->send_invoice    = $this->get_option( 'send_invoice' );
		$this->delivery_method = $this->get_option( 'delivery_method' );
		$this->invoice_fee	   = $this->get_option( 'invoice_fee' );
	}


	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	*/
	public function get_icon() 
	{
		$icon = WC_Billogram::plugin_url( '/assets/images/billogram.png' );
		
		$icon_html = '<img src="'. esc_attr( $icon ) .'" alt="'. $this->method_title .'" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}


	/**
	 * Add Org-no-field to the payment gateway field.
	 *
	 * @access public
	 * @return void
	*/
	public function payment_fields() 
	{
		include WC_Billogram::plugin_path( '/views/org-no-form.php' );
	}


	/**
	 * Validate org no.
	 *
	 * @access public
	 * @return bool or string
	*/
	public function validate_org_no( $data ) 
	{
		$org_no = isset( $data['org_no'] ) ? sanitize_text_field( $data['org_no'] ) : false;

		if ($org_no) {
			$org_no = preg_replace( '/[^0-9]/', '', $org_no );
			
			if (strlen( $org_no ) > 10) {
				// Remove 19
				$org_no = substr( $org_no, 2 );
			}

			if (strlen( $org_no ) == 10 ) {
				return implode( '-', str_split( $org_no, 6 ) );
			} else {
				return false;
			}
		}
	}


	/**
	 * Add org no field to order admin page.
	 *
	 * @access public
	 * @return string
	*/
	public function add_org_no_to_order_page( $order )
	{
		 echo '<p><strong>'. __( 'Personnr/Orgnr', WC_Billogram::$textdomain ) .':</strong> ' . get_post_meta( $order->id, '_org_no', true ) . '</p>';
	}


	/**
	 * Search customer from Billogram 
	 *
	 * @access public
	 * @param  array $data
	 * @return array
	*/
	public function create_and_get_customer( $data, $order_id )
	{

		$org_no = $this->validate_org_no( $data );
		
		// Search for customer in Billogram on Org No
		try {
			$customer = current( $this->billogram_api->customers->query()->pageSize(1)->filterSearch('org_no', $org_no)->getPage(1) );
		} catch (Exception $e) {
			exit;
		}

		$company_sv = isset( $data['billing_country'] ) && $data['billing_country'] == 'SE' ? true : false;

		// If we have company name we assume it's a company order
		if( isset( $data['billing_company'] ) ) {
			// Use the company name in billogram
			$name = $data['billing_company'];

			// Swedish customer
			if( $company_sv ) {
				$company_type = 'business';
			} else {
				$company_type = 'foreign business';
			}	
		} else {
			// User personal name in billogram
			$name = $data['billing_first_name'] . ' ' . $data['billing_last_name'];

			// Swedish customer
			if( $company_sv ) {
				$company_type = 'individual';
			} else {
				$company_type = 'foreign individual';
			}	
		}

		if( !$customer ) {
			$customer_data = [
			    'name' 				 =>  $name,
			    'address' 			 => [
			        'street_address' => $data['billing_address_1'],
			        'zipcode' 	 	 => $data['billing_postcode'],
			        'city' 	 		 => $data['billing_city'],
			        'country' 	 	 => $data['shipping_country'],
			    ],
			    'contact' 			 => [
			    	'name'			 => $name,
			        'email' 		 => $data['billing_email'],
			        'phone'			 => $data['billing_phone'],
			    ],
			    'org_no'		     => $org_no,
			    'company_type'		 => $company_type,
			    'notes'				 => __( 'Customer from Webshop', WC_Billogram::$textdomain )
			];


			if( isset( $data['ship_to_different_address'] ) ) {
				if( isset( $data['shipping_company'] ) ) {
					$name = $data['shipping_company'];
				} else {
					$name = $data['shipping_first_name'] . ' ' . $data['shipping_last_name'];
				}

				$customer_data['delivery_address'] = [
					'name' 		     => $name,
			        'street_address' => $data['shipping_address_1'],
			        'zipcode' 	 	 => $data['shipping_postcode'],
			        'city' 	 		 => $data['shipping_city'],
			        'country' 	 	 => $data['shipping_country'],
				];
			}

			// Create customer in Billogram
			$customer = $this->billogram_api->customers->create( $customer_data );
		}

		return $customer;
	}


	/**
	 * Create and send invoice
	 *
	 * @access public
	 * @param  array $order
	 * @param  string $customer_no
	 * @return void
	*/
	public function create_and_send_invoice( $order, $customer_no )
	{
		// Generate sign_key based on order_id
		$sign_key = $order->id;
		$callback = WC_Billogram::callback_url();

		// Hold Billogram items
		$items = [];

		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$product     = $cart_item['data'];
			$title 		 = $product->get_title();
			$quantity	 = $cart_item['quantity'];
			$price 		 = wc_format_decimal( $product->get_price_excluding_tax(), true, true );
			$rate 	 	 = $this->get_product_tax_rate( $product );

			$items[] = [
				'title'		   => $title,
                'price' 	   => $price,
                'vat'		   => $rate,
                'count'		   => $quantity,
                'unit'		   => 'unit'
			];
		}


		// Add Shipping
		if ($order->get_total_shipping() > 0) {
			$items[] = [
				'title'		   => __( 'Shipping and Handling', WC_Billogram::$textdomain ) .' - '. $order->get_shipping_method(),
	            'price' 	   => ( $order->get_total_shipping() + $order->get_shipping_tax() ),
	            'count'		   => 1,
	            'unit'		   => 'unit'
			];
		}


		// Add Coupons
		if (count( WC()->cart->applied_coupons ) > 0 ) {
			foreach (WC()->cart->applied_coupons as $code ) {
				
				// Add coupon as item in Billogram 
				// With negative value
				$items[] = [
					'title'	   => __( 'Coupon:', WC_Billogram::$textdomain ) . $code,
					'price'    => (int) - WC()->cart->coupon_discount_amounts[ $code ],
		            'count'    => 1,
		            'unit' 	   => 'unit'
				];
			}
		}


		// Add fees
		if ( count(WC()->cart->fees) > 0) {
			foreach (WC()->cart->fees as $fee) {
				// Add fee as item in Billogram 
				$items[] = [
					'title'	   => $fee->name,
					'price'    => $fee->amount,
		            'count'    => 1,
		            'unit' 	   => 'unit'
				];
			}
		}


		// Create invoice 
		try {
			$invoice = $this->billogram_api->billogram->create([
				'currency' 		   => 'SEK',
				'due_days' 		   => $this->due_days,
				'customer' 		   => [
					'customer_no'  => $customer_no,
				],
				'items' 		   => $items,
				'callbacks' 	   => [
					'sign_key'     => $sign_key,
					'url' 		   => $callback,
					'custom' 	   => $order->id
				],
			]);
		} catch (Exception $e) {
			
		}


		// Send invoice based on saved delivery method
		// And if send invoice is set to true in settings
		if ($this->send_invoice == 'yes') {
			$invoice = $invoice->send($this->delivery_method());
		}


		// Save invoice id
		if ($invoice->id) {
			update_post_meta($order->id, '_invoice_no', $invoice->id);
		}
	}


	/**
	 * Get tax rate on product item.
	 *
	 * @access public
	 * @param  object $product
	 * @return string
	*/
	public function get_product_tax_rate( $product )
	{
		$tax  = new WC_Tax();
		$rate = current( $tax->get_rates( $product->get_tax_class() ) )['rate'];
		
		return wc_format_decimal( $rate, true, true );
	}


	/**
	 * Setup delivery method for Billogram.
	 *
	 * @access public
	 * @return string
	*/
	public function delivery_method()
	{
		$saved_method = $this->delivery_method;

		$method = 'Email'; // Default

		switch ($saved_method) {
			case '1':
				$method = 'Letter';
				break;
			case '2':
				$method = 'Email+Letter';
				break;
		}

		return $method;
	}


	/**
	* Output for the order received page.
   	*
   	* @access public
  	*/
	public function thankyou_page($order_id) 
	{
		if (!empty( $this->instructions)) {
			echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
		}
	}


	/**
	 * Process the payment and redirect.
	 *
	 * @access public
	 * @param  int $order_id
	 * @return array
	*/
	public function process_payment($order_id) 
	{
		$order = new WC_Order( $order_id );
		$data  = isset( $_POST ) ? $_POST : false;

		$org_no = $this->validate_org_no( $data );

		// Check if valid number
		if( $org_no ) {
			update_post_meta( $order_id, '_org_no', $org_no );
		} else {
			wc_add_notice( __( 'Not a valid social security number.', WC_Billogram::$textdomain ), 'error' );
			return;
		}

		// Setup new customer in Billogram or return current
		$customer = $this->create_and_get_customer( $data, $order_id );

		// Create and send invoice to customer in Billogram
		$this->create_and_send_invoice( $order, $customer->customer_no );

		// Reduce stock levels.
		// Remove cart, leave as is if debugging.
		if ($this->debug == 'no') {
			$order->reduce_order_stock();
			WC()->cart->empty_cart();
		}

		// Return to reciept page redirect.
		return [
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		];
	}
}