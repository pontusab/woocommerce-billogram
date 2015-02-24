<?php if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * WooCommerce Billogram Events.
 *
 * @class   WC_Events_Billogram
 * @version 1.0
 * @package WooCommerce Billogram Events
 * @author  Pontus Abrahamsson
*/

class WC_Events_Billogram {
	
	/**
	 * Constructor for the events.
	 *
	 * @access public
	 * @return void
	*/
	public function __construct()
	{
		$this->callback_handler();
	}


	/**
	 * Process Billogram callbacks.
	 *
	 * @since  1.0
	 * @access private
	 * @return void
	*/
	private function callback_handler()
	{
		$response  = json_decode( file_get_contents('php://input') );
		$order_id  = $response->custom;
		$checker   = md5( $response->callback_id . $order_id );

		// Check signature on response
		if( $checker == $response->signature )
		{
			$order  = new WC_Order( $order_id );
			$status = 'processing'; // Default status 

			switch( $response->event->type ) 
			{
				case 'BillogramCreated':
					$message = __( 'Invoice created in Billogram.', WC_Billogram::$textdomain );
					break;
				case 'BillogramSent':
					$message = __( 'Invoice sent from Billogram.', WC_Billogram::$textdomain );
					break;
				case 'DeliveryAccepted':
					$message = __( 'Invoice received and opened in email.', WC_Billogram::$textdomain );
					break;
				case 'EmailNotAccepted':
				case 'DeliveryFailed':
					$message = __( 'Invoice could not be delivered via email.', WC_Billogram::$textdomain );
					break;
				case 'LetterSent':
					$message = __( 'Letter sent to customer.', WC_Billogram::$textdomain );
					break;
				case 'Resent':
					$message = __( 'A reminder has been sent.', WC_Billogram::$textdomain );
					break;
				case 'Overdue':
					$message = __( 'Invoice has past its due date.', WC_Billogram::$textdomain );
					break;

				// Mark as complete
				case 'Payment':
					if( !$response->event->data->remaining_sum )
					{
						$message = __( 'Invoice paid.', WC_Billogram::$textdomain );
						$status  = 'completed';
					}
					else
					{
						$message = __( 'Invoice paid but missing.', WC_Billogram::$textdomain );
					}
					break;
				case 'Credit':
					if( !$response->event->data->remaining_sum )
					{
						$message = __( 'Invoice paid.', WC_Billogram::$textdomain );
						$status  = 'completed';
					}
					else
					{
						$message = __( 'Invoice credited.', WC_Billogram::$textdomain );
						$status  = 'cancelled';
					}
					break;
			}

			// Add notes to order
			$order->add_order_note( $message );

			// Add status to order
			$order->update_status( $status );
		}

		wp_die( 'Billogram Request Failure.', 'Billogram Request', [ 'response' => 200 ] );
	}
}