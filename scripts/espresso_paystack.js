jQuery(document).ready(function($) {
	var EE_PAYSTACK;
	
	EE_PAYSTACK = {
		
		handler : {},
		submit_button_id : '#ee-paystack-button-btn',
		payment_method_selector : {},
		payment_method_info_div : {},
		paystack_button_div : {},
		submit_payment_button : {},
		transcation_reference : {},
		transaction_email : {},
		transaction_total : {},
		product_description : {},
		paystack_response : {},
		offset_from_top_modifier : -400,
		notification : '',
		initialized : false,
		selected : false,
		txn_data : {},
		
		/**
		 * @function initialize
		 */
		initialize : function() {

			EE_PAYSTACK.initialize_objects();
			// ensure that the SPCO js class is loaded
			if ( typeof SPCO === 'undefined' ) {
				EE_PAYSTACK.hide_paystack();
				EE_PAYSTACK.display_error( paystack_transaction_args.no_SPCO_error );
				return;
			}
			/*// ensure that the PaystackCheckout js class is loaded
			if ( typeof PaystackCheckout === 'undefined' ) {
				//SPCO.console_log( 'initialize', 'no PaystackCheckout!!', true );
				SPCO.offset_from_top_modifier = EE_PAYSTACK.offset_from_top_modifier;
				EE_PAYSTACK.notification = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'EE_PAYSTACK.init() error', paystack_transaction_args.no_PaystackCheckout_error ), '' );
				SPCO.scroll_to_top_and_display_messages( EE_PAYSTACK.paystack_button_div, EE_PAYSTACK.notification, true );
				return;
			}*/
			EE_PAYSTACK.selected = true;
			EE_PAYSTACK.disable_SPCO_submit_buttons_if_Paystack_selected();

			// has the Paystack gateway has been selected ? or already initialized?
			if ( ! EE_PAYSTACK.submit_payment_button.length || EE_PAYSTACK.initialized ) {
				//SPCO.console_log( 'initialize', 'already initialized!', true );
				return;
			}
			
			EE_PAYSTACK.get_transaction_data();
			/*EE_PAYSTACK.set_up_handler();
			EE_PAYSTACK.set_listener_for_payment_method_selector();
			EE_PAYSTACK.set_listener_for_payment_amount_change();
			EE_PAYSTACK.set_listener_for_submit_payment_button();
			EE_PAYSTACK.set_listener_for_leave_page();
			//EE_PAYSTACK.get_transaction_data();
			//alert('EE_PAYSTACK.initialized');
			EE_PAYSTACK.initialized = true;*/
		},

		/**
		 * @function initialize_objects
		 */
		initialize_objects : function() {
			EE_PAYSTACK.submit_payment_button = $( EE_PAYSTACK.submit_button_id );
			EE_PAYSTACK.payment_method_selector = $('#ee-available-payment-method-inputs-paystack_onsite-lbl');
			EE_PAYSTACK.payment_method_info_div = $('#spco-payment-method-info-paystack_onsite');
			EE_PAYSTACK.paystack_button_div = $('#ee-paystack-button-dv');
			EE_PAYSTACK.transcation_reference = $('#ee-paystack-reference');
			EE_PAYSTACK.transaction_email = $('#ee-paystack-transaction-email');
			EE_PAYSTACK.transaction_total = $('#ee-paystack-transaction-total');
			EE_PAYSTACK.product_description = $('#ee-paystack-prod-description');
			EE_PAYSTACK.paystack_response = $('#ee-paystack-response-pg');
		},
		
		/**
		 * @function get_transaction_data
		 */
		get_transaction_data : function() {
			var req_data = {};
			req_data.step = 'payment_options';
			req_data.action = 'get_transaction_details_for_gateways';
			req_data.selected_method_of_payment = paystack_transaction_args.payment_method_slug;
			req_data.generate_reg_form = false;
			req_data.process_form_submission = false;
			req_data.noheader = true;
			req_data.ee_front_ajax = true;
			req_data.EESID = eei18n.EESID;
			req_data.revisit = eei18n.revisit;
			req_data.e_reg_url_link = eei18n.e_reg_url_link;

			$.ajax( {
				type : "POST",
				url : eei18n.ajax_url,
				data : req_data,
				dataType : "json",

				beforeSend : function() {
					SPCO.do_before_sending_ajax();
				},
				success : function( response ) {
					EE_PAYSTACK.txn_data = response;
					SPCO.end_ajax();
					EE_PAYSTACK.set_up_handler();
					EE_PAYSTACK.set_listener_for_payment_method_selector();
					EE_PAYSTACK.set_listener_for_payment_amount_change();
					EE_PAYSTACK.set_listener_for_submit_payment_button();
					EE_PAYSTACK.set_listener_for_leave_page();
					//alert('EE_PAYSTACK.initialized');
					EE_PAYSTACK.initialized = true;
				},
				error : function() {
					SPCO.end_ajax();
					return SPCO.submit_reg_form_server_error();
				},
				timeout: 10000
			});
		},
		
		/**
		 * @function set_up_handler
		 */
		set_up_handler : function() {
			//console.log( JSON.stringify(EE_PAYSTACK.txn_data, null, 4 ) );
			EE_PAYSTACK.handler = PaystackPop.setup({
				key: paystack_transaction_args.key,
				email:EE_PAYSTACK.transaction_email.val(),
				amount:parseFloat( EE_PAYSTACK.txn_data['payment_amount'] ) * 100,
				callback: function(response){
					if( (response.hasOwnProperty('trxref')) && (response.trxref !== '') ){
						EE_PAYSTACK.checkout_success(response);
					}else{
						EE_PAYSTACK.checkout_error(response);
					}
				}
				
			});
		},
		
		/**
		 * @function checkout_success
		 * @param  {object} paystack_token
		 */
		checkout_success : function( response ) {
			// Enable SPCO submit buttons.
			SPCO.enable_submit_buttons();
			if ( (response.hasOwnProperty('trxref')) && (response.trxref !== '') ) {
				EE_PAYSTACK.submit_payment_button.prop('disabled', true ).addClass('spco-disabled-submit-btn');
				EE_PAYSTACK.transcation_reference.val( response.trxref );
				SPCO.offset_from_top_modifier = EE_PAYSTACK.offset_from_top_modifier;
				EE_PAYSTACK.notification = SPCO.generate_message_object( paystack_transaction_args.accepted_message, '', '' );
				SPCO.scroll_to_top_and_display_messages( EE_PAYSTACK.paystack_button_div, EE_PAYSTACK.notification, true );
				//  hide any return to cart buttons, etc
				$( '.hide-me-after-successful-payment-js' ).hide();
				// trigger click event on SPCO "Proceed to Next Step" button
				EE_PAYSTACK.submit_payment_button.parents( 'form:first' ).find( '.spco-next-step-btn' ).trigger( 'click' );
			}
		},

		/**
		 * @function checkout_error
		 * @param  {object} Paystack_token
		 */
		checkout_error : function( response ) {
			SPCO.hide_notices();
			if ( typeof response.error !== 'undefined' ) {
				SPCO.offset_from_top_modifier = EE_PAYSTACK.offset_from_top_modifier;
				EE_PAYSTACK.notification = SPCO.generate_message_object( '', SPCO.tag_message_for_debugging( 'PaystackResponseHandler error', response.error.message ), '' );
				SPCO.scroll_to_top_and_display_messages( EE_PAYSTACK.paystack_button_div, EE_PAYSTACK.notification, true );
				EE_PAYSTACK.paystack_response.text( paystack_transaction_args.card_error_message ).addClass( 'important-notice error' ).show();
			}
		},

		/**
		 * @function set_listener_for_payment_method_selector
		 */
		set_listener_for_payment_method_selector : function() {
			SPCO.main_container.on( 'click', '.spco-next-step-btn', function() {
				EE_PAYSTACK.disable_SPCO_submit_buttons_if_Paystack_selected();
			});
		},

		/**
		 * @function set_listener_for_payment_amount_change
		 */
		set_listener_for_payment_amount_change : function() {
			SPCO.main_container.on( 'spco_payment_amount', function( event, payment_amount ) {
				EE_PAYSTACK.transaction_total.val( payment_amount * 100 );
				EE_PAYSTACK.txn_data[ 'payment_amount' ] = payment_amount;
			});
		},

		/**
		 * @function disable_SPCO_submit_buttons_if_Paystack_selected
		 * Deactivate SPCO submit buttons to prevent submitting with no Paystack token.
		 */
		disable_SPCO_submit_buttons_if_Paystack_selected : function() {
			if ( EE_PAYSTACK.selected && EE_PAYSTACK.submit_payment_button.length > 0 && EE_PAYSTACK.submit_payment_button.val().length <= 0 ) {
				SPCO.allow_enable_submit_buttons = false;
				SPCO.disable_submit_buttons();
			}
		},

		/**
		 * @function set_listener_for_submit_payment_button
		 */
		set_listener_for_submit_payment_button : function() {
			SPCO.main_container.on( 'click', EE_PAYSTACK.submit_button_id, function(e) {
				e.preventDefault();
				SPCO.hide_notices();
				EE_PAYSTACK.handler.openIframe();
			});
		},

		/**
		 * @function hide_paystack
		 */
		hide_paystack : function() {
			EE_PAYSTACK.payment_method_selector.hide();
			EE_PAYSTACK.payment_method_info_div.hide();
		},
		
		/**
		 * @function set_listener_for_leave_page
		 * Close Checkout on page navigation
		 */
		set_listener_for_leave_page : function() {
			$(window).on( 'popstate', function() {
				EE_PAYSTACK.handler.close();
			});
		},

		/**
		 * @function display_error
		 * @param  {string} msg
		 */
		display_error : function( msg ) {
			// center notices on screen
			$('#espresso-ajax-notices').eeCenter( 'fixed' );
			// target parent container
			var espresso_ajax_msg = $('#espresso-ajax-notices-error');
			//  actual message container
			espresso_ajax_msg.children('.espresso-notices-msg').html( msg );
			// bye bye spinner
			$('#espresso-ajax-loading').fadeOut('fast');
			// display message
			espresso_ajax_msg.removeClass('hidden').show().delay( 10000 ).fadeOut();
			// Log the Error.
			EE_PAYSTACK.log_error(msg);
		},
		
		/**
		 * @function log_error
		 * @param  {string} msg
		 */
		log_error : function( msg ) {
			$.ajax({
				type : 'POST',
				dataType : 'json',
				url : eei18n.ajax_url,
				data : {
					action : 'eea_paystack_log_error',
					txn_id : EE_PAYSTACK.txn_data['TXN_ID'],
					message : msg
				}
			});
		}
/*
var handler = PaystackPop.setup({
            key: wc_paystack_params.key,
            email: wc_paystack_params.email,
            amount: wc_paystack_params.amount,
            ref: wc_paystack_params.txnref,
            callback: paystack_callback,
            onClose: function() {
                $( this.el ).unblock();
            }
        });
        handler.openIframe();
*/

	};
	// end of EE_PAYSTACK object

	// initialize Paystack Checkout if the SPCO reg step changes to "payment_options"
	SPCO.main_container.on( 'spco_display_step', function( event, step_to_show ) {
		if ( typeof step_to_show !== 'undefined' && step_to_show === 'payment_options' ) {
			EE_PAYSTACK.initialize();
		}
	});

	// also initialize Paystack Checkout if the selected method of payment changes
	SPCO.main_container.on( 'spco_switch_payment_methods', function( event, payment_method ) {
		//SPCO.console_log( 'payment_method', payment_method, false );
		if ( typeof payment_method !== 'undefined' && payment_method === paystack_transaction_args.payment_method_slug ) {
			EE_PAYSTACK.selected = true;
			EE_PAYSTACK.initialize();
		} else {
			EE_PAYSTACK.selected = false;
		}
	});

	// also initialize Paystack Checkout if the page just happens to load on the "payment_options" step with Paystack already selected!
	EE_PAYSTACK.initialize();

});