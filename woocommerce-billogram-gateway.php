<?php if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/*
 * Plugin Name:       WooCommerce Billogram
 * Plugin URI:        https://github.com/pontusab/woocommerce-billogram
 * Description:       A payment gateway for Billogram invoice service.
 * Version:           1.0
 * Author:            Pontus Abrahamsson
 * Author URI:        http://www.pontusab.se
 * Text Domain:       woocommerce-billogram-gateway
 * Domain Path:       languages
 * Network:           false
 *
 * Copyright (C) 2015 Pontus Abrahamsson
 * @package  WooCommerce Billogram
 * @author   Pontus Abrahamsson
 * @category Core
*/

/**
 * WooCommerce Billogram main class.
 *
 * @class   WC_Billogram
 * @version 1.0
*/
final class WC_Billogram {

	/**
	 * Instance of this class.
	 *
	 * @access protected
	 * @access static
	 * @var object
	*/
	protected static $instance = null;


	/**
	 * Slug
	 *
	 * @access public
	 * @var    string
	*/
	public $gateway_slug = 'billogram';


	/**
	 * Text domain
	 *
	 * @access public
	 * @var    string
	*/
	public static $textdomain = 'woocommerce-billogram';


	/**
	 * The Gateway Name.
	 *
	 * @access public
	 * @var    string
	*/
	public $name = 'Billogram';


	/**
	 * Gateway version.
	 *
	 * @access public
	 * @var    string
	*/
	public $version = '1.0';


	/**
	 * The Gateway documentation URL.
	 *
	 * @access public
	 * @var    string
	*/
	public $doc_url = 'https://github.com/pontusab/woocommerce-billogram';


	/**
	 * Gateway support for currencies.
	 *
	 * @access public
	 * @var    string
	*/
	public $currencies = ['SEK'];


	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	*/
	public static function get_instance() 
	{
		if( self::$instance == null ) 
		{
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	*/
	public function __clone() 
	{
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', self::$textdomain ), $this->version );
	}


	/**
	 * Disable unserializing of the class
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	*/
	public function __wakeup() 
	{
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', self::$textdomain ), $this->version );
	}


	/**
	 * Initialize the plugin public actions.
	 *
	 * @access private
	*/
	private function __construct() 
	{
		// Hooks.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'action_links' ] );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );


		// Check we have the minimum version of WooCommerce required before loading the gateway.
		if( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) 
		{
			if( class_exists( 'WC_Payment_Gateway' ) ) 
			{
				spl_autoload_register([ &$this, 'billogram_sdk']);

				$this->includes();

				add_filter( 'woocommerce_payment_gateways', [ &$this, 'add_gateway' ] );
			}
		} 
		else 
		{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', [ &$this, 'upgrade_notice' ] );
			
			return false;
		}

		add_action( 'wp_enqueue_scripts', [ &$this, 'add_script' ] );
		add_action( 'woocommerce_cart_calculate_fees', [ &$this, 'billogram_fee' ] );
	}


	/**
	 * Plugin action links.
	 *
	 * @access public
	 * @param  mixed $links
	 * @return void
	*/
	public function action_links( $links ) 
	{
		if( current_user_can( 'manage_woocommerce' ) ) 
		{
			$plugin_links = [
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_' . $this->gateway_slug ) . '">' . __( 'Payment Settings', self::$textdomain ) . '</a>',
			];

			$links = array_merge( $plugin_links, $links );
		}

		return $links;
	}


	/**
	 * Plugin row meta links
	 *
	 * @access public
	 * @param  array $input already defined meta links
	 * @param  string $file plugin file path and name being processed
	 * @return array $input
	*/
	public function plugin_row_meta( $input, $file ) 
	{
		if( plugin_basename( __FILE__ ) !== $file ) 
		{
			return $input;
		}

		$links = [
			'<a href="' . esc_url( $this->doc_url ) . '">' . __( 'Documentation', self::$textdomain ) . '</a>',
		];

		$input = array_merge( $input, $links );

		return $input;
	}


	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any
	 * following ones if the same translation is present.
	 *
	 * @access public
	 * @return void
	*/
	public function load_plugin_textdomain() 
	{
		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/';
		$lang_dir = apply_filters( 'woocommerce_'. $this->gateway_slug .'_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale',  get_locale(), self::$textdomain );
		$mofile = sprintf( '%1$s-%2$s.mo', self::$textdomain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR .'/'. self::$textdomain .'/'. $mofile;

		if( file_exists( $mofile_global ) ) 
		{
			load_textdomain( self::$textdomain, $mofile_global );
		} 
		else if( file_exists( $mofile_local ) ) 
		{
			load_textdomain( self::$textdomain, $mofile_local );
		} 
		else 
		{
			// Load the default language files
			load_plugin_textdomain( self::$textdomain, false, $lang_dir );
		}
	}


	/**
	 * Include files.
	 *
	 * @access private
	 * @return void
	*/
	private function includes() 
	{
		include_once( 'includes/class-wc-gateway-billogram.php' ); // Payment Gateway.
		include_once( 'includes/class-wc-events-billogram.php' );  // Billogram Events.
	}


	/**
	 * Include Billogram library.
	 *
	 * @access private
	 * @return void
	*/
	private function billogram_sdk( $class )
    {
        if( stripos( $class, 'billogram' ) !== false )
        {
            require str_replace( '\\', '/', dirname( __FILE__ ) .'/vendor/'. $class .'.php' );
        }
    }


	/**
	 * Add script to update checkout on payment method change.
	 *
	 * @access public
	*/
	public function add_script()
	{
		$gateway = new WC_Gateway_Billogram;

		// Only include script on checkout  
		if( $gateway->invoice_fee && is_checkout() )
		{
			wp_enqueue_script(
				'update-payment-method',
				WC_Billogram::plugin_url( '/assets/js/update-payment-method.js' ),
				[ 'jquery' ]
			);
		}
	}


	/**
	 * Add invoice fee if Billogram is used and fee is mor than 0
	 *
	 * @access public
	 * @return string
	*/
	public function billogram_fee( $cart )
	{
		if( is_admin() && !defined( 'DOING_AJAX' ) )
		{
			return;
		}

		// Get the gateway class
		$gateway = new WC_Gateway_Billogram;


		// If invoice fee enabled
		if( $gateway->invoice_fee > 0 )
		{
			// Get all payment methods
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
				
			// Get current selected menthod
			if( WC()->session->chosen_payment_method )
			{
				$current_gateway = $available_gateways[ WC()->session->chosen_payment_method ];

				// If current menthod is Billogram
				if( $current_gateway && $current_gateway->id == $this->gateway_slug )
				{
					$cart->add_fee( __( 'Invoice fee', self::$textdomain ), $gateway->invoice_fee );
				}	
			}
		}
	}


	/**
	 * Add the gateway.
	 *
	 * @access public
	 * @param  array $methods WooCommerce payment methods.
	 * @return array WooCommerce Billogram gateway.
	*/
	public function add_gateway( $methods ) 
	{
		// This checks if the gateway is supported for your currency.
    	if( in_array( get_woocommerce_currency(), $this->currencies ) ) 
      	{
			$methods[] = 'WC_Gateway_' . str_replace( ' ', '_', $this->name );
		}

		return $methods;
	}


	/**
	 * WooCommerce Billogram Gateway Upgrade Notice.
	 *
	 * @access public
	 * @return string
	*/
	public function upgrade_notice() 
	{
		echo '<div class="updated woocommerce-message wc-connect"><p>' . sprintf( __( 'WooCommerce %s depends on version 2.2 and up of WooCommerce for this gateway to work! Please upgrade before activating.', self::$textdomain ), $this->name ) . '</p></div>';
	}


	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	*/
	public static function plugin_url( $path = '/' ) 
	{
		return untrailingslashit( plugins_url( $path, __FILE__ ) );
	}


	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	*/
	public static function plugin_path( $file = false ) 
	{
		return untrailingslashit( plugin_dir_path( __FILE__ ) ) . $file;
	}


	/**
	 * Get the callback url.
	 *
	 * @access public
	 * @return string
	*/
	public static function callback_url() 
	{
		return str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Events_Billogram', home_url( '/' ) ) );
	}
} 

// Load WC_Billogram on plugins loaded hook
add_action( 'plugins_loaded', [ 'WC_Billogram', 'get_instance' ], 0 );


/**
 * Returns the main instance of WC_Billogram to prevent the need to use globals.
 *
 * @return WooCommerce Billogram Gateway
*/
function WC_Billogram() 
{
	return WC_Billogram::get_instance();
}