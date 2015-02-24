<?php

/**
 * Settings fields in settings.
 *
 * @return array
*/

return [
	'enabled' => [
		'title'       => __( 'Enable/Disable', WC_Billogram::$textdomain ),
		'label'       => __( 'Enable Billogram', WC_Billogram::$textdomain ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no'
	],
	'title' => [
		'title'       => __( 'Title', WC_Billogram::$textdomain ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the customer sees during checkout.', WC_Billogram::$textdomain ),
		'default'     => __( 'Invoice - 14 days', WC_Billogram::$textdomain ),
		'desc_tip'    => true
	],
	'description' => [
		'title'       => __( 'Description', WC_Billogram::$textdomain ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', WC_Billogram::$textdomain ),
		'default'     => __( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', WC_Billogram::$textdomain )
	],
	'delivery_method' => [
		'title'             => __( 'Delivery method', WC_Billogram::$textdomain ),
		'type'              => 'select',
		'class'             => 'chosen_select',
		'css'               => 'width: 350px;',
		'default'           => 'Email',
		'description'       => __( '', WC_Billogram::$textdomain ),
		'options'           => [
			__( 'Email', WC_Billogram::$textdomain ),
			__( 'Letter', WC_Billogram::$textdomain ),
			__( 'Email and Letter', WC_Billogram::$textdomain ),
		],
		'desc_tip'          => true,
		'custom_attributes' => [
			'data-placeholder' => __( 'Select shipping methods', WC_Billogram::$textdomain )
		],
	],
	'due_days' => [
		'title'       => __( 'Due days of the invoice', WC_Billogram::$textdomain ),
		'type'        => 'text',
		'default'     => '14',
		'css'         => 'width: 50px;',
	],
	'invoice_fee' => [
		'title'       => __( 'Invoice fee', WC_Billogram::$textdomain ),
		'type'        => 'text',
		'default'     => '0',
		'description' => __( 'Add amount if you want the customer to pay the invoice fee. In SEK.', WC_Billogram::$textdomain ),
		'css'         => 'width: 50px;',
	],
	'send_invoice' => [
		'title'       => __( 'Send invoice', WC_Billogram::$textdomain ),
		'type'        => 'checkbox',
		'label'       => __( 'Send invoice on checkout', WC_Billogram::$textdomain ),
		'default'     => 'yes',
		'description' => __( 'Automatic send invoice on checkout. If not you need to manually send invoice from Billogram.', WC_Billogram::$textdomain ),

	],
	'testmode' => [
		'title'       => __( 'Billogram sandbox', WC_Billogram::$textdomain ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Billogram sandbox', WC_Billogram::$textdomain ),
		'default'     => 'no',
		'description' => sprintf( __( 'Billogram sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', WC_Billogram::$textdomain ), 'https://sandbox.billogtam.se/' ),
	],
	'debug' => [
		'title'       => __( 'Debug', WC_Billogram::$textdomain ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable debug mode', WC_Billogram::$textdomain ),
		'default'     => 'no',
		'description' => __( 'When enabled the stock will not be reduced.', WC_Billogram::$textdomain )
	],
	'api_details' => [
		'title'       => __( 'API Credentials', WC_Billogram::$textdomain ),
		'type'        => 'title',
		'description' => sprintf( __( 'Enter your PayPal API credentials to process refunds via PayPal. Learn how to access your PayPal API Credentials %shere%s.', WC_Billogram::$textdomain ), '<a href="https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/#creating-classic-api-credentials">', '</a>' ),
	],
	'api_username' => [
		'title'       => __( 'API Username', WC_Billogram::$textdomain ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', WC_Billogram::$textdomain ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', WC_Billogram::$textdomain )
	],
	'api_password' => [
		'title'       => __( 'API Password', WC_Billogram::$textdomain ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', WC_Billogram::$textdomain ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', WC_Billogram::$textdomain )
	],
];