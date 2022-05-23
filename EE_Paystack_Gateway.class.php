<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }
/**
 * ------------------------------------------------------------------------
 *
 * Class  EE_PayStack
 *
 * @package			Event Espresso
 * @subpackage		espresso-paystack
 * @author			George Nnanwubar
 * @ version		 	$VID:$
 *
 * ------------------------------------------------------------------------
 */
// define the plugin directory path and URL
define( 'EE_PAYSTACK_BASENAME', plugin_basename( EE_PAYSTACK_PLUGIN_FILE ));
define( 'EE_PAYSTACK_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_PAYSTACK_URL', plugin_dir_url( __FILE__ ));
Class  EE_PayStack extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() {
		// Log Paystack JS errors.
		add_action( 'wp_ajax_eea_paystack_log_error', array( 'EE_PMT_Paystack_Onsite', 'log_paystack_error' ) );
		add_action( 'wp_ajax_nopriv_eea_paystack_log_error', array( 'EE_PMT_Paystack_Onsite', 'log_paystack_error' ) );
	}

	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'PayStack',
			array(
				'version' => EE_PAYSTACK_VERSION,
				'min_core_version' => '4.8.11.dev.000',
				'main_file_path' => EE_PAYSTACK_PLUGIN_FILE,
				'admin_callback' => 'additional_paystack_admin_hooks',
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options' => array(
					'pue_plugin_slug' => 'eea-paystack-gateway', //eea-paystack-gateway
					'plugin_basename' => EE_PAYSTACK_BASENAME,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
				),
				'payment_method_paths' => array(
					EE_PAYSTACK_PATH . 'payment_methods' . DS . 'Paystack_Onsite'
				),
		));
	}

	/**
	 * 	Setup default data for the addon.
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function initialize_default_data() {
		parent::initialize_default_data();

		// Update the currencies supported by this gateway (if changed).
		$paystack = EEM_Payment_method::instance()->get_one_of_type( 'Paystack_Onsite' );
		// Update If the payment method already exists.
		if ( $paystack ) {
			$currencies = $paystack->get_all_usable_currencies();
			$all_related = $paystack->get_many_related( 'Currency' );

			if ( ($currencies != $all_related) ) {
				$paystack->_remove_relations( 'Currency' );
				foreach ( $currencies as $currency_obj ) {
					$paystack->_add_relation_to( $currency_obj, 'Currency' );
				}
			}
		}
	}


	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_paystack_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		}
	}

	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links, $file ) {
		if ( $file == EE_PAYSTACK_BASENAME ) {
			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_payment_settings">' . __('Settings') . '</a>' );
		}
		return $links;
	}
}
// End of file EE_Paystack_Gateway.class.php
// Location: wp-content/plugins/espresso-paystack/EE_Paystack_Gateway.class.php
