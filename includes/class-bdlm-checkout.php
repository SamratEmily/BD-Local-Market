<?php
/**
 * BDLM Checkout — Simplified Checkout with Delivery Zone Charges for BD Local Market.
 *
 * Features:
 * - Fields: Name (required), Phone Number (required), Address (optional).
 * - Delivery Zone selection: Inside Lohagara (৳30) vs Outside Lohagara (৳60).
 * - Dynamic cart fee calculation based on selected delivery zone.
 * - Automatic AJAX recalculation on radio button change.
 * - Order & profile meta saving.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Checkout
 */
class BDLM_Checkout {

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		// Filter billing fields
		add_filter( 'woocommerce_billing_fields', array( $this, 'modify_billing_fields' ), 999, 1 );

		// Filter checkout fields array
		add_filter( 'woocommerce_checkout_fields', array( $this, 'modify_checkout_fields' ), 999, 1 );

		// Enable guest checkout programmatically ('yes' string required by WC core)
		add_filter( 'option_woocommerce_enable_guest_checkout', function() { return 'yes'; } );
		add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', function() { return 'no'; } );
		add_filter( 'option_woocommerce_enable_checkout_login_reminder', function() { return 'no'; } );

		// Dynamic cart fee calculation (Inside vs Outside Lohagara)
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_delivery_fee' ) );

		// Inject fallback data before WC core validation runs
		add_action( 'woocommerce_checkout_process', array( $this, 'inject_checkout_fallbacks' ), 1 );

		// Server-side validation
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_fields' ), 5 );

		// Save delivery zone & phone to order meta & user meta
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_order_fields' ) );
		add_action( 'woocommerce_checkout_update_user_meta',  array( $this, 'save_user_phone_meta' ), 10, 2 );

		// Admin order view details
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_order_meta_admin' ), 10, 1 );

		// Auto-fill phone from user profile
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'autofill_phone_from_profile' ), 10, 2 );

		// Enqueue checkout JS for live fee updating
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_js' ) );
	}

	// =========================================================================
	// Billing Fields Filter (woocommerce_billing_fields)
	// =========================================================================

	/**
	 * Keep Name, Phone Number, Address (optional), and Delivery Zone radio.
	 *
	 * @param array $fields Billing fields.
	 * @return array
	 */
	public function modify_billing_fields( $fields ) {
		$is_logged_in = is_user_logged_in();

		// Preserve Name, Phone, and Address 1
		$keep = array( 'billing_first_name', 'billing_phone', 'billing_address_1' );

		foreach ( array_keys( $fields ) as $field_key ) {
			if ( ! in_array( $field_key, $keep, true ) ) {
				unset( $fields[ $field_key ] );
			}
		}

		// 1. Name field
		if ( isset( $fields['billing_first_name'] ) ) {
			$fields['billing_first_name']['label']       = __( 'Name', 'bd-local-market' );
			$fields['billing_first_name']['placeholder'] = __( 'Enter your full name', 'bd-local-market' );
			$fields['billing_first_name']['required']    = true;
			$fields['billing_first_name']['class']       = array( 'form-row-wide' );
			$fields['billing_first_name']['priority']    = 10;
		}

		// 2. Phone field
		if ( isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone']['label']       = __( 'Phone Number', 'bd-local-market' );
			$fields['billing_phone']['placeholder'] = '01XXXXXXXXX';
			$fields['billing_phone']['class']       = array( 'form-row-wide' );
			$fields['billing_phone']['priority']    = 20;

			if ( ! $is_logged_in ) {
				$fields['billing_phone']['required'] = true;
			} else {
				$user_id                             = get_current_user_id();
				$user_phone                          = get_user_meta( $user_id, 'billing_phone', true );
				$fields['billing_phone']['required'] = empty( $user_phone );
			}
		}

		// 3. Address field (Optional)
		if ( isset( $fields['billing_address_1'] ) ) {
			$fields['billing_address_1']['label']       = __( 'Address (optional)', 'bd-local-market' );
			$fields['billing_address_1']['placeholder'] = __( 'e.g. Village/Area, House, Road (optional)', 'bd-local-market' );
			$fields['billing_address_1']['required']    = false;
			$fields['billing_address_1']['class']       = array( 'form-row-wide' );
			$fields['billing_address_1']['priority']    = 30;
		}

		// 4. Add Delivery Area Radio selection
		$settings = (array) get_option( 'bdlm_checkout_settings', array() );
		$fee_in   = absint( $settings['fee_inside_lohagara'] ?? 30 );
		$fee_out  = absint( $settings['fee_outside_lohagara'] ?? 60 );

		$fields['bdlm_delivery_zone'] = array(
			'type'        => 'radio',
			'label'       => __( 'Delivery Area / Zone', 'bd-local-market' ),
			'required'    => true,
			'class'       => array( 'form-row-wide', 'bdlm-delivery-zone-row' ),
			'priority'    => 40,
			'default'     => 'inside_lohagara',
			'options'     => array(
				'inside_lohagara'  => sprintf( __( 'Inside Lohagara (৳%d)', 'bd-local-market' ), $fee_in ),
				'outside_lohagara' => sprintf( __( 'Outside Lohagara (৳%d)', 'bd-local-market' ), $fee_out ),
			),
		);

		return $fields;
	}

	// =========================================================================
	// Checkout Fields Filter (woocommerce_checkout_fields)
	// =========================================================================

	/**
	 * Remove shipping & account sections.
	 *
	 * @param array $fields All checkout fields.
	 * @return array
	 */
	public function modify_checkout_fields( $fields ) {
		if ( isset( $fields['billing'] ) ) {
			$fields['billing'] = $this->modify_billing_fields( $fields['billing'] );
		}

		unset( $fields['shipping'] );
		unset( $fields['account'] );

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = __( 'Delivery Instructions (optional)', 'bd-local-market' );
			$fields['order']['order_comments']['placeholder'] = __( 'Any special instructions for delivery?', 'bd-local-market' );
			$fields['order']['order_comments']['required']    = false;
		}

		return $fields;
	}

	// =========================================================================
	// Dynamic Cart Fee Calculation (Inside vs Outside Lohagara)
	// =========================================================================

	/**
	 * Calculate delivery fee dynamically based on selected zone.
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public function calculate_delivery_fee( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$settings = (array) get_option( 'bdlm_checkout_settings', array() );
		$fee_in   = absint( $settings['fee_inside_lohagara'] ?? 30 );
		$fee_out  = absint( $settings['fee_outside_lohagara'] ?? 60 );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$post_data = array();
		if ( isset( $_POST['post_data'] ) ) {
			wp_parse_str( wp_unslash( $_POST['post_data'] ), $post_data );
		}

		$selected_zone = $post_data['bdlm_delivery_zone'] ?? ( $_POST['bdlm_delivery_zone'] ?? 'inside_lohagara' );
		// phpcs:enable

		if ( 'outside_lohagara' === $selected_zone ) {
			$fee_amount = $fee_out;
			$fee_title  = sprintf( __( 'Delivery Fee (Outside Lohagara)', 'bd-local-market' ) );
		} else {
			$fee_amount = $fee_in;
			$fee_title  = sprintf( __( 'Delivery Fee (Inside Lohagara)', 'bd-local-market' ) );
		}

		if ( $fee_amount > 0 ) {
			$cart->add_fee( $fee_title, $fee_amount, false, '' );
		}
	}

	// =========================================================================
	// Inject Fallback Values for WooCommerce Core
	// =========================================================================

	/**
	 * Inject hidden defaults into $_POST for WooCommerce core validation.
	 */
	public function inject_checkout_fallbacks() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$phone       = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '';
		$clean_phone = preg_replace( '/\D/', '', $phone );
		$address     = isset( $_POST['billing_address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_address_1'] ) ) : '';

		if ( empty( $_POST['billing_first_name'] ) ) {
			$_POST['billing_first_name'] = __( 'Customer', 'bd-local-market' );
		}
		$_POST['billing_last_name'] = '';

		if ( empty( $address ) ) {
			$_POST['billing_address_1'] = __( 'Lohagara Area', 'bd-local-market' );
		}

		$_POST['billing_country']  = 'BD';
		$_POST['billing_address_2'] = '';
		$_POST['billing_city']      = __( 'Lohagara', 'bd-local-market' );
		$_POST['billing_state']     = 'BD-13';
		$_POST['billing_postcode']  = '1200';

		if ( empty( $_POST['billing_email'] ) ) {
			$email_user = ! empty( $clean_phone ) ? $clean_phone : 'guest_' . time();
			$_POST['billing_email'] = $email_user . '@bazarshodai.local';
		}
		// phpcs:enable
	}

	// =========================================================================
	// Server-side Validation
	// =========================================================================

	/**
	 * Validate required fields.
	 */
	public function validate_checkout_fields() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$name  = isset( $_POST['billing_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) ) : '';
		$phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '';
		// phpcs:enable

		$is_logged_in = is_user_logged_in();

		if ( empty( $name ) || __( 'Customer', 'bd-local-market' ) === $name ) {
			wc_add_notice( __( 'Please enter your name.', 'bd-local-market' ), 'error' );
		}

		$phone_required = true;
		if ( $is_logged_in ) {
			$user_phone     = get_user_meta( get_current_user_id(), 'billing_phone', true );
			$phone_required = empty( $user_phone );
		}

		if ( $phone_required && empty( $phone ) ) {
			wc_add_notice( __( 'Please enter your phone number.', 'bd-local-market' ), 'error' );
		} elseif ( ! empty( $phone ) ) {
			$gen_checkout = (array) get_option( 'bdlm_checkout_settings', array() );
			$validate_bd  = $gen_checkout['enable_phone_validation'] ?? '1';

			if ( '1' === (string) $validate_bd && ! $this->validate_bd_phone( $phone ) ) {
				wc_add_notice(
					__( 'Please enter a valid Bangladeshi mobile number (e.g. 01712345678 or +8801712345678).', 'bd-local-market' ),
					'error'
				);
			}
		}
	}

	/**
	 * Validate BD phone format.
	 */
	private function validate_bd_phone( $phone ) {
		$clean_phone = preg_replace( '/[\s\-]/', '', $phone );
		return (bool) preg_match( '/^(\+?880|0{2}880)?01[3-9]\d{8}$/', $clean_phone );
	}

	// =========================================================================
	// Save Order & User Meta
	// =========================================================================

	/**
	 * Save delivery location & phone to order meta.
	 *
	 * @param int $order_id WC Order ID.
	 */
	public function save_order_fields( $order_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '';
		$zone  = isset( $_POST['bdlm_delivery_zone'] ) ? sanitize_text_field( wp_unslash( $_POST['bdlm_delivery_zone'] ) ) : 'inside_lohagara';
		// phpcs:enable

		$settings   = (array) get_option( 'bdlm_checkout_settings', array() );
		$zone_label = ( 'outside_lohagara' === $zone )
			? sprintf( __( 'Outside Lohagara (৳%d)', 'bd-local-market' ), absint( $settings['fee_outside_lohagara'] ?? 60 ) )
			: sprintf( __( 'Inside Lohagara (৳%d)', 'bd-local-market' ), absint( $settings['fee_inside_lohagara'] ?? 30 ) );

		if ( ! empty( $phone ) ) {
			update_post_meta( $order_id, '_bdlm_billing_phone', $phone );
		}

		update_post_meta( $order_id, '_bdlm_delivery_zone', $zone_label );

		if ( ! is_user_logged_in() ) {
			update_post_meta( $order_id, '_bdlm_guest_order', '1' );
		}
	}

	/**
	 * Save phone to user profile meta for logged-in users.
	 */
	public function save_user_phone_meta( $user_id, $posted ) {
		if ( ! empty( $posted['billing_phone'] ) ) {
			$phone = sanitize_text_field( $posted['billing_phone'] );
			update_user_meta( $user_id, 'billing_phone', $phone );
		}
	}

	/**
	 * Display details in WC Admin Order view.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function display_order_meta_admin( $order ) {
		$zone_label = get_post_meta( $order->get_id(), '_bdlm_delivery_zone', true );
		if ( $zone_label ) {
			echo '<p><strong>' . esc_html__( 'Delivery Area:', 'bd-local-market' ) . '</strong> ' . esc_html( $zone_label ) . '</p>';
		}
		$is_guest = get_post_meta( $order->get_id(), '_bdlm_guest_order', true );
		if ( $is_guest ) {
			echo '<p style="color:#00A651;font-weight:700;">🛒 ' . esc_html__( 'Guest Order (BD Local Market)', 'bd-local-market' ) . '</p>';
		}
	}

	/**
	 * Autofill profile phone.
	 */
	public function autofill_phone_from_profile( $value, $input_key ) {
		if ( is_user_logged_in() && 'billing_phone' === $input_key && empty( $value ) ) {
			$user_id    = get_current_user_id();
			$user_phone = get_user_meta( $user_id, 'billing_phone', true );
			if ( ! empty( $user_phone ) ) {
				return $user_phone;
			}
		}
		return $value;
	}

	// =========================================================================
	// Enqueue Checkout JS
	// =========================================================================

	/**
	 * Enqueue checkout JS to trigger AJAX recalculation on radio button change.
	 */
	public function enqueue_checkout_js() {
		if ( is_checkout() && ! is_order_received_page() ) {
			wp_add_inline_script(
				'jquery',
				"jQuery(document).ready(function($) {
					$(document.body).on('change', 'input[name=\"bdlm_delivery_zone\"]', function() {
						$(document.body).trigger('update_checkout');
					});
				});"
			);
		}
	}
}
