<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEG_Paystack_Onsite
 *
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
 
 
class EEG_Paystack_Onsite extends EE_Onsite_Gateway{
	
	/**
	 * All the currencies supported by this gateway. Add any others you like,
	 * as contained in the esp_currency table
	 * @var array
	 */
	protected $_currencies_supported = array('NGN');
	
	/**
	 *
	 * @param EEI_Payment $payment
	 * @param array $billing_info
	 * @return \EE_Payment|\EEI_Payment
	 */
	public function do_direct_payment($payment, $billing_info = null) {
		
		$this->log( $billing_info, $payment );
		$secret_key = $this->_paystack_secret_key;
		$amount =  str_replace( array(',', '.'), '', number_format( $payment->amount(), 2) );
		$reference = '';
		
		//Verify paystack refernce code
		try {
			//Get Paystack reference code
			if(!isset($_POST['EEA_PaystackReference']) && empty($_POST['EEA_PaystackReference']) ){
				throw new Exception ('Transcation refernece not provided');
			}
			$reference = $_POST['EEA_PaystackReference'];
			
			//Generate headers
			$chHeader = array();
			$chHeader[] = 'Content-Type: application/json';
			$chHeader[] = 'Authorization: Bearer ' . $secret_key;
			
			//Verify Paystack transcation
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/'. $reference);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $chHeader);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSLVERSION, 6);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);//CAUTION!: disable verify certificate. for dev. purposes. turn on when LIVE
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);//CAUTION!: disable verify certificate. for dev. purposes. turn on when LIVE
			$resp = curl_exec($ch);
			
			if (curl_errno($ch)) {// curl ended with an error
				$cerr = curl_error($ch);
				curl_close($ch);
				throw new Exception("Sending request error: '" . $cerr . "'.");
			}
			
			// Decode JSON from Paystack:
			$response = json_decode($resp);
			curl_close($ch);
			
			if (json_last_error() !== JSON_ERROR_NONE || !$response->status) {
				throw new Exception("Response request error: '" . ((json_last_error() === JSON_ERROR_NONE) ? $response->message : $resp) . "'.");
			}
			
			
			
		} catch ( Exception $exception ) {
			$payment->set_status( $this->_pay_model->failed_status() );
			$payment->set_gateway_response( $exception->getMessage() );
			$payment->set_txn_id_chq_nmbr( $reference );
			$this->log( array('Paystack Error: ' => $exception), $payment );
			return $payment;
		}
		
		/*//Test: Write result to file
		$myfilename = EE_PAYSTACK_PATH . 'payment_methods/Paystack_Onsite/' .$reference. '_'. time(). '.txt';
		$myfile = fopen($myfilename, "w") or die("Unable to open file!");
		$txt = json_encode($response);
		fwrite($myfile, $txt);
		fclose($myfile);
		//End Test.*/

		if ( 'success' == $response->data->status ) {
			
			//payment successful
			$this->log( array( 'Paystack successful: ' => json_encode($response, true) ), $payment );
			$payment->set_gateway_response( $response->data->status );
			$payment->set_txn_id_chq_nmbr( $response->data->reference );
			$payment->set_details( json_encode($response, true) );
			$payment->set_amount( floatval( $response->data->amount / 100 ) );

				$auth_details = $response->data->authorization;
				$code = "Authorization Code: ". $auth_details->authorization_code. "\n";
				$code .= "Card: **** **** **** ". $auth_details->last4. " / ". $auth_details->card_type. "\n";
				$code .= "Expiry: ". $auth_details->exp_month. "/". $auth_details->exp_year. "\n";
				$code .= "Issuer: ". $auth_details->bank. "\n";
				$code .= "Channel: ". $auth_details->channel. "\n";

			$payment->set_extra_accntng( $code );
			$payment->set_status( $this->_pay_model->approved_status() );
	
			return $payment;
			
		}else{
		 
			$this->log( array('Payment declined: '. $response->message), $payment );
			$payment->set_gateway_response( 'Payment declined: '. $response->message);
			$payment->set_txn_id_chq_nmbr( $response->data->reference );
			$payment->set_details( $response );
			
			$payment->set_extra_accntng(json_encode( $response->data->authorization));
			$payment->set_status( $this->_pay_model->failed_status() );
			
			return $payment;
		
		}
		
	}
	
}

// End of file EEG_Paystack_Onsite.php