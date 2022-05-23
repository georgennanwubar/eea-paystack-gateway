<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_PMT_Onsite
 *
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_PMT_Paystack_Onsite extends EE_PMT_Base{

	/**
	 *
	 * @param EE_PayStack $pm_instance
	 * @return EE_PMT_Paystack_Onsite
	 */
	 
	protected $_template_path = NULL;
	 
	public function __construct($pm_instance = NULL) {
		
		$this->_pretty_name = __("Paystack", 'event_espresso');
		$this->_default_description = __( 'Click the "Pay Now" button to proceed with payment.', 'event_espresso' );
		$this->_template_path = dirname(__FILE__) . DS . 'templates' . DS;
		$this->_requires_https = FALSE;
		$this->_cache_billing_form = FALSE;
		$this->_default_button_url = EE_PAYSTACK_URL . 'payment_methods' . DS . 'Paystack_Onsite' . DS . 'lib' . DS . 'paystack-cc-logo.png';
		
		// Include Paystack API dependencies.

		require_once($this->file_folder().'EEG_Paystack_Onsite.gateway.php');
		$this->_gateway = new EEG_Paystack_Onsite();
		
		parent::__construct($pm_instance);
		// Scripts for generating Paystack token.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_paystack_payment_scripts' ));
	}

	/**
	 * Generate a new payment settings form.
	 *
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {

		return new EE_Payment_Method_Form( array(
			'extra_meta_inputs' => array(
				'paystack_public_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Paystack Public Key %s", "event_espresso"), $this->get_help_tab_link() ),
					'required' => true
				)),
				'paystack_secret_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Paystack Secret Key %s", "event_espresso"), $this->get_help_tab_link() ),
					'required' => true
				)),
				'paystack_saved_cards' => new EE_Yes_No_Input(
					array(
						'html_label_text'=> sprintf( __("Save cards? %s", 'event_espresso'),  $this->get_help_tab_link() ),
						'default' => false,
						'required' => true
					)
				)
			)
		));
	}
	
	/**
	 * Creates a billing form for this payment method type.
	 * @param \EE_Transaction $transaction
	 * @return \EE_Billing_Info_Form
	 */
	public function generate_new_billing_form( EE_Transaction $transaction = NULL, $extra_args = array() ) {
		EE_Registry::instance()->load_helper( 'Money' );
		$event_name = '';
		$email = '';
		if ( $transaction->primary_registration() instanceof EE_Registration ) {
			$event_name = $transaction->primary_registration()->event_name();
			if ( $transaction->primary_registration()->attendee() instanceof EE_Attendee ) {
				$email = $transaction->primary_registration()->attendee()->email();
			}
		}
		if ( isset( $extra_args['amount_owing' ] )) {
			$amount = $extra_args[ 'amount_owing' ] * 100;
		} else {
			// If this is a partial payment..
			$total = EEH_Money::convert_to_float_from_localized_money( $transaction->total() ) * 100;
			$paid = EEH_Money::convert_to_float_from_localized_money( $transaction->paid() ) * 100;
			$owning = $total - $paid;
			$amount = ( $owning > 0 ) ? $owning : $total;
		}
		//echo $transaction->ID().'_'.time();
		
		//print_r($transaction);
		
		return new EE_Billing_Info_Form(
			$this->_pm_instance,
			array(
				'name' => 'Paystack_Onsite_Billing_Form',
				'html_id'=> 'ee-Paystack-billing-form',
				'html_class'=> 'ee-billing-form',
				'subsections' => array(
					//$this->generate_billing_form_debug_content(),
					$this->paystack_embedded_form(),
					'ee_paystack_reference' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-paystack-reference',
							'html_name' => 'EEA_PaystackReference',
							'default' => ''
						)
					),
					'ee_paystack_transaction_email' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-paystack-transaction-email',
							'html_name' => 'eeTransactionEmail',
							'default' => $email,
							'validation_strategies' => array( new EE_Email_Validation_Strategy() )
						)
					),
					'ee_paystack_transaction_total' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-paystack-transaction-total',
							'html_name' => 'eeTransactionTotal',
							'default' => $amount,
							'validation_strategies' => array( new EE_Float_Validation_Strategy() )
						)
					),
					'ee_paystack_prod_description' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-paystack-prod-description',
							'html_name' => 'paystackProdDescription',
							'default' => apply_filters( 'FHEE__EE_PMT_Paystack_Onsite__generate_new_billing_form__description', $event_name, $transaction )
						)
					)
				)
			)
		);
	}

	/**
	 *  Use Paystack's Embedded form.
	 *
	 * @return EE_Form_Section_Proper
	 */
	public function paystack_embedded_form() {
		$template_args = array();
		return new EE_Form_Section_Proper(
			array(
				'layout_strategy' => new EE_Template_Layout(
					array(
						'layout_template_file' => $this->_template_path . 'paystack_embedded_form.template.php',
						'template_args' => $template_args
					)
				)
			)
		);
	}

	/**
	 * Adds the help tab
	 * @see EE_PMT_Base::help_tabs_config()
	 * @return array
	 */
	public function help_tabs_config(){
		return array(
			$this->get_help_tab_name() => array(
				'title' => __('Paystack Settings', 'event_espresso'),
				'filename' => 'paystack_onsite'
				),
		);
	}

	/**
	 *  Load all the scripts needed for the Paystack checkout.
	 *
	 * @return void
	 */
	public function enqueue_paystack_payment_scripts() {
		// Data needed in the JS.
		$trans_args = array(
			'key' => $this->_pm_instance->get_extra_meta( 'paystack_public_key', TRUE ),
			'pay_label' =>  sprintf( __( 'Pay %1$s Now', 'event_espresso' ), '{{amount}}' ),
			'accepted_message' => __( 'Payment Accepted. Please click "Proceed to Finalize Registration" if not forwarded automatically.', 'event_espresso' ),
			'no_PSCO_error' => __( 'It appears the Single Page Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso' ),
			'card_error_message' => __( 'Payment Error! Please refresh the page and try again or contact support.', 'event_espresso' ),
			'no_PaystackCheckout_error' => __( 'It appears the Paystack Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso' ),
			'payment_method_slug' => $this->_pm_instance->slug()
		);
		
		wp_enqueue_style( 'espresso_paystack_payment_css', EE_PAYSTACK_URL . 'css' . DS . 'espresso_paystack.css' );
		wp_enqueue_script( 'paystack_payment_js', 'https://js.paystack.co/v1/inline.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'espresso_paystack_payment_js', EE_PAYSTACK_URL . 'scripts' . DS . 'espresso_paystack.js', array( 'paystack_payment_js', 'single_page_checkout' ), EE_PAYSTACK_VERSION, TRUE );
		// Localize the script with our transaction data.
		wp_localize_script( 'espresso_paystack_payment_js', 'paystack_transaction_args', $trans_args);
	}
	/**
	 * Log Paystack TXN Error.
	 *
	 * @return void
	 */
	public static function log_paystack_error() {
		if ( isset($_POST['txn_id']) && ! empty($_POST['txn_id']) ) {
			$paystack_pm = EEM_Payment_method::instance()->get_one_of_type( 'Paystack_Onsite' );
			$transaction = EEM_Transaction::instance()->get_one_by_ID( $_POST['txn_id'] );
			$paystack_pm->type_obj()->get_gateway()->log( array('Paystack JS Error (Transaction: ' . $transaction->ID() . ')' => $_POST['message']), $transaction );
		}
	}

}
// End of file EE_PMT_Onsite.php