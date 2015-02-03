<?php
/**
 * Plugin Name: Easy Digital Downloads - FES Product Updates
 * Plugin URI: http://easydigitaldownloads.com
 * Description: Allow Frontend Submissions vendors to send product updates to their customers 
 * Author: Pippin Williamson and Chris Klosowski
 * Author URI: http://easydigitaldownlads.com
 * Version: 1.0
 * Text Domain: edd-fes-product-updates
 * Domain Path: languages
 *
 * Easy Digital Downloads - FES Product Updates is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Easy Digital Downloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy Digital Downloads. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class EDD_FES_Product_Updates {

	private $plugin_dir;
	private $plugin_url;

	public function __construct() {

		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		$this->includes();

		add_filter( 'edd_template_paths', array( $this, 'register_template_path' ) );
		add_filter( 'fes_vendor_dashboard_menu', array( $this, 'register_email_menu' ) );
		add_filter( 'fes_signal_custom_task', array( $this, 'register_dashboard_task' ), 10, 2 );
		add_action( 'fes_custom_task_email_customers', array( $this, 'render_email_tab' ) );
	}

	public function includes() {

	}

	public function register_template_path( $paths ) {

		$paths[49] = trailingslashit( $this->plugin_dir ) . 'templates/';
		return $paths;
	}

	public function register_email_menu( $menu_items = array() ) {

		if( isset( $menu_items['logout'] ) ) {

			// Remove logout
			$logout = $menu_items['logout'];
			unset( $menu_items['logout'] );
		}

		$menu_items['emails'] = array(
			"icon" => "envelope",
			"task" => array( 'email_customers' ),
			"name" => __( 'Email Customers', 'edd-fes-product-updates' ),
		);

		if( isset( $logout ) ) {

			// Re-add logout so it remains at the end
			$menu_items['logout'] = $logout;
		}

		return $menu_items;
	}

	public function register_dashboard_task( $custom, $task ) {
		
		if( 'email_customers' == $task ) {
			$custom = 'email_customers';
		}

		return $custom;
	}

	public function render_email_tab() {
		edd_get_template_part( 'fes', 'email-customers-tab' );
	}

}

function edd_fes_load_product_updates() {

	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		return;
	}

	if( ! class_exists( 'EDD_Front_End_Submissions' ) ) {
		return;
	}

	if( ! class_exists( 'EDD_Product_Updates' ) ) {
		return;
	}

	$instance = new EDD_FES_Product_Updates;

}
add_action( 'plugins_loaded', 'edd_fes_load_product_updates', 9999 );