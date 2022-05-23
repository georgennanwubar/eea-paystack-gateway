<?php
/*
  Plugin Name: Event Espresso - Paystack Payment Gateway (EE 4.6.0+)
  Plugin URI: http://www.eventespresso.com
  Description: The Event Espresso Paystack Gateway adds a new onsite payment method using Paystack Nigeria API.
  Version: 1.0.00.p
  Author: George E. Nnanwubar
  Author URI: http://www.george.ng
  Copyright 2016 Manndi Technologies (email : me@george.ng)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define( 'EE_PAYSTACK_VERSION', '1.0.00.p' );
define( 'EE_PAYSTACK_PLUGIN_FILE',  __FILE__ );
function load_espresso_paystack() {
	if ( class_exists( 'EE_Addon' ) && function_exists('json_decode') && function_exists('curl_init') ) {
		// paystack version
		require_once ( plugin_dir_path( __FILE__ ) . 'EE_Paystack_Gateway.class.php' );
		EE_PayStack::register_addon();
	}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_paystack' );

// End of file eea-paystack-gateway.php
// Location: wp-content/plugins/espresso-paystack/eea-paystack-gateway.php
