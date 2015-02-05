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
		add_filter( 'edd_settings_emails', array( $this, 'settings' ) );
		add_action( 'fes_custom_task_email_customers', array( $this, 'render_email_tab' ) );
		add_action( 'edd_fes_create_email', array( $this, 'process_vendor_email_submission' ) );

	}

	public function includes() {

		include $this->plugin_dir . 'includes/functions.php';

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

	public function settings( $settings ) {


		$settings['fes_pu_settings'] = array(
			'id' => 'fes_pu_settings',
			'name' => '<strong>' . __( 'FES Product Updates', 'edd-fes-product-updates' ) . '</strong>',
			'desc' => __( 'Configure the options for FES Product Updates', 'edd-fes-product-updates' ),
			'type' => 'header'
		);

		$settings['fes_pu_default_footer'] = array(
			'id' => 'fes_pu_default_footer',
			'name' => __( 'Default Email Footer Content', 'edd-fes-product-updates' ),
			'desc' => __( 'Content entered here will be automatically appended to the bottom of emails created by vendors', 'edd-fes-product-updates' ),
			'type' => 'textarea'
		);

		return $settings;
	}

	public function render_email_tab() {
		edd_get_template_part( 'fes', 'email-customers-tab' );
	}

	public function process_vendor_email_submission( $data ) {

		if( ! current_user_can( 'edit_shop_payments' ) && ! EDD_FES()->vendors->is_vendor( get_current_user_id() ) ) {
			wp_die( __( 'You do not have permission to submit email updates', 'edd-fes-product-updates' ), __( 'Error', 'edd-fes-product-updates' ), array( 'response' => 401 ) );
		}

		if( ! wp_verify_nonce( $data['edd_fes_create_email'], 'edd_fes_create_email' ) ) {
			wp_die( __( 'Nonce verification has failed', 'edd-fes-product-updates' ), __( 'Error', 'edd-fes-product-updates' ), array( 'response' => 401 ) );
		}

		if( empty( $data['fes-email-products'] ) ) {
			wp_die( __( 'Please select at least one product', 'edd-fes-product-updates' ), __( 'Error', 'edd-fes-product-updates' ), array( 'response' => 401 ) );
		}

		if( empty( $data['fes-email-subject'] ) ) {
			wp_die( __( 'Please enter a subject for the email', 'edd-fes-product-updates' ), __( 'Error', 'edd-fes-product-updates' ), array( 'response' => 401 ) );
		}

		if( empty( $data['fes-email-message'] ) ) {
			wp_die( __( 'Please enter a message for the email', 'edd-fes-product-updates' ), __( 'Error', 'edd-fes-product-updates' ), array( 'response' => 401 ) );
		}

		$products = $data['fes-email-products'];

		// Verify all included products belong to the current user
		foreach( $products as $key => $product_id ) {
			$product = get_post( $product_id );
			if( (int) get_current_user_id() !== (int) $product->post_author ) {
				unset( $products[ $key ] );
			}
		}

		// Re-verify there are products
		if( empty( $products ) ) {
			wp_die( __( 'Please select at least one product', 'edd-fes-product-updates' ), __( 'Error', 'edd-fes-product-updates' ), array( 'response' => 401 ) );
		}

		$author     = get_userdata( get_current_user_id() );
		$subject    = sanitize_text_field( $data['fes-email-subject'] );
		$message    = sanitize_text_field( $data['fes-email-message'] ) . "\n\n" . edd_get_option( 'fes_pu_default_footer' );
		$from_name  = $author->display_name;
		$from_email = $author->user_email;
		$auto_send  = $this->auto_send( $author->ID );

		$args = array(
			'post_type'    => 'edd_pup_email',
			'post_status'  => 'draft',
			'post_content' => $message,
			'post_excerpt' => $subject,
			'post_title'   => sprintf( __( 'Vendor #%d: %s', 'edd-fes-product-updates' ), $author->ID, $subject ),
		);

		$email_id   = wp_insert_post( $args );
		$recipients = edd_pup_customer_count( $email_id, $products );
		
		update_post_meta( $email_id, '_edd_pup_subject', $subject );
		update_post_meta( $email_id, '_edd_pup_message', $message );
		update_post_meta( $email_id, '_edd_pup_from_name', $from_name );
		update_post_meta( $email_id, '_edd_pup_from_email', $from_email );
		update_post_meta( $email_id, '_edd_pup_updated_products', $products );
		update_post_meta( $email_id, '_edd_pup_recipients', $recipients );

		if( $auto_send ) {

			

		} else {

			$this->notify_admins( $email_id, $author );

		}

	}

	private function notify_admins( $email_id = 0, $author = OBJECT ) {

		$subject = __( 'New Product Update Email Submitted', 'edd-fes-product-updates' );
		$message = sprintf(
			__( 'A new product update email has been submitted by a %s. Click <a href="%s">here</a> to review the email.', 'edd-fes-product-updates' ),
			$author->display_name,
			admin_url( 'edit.php?post_type=download&page=edd-prod-updates&view=edit_pup_email&id=' . $email_id )
		);

		EDD()->emails->send( edd_get_admin_notice_emails(), $subject, $message );

	}

	private function auto_send( $author_id = 0 ) {
		$ret = get_post_meta( $author_id, '_edd_fes_pu_auto_send', true );
		return (bool) apply_filters( 'edd_fes_product_updates_auto_send', $ret, $author_id );
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