<?php
/**
 * BDLM Product Card — Renderer, WC loop integration, and product meta.
 *
 * Provides:
 * - bd_local_product_card( $product, $args )  Global template function
 * - BDLM_Product_Card class                   WC loop hooks + admin meta fields
 *
 * WC Loop integration strategy:
 * - On shop/category pages: remove default WC loop hooks and replace with our card.
 * - Hooks removed: loop-item link open/close, thumbnail, title, rating, price, add-to-cart.
 * - Replaced by: single woocommerce_before_shop_loop_item_title action → bd_local_product_card.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// Global template function
// ============================================================================

/**
 * Render a single product card using the Shwapno-style template.
 *
 * @param WC_Product|int $product Product object or ID.
 * @param array          $args    {
 *     Optional overrides.
 *
 *     @type string $badge_type   'percent' | 'taka'. Default: from product card settings.
 *     @type string $badge_style  'pill' | 'square' | 'ribbon'. Default: from settings.
 *     @type bool   $show_delivery Whether to show delivery badge. Default: true.
 *     @type string $currency     Currency symbol. Default: from settings.
 * }
 * @return string Rendered HTML or empty string on failure.
 */
function bd_local_product_card( $product, $args = array() ) {
	// Accept product ID.
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	// Load settings once.
	$card_settings    = wp_parse_args( (array) get_option( 'bdlm_product_card_settings', array() ), bdlm_default_product_card_settings() );
	$general_settings = wp_parse_args( (array) get_option( 'bdlm_general_settings',      array() ), bdlm_default_general_settings() );

	// Merge caller args with defaults.
	$args = wp_parse_args( $args, array(
		'badge_type'    => 'percent',                                 // 'percent' | 'taka'
		'badge_style'   => $card_settings['badge_style']  ?? 'pill',
		'show_delivery' => ! empty( $card_settings['show_delivery_badge'] ),
		'show_savings'  => ! empty( $card_settings['show_savings_badge'] ),
		'show_unit'     => ! empty( $card_settings['show_unit_info'] ),
		'currency'      => $general_settings['currency_symbol'] ?? '৳',
	) );

	$product_id = $product->get_id();

	// ---- Delivery text ----
	$delivery_text = get_post_meta( $product_id, '_bdlm_delivery_time', true );
	if ( empty( $delivery_text ) ) {
		$delivery_text = $general_settings['delivery_time_text'] ?? __( '1-2 hours', 'bd-local-market' );
	}

	// ---- Unit label ----
	$unit_label = '';
	if ( $args['show_unit'] ) {
		$unit_label = get_post_meta( $product_id, '_bdlm_unit', true );
		if ( empty( $unit_label ) && $product->has_weight() ) {
			$unit_label = $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit', 'kg' );
		}
	}

	// ---- Minimum quantity note ----
	$min_qty_note = get_post_meta( $product_id, '_bdlm_min_qty', true );

	// Expose all variables for the template.
	$currency      = $args['currency'];
	$badge_type    = $args['badge_type'];
	$badge_style   = $args['badge_style'];
	$show_delivery = $args['show_delivery'];

	ob_start();

	$template = apply_filters(
		'bdlm_product_card_template',
		BDLM_PATH . 'templates/product-card.php',
		$product
	);

	if ( file_exists( $template ) ) {
		include $template;
	}

	return ob_get_clean();
}

// ============================================================================
// Class: BDLM_Product_Card
// ============================================================================

/**
 * Class BDLM_Product_Card
 *
 * Hooks into WooCommerce product loops to replace the default loop card
 * with the Shwapno-style bd_local_product_card() template on shop and
 * category archive pages.
 *
 * Also adds custom product meta fields to the WC admin product editor.
 */
class BDLM_Product_Card {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Completely suppress WooCommerce & Astra default "Sale!" flash badge
		add_filter( 'woocommerce_sale_flash', '__return_empty_string', 999 );
		add_filter( 'astra_woo_sale_flash',   '__return_empty_string', 999 );

		// Template overrides for content-product.php
		add_filter( 'wc_get_template',           array( $this, 'override_wc_content_product_template' ), 999, 5 );
		add_filter( 'woocommerce_locate_template', array( $this, 'override_wc_content_product_template' ), 999, 5 );

		// Remove default WooCommerce loop elements globally.
		add_action( 'init', array( $this, 'remove_default_loop_hooks_globally' ), 99 );

		// Replace WC loop output on archive/shop pages.
		add_action( 'wp',   array( $this, 'maybe_override_loop' ) );

		// Admin: extra product meta fields.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_meta_fields' ) );
		add_action( 'woocommerce_process_product_meta',                 array( $this, 'save_product_meta_fields' ) );

		// Single product page: show unit/delivery below title.
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_unit_delivery' ), 21 );
	}

	/**
	 * Override WooCommerce content-product.php template with our clean product card template.
	 */
	public function override_wc_content_product_template( $located, $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( 'content-product.php' === $template_name || ( is_string( $located ) && str_ends_with( $located, 'content-product.php' ) ) ) {
			$custom_template = BDLM_PLUGIN_DIR . 'templates/content-product.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $located;
	}

	/**
	 * Remove default WooCommerce loop elements globally to avoid double titles, prices, and Sale badges.
	 */
	public function remove_default_loop_hooks_globally() {
		// Hide default Sale! badge overlay
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

		// Astra theme loop filters
		remove_action( 'woocommerce_after_shop_loop_item', 'astra_woo_woocommerce_shop_loop_item_title', 10 );
	}

	// =========================================================================
	// WC Loop Override
	// =========================================================================

	/**
	 * Hook in after WP query is resolved so we can inspect page type.
	 * Only replaces loop on shop/category/tag archive pages.
	 */
	public function maybe_override_loop() {
		if ( ! ( is_shop() || is_product_taxonomy() || is_product_tag() || is_search() ) ) {
			return;
		}
		$this->remove_default_loop_hooks();
		$this->add_custom_loop_hooks();
	}

	/**
	 * Remove all the default WooCommerce loop item hooks we're replacing.
	 */
	private function remove_default_loop_hooks() {
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_before_shop_loop_item',       'woocommerce_template_loop_product_link_open',  10 );
		remove_action( 'woocommerce_after_shop_loop_item',        'woocommerce_template_loop_product_link_close', 5  );
		remove_action( 'woocommerce_after_shop_loop_item',        'woocommerce_template_loop_add_to_cart',        10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail',  10 );
		remove_action( 'woocommerce_shop_loop_item_title',        'woocommerce_template_loop_product_title',      10 );
		remove_action( 'woocommerce_after_shop_loop_item_title',  'woocommerce_template_loop_rating',             5  );
		remove_action( 'woocommerce_after_shop_loop_item_title',  'woocommerce_template_loop_price',              10 );
	}

	/**
	 * Remove default loop hooks on archive pages.
	 */
	private function add_custom_loop_hooks() {
		// Prevent empty link tags or theme wrappers
		remove_all_actions( 'woocommerce_before_shop_loop_item_title' );
		remove_all_actions( 'woocommerce_shop_loop_item_title' );
		remove_all_actions( 'woocommerce_after_shop_loop_item_title' );
		remove_all_actions( 'woocommerce_after_shop_loop_item' );
	}

	/**
	 * Echo the custom card for the current product in a WC loop.
	 */
	public function render_loop_card() {
		global $product;
		if ( ! $product ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo bd_local_product_card( $product );
	}

	/**
	 * Neutralise WC's default <a> link open around the loop item.
	 * Our card has its own links — we don't want a double wrapper.
	 */
	public function noop_link_open() {
		// Override the default woocommerce_template_loop_product_link_open.
		remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	}

	public function noop_link_close() {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	}

	// =========================================================================
	// Single product: unit & delivery info
	// =========================================================================

	/**
	 * Show unit label and delivery badge on single product summary.
	 */
	public function render_single_unit_delivery() {
		global $product;
		if ( ! $product ) {
			return;
		}

		$unit         = get_post_meta( $product->get_id(), '_bdlm_unit', true );
		$delivery     = get_post_meta( $product->get_id(), '_bdlm_delivery_time', true );
		$min_qty_note = get_post_meta( $product->get_id(), '_bdlm_min_qty', true );
		$general      = wp_parse_args( (array) get_option( 'bdlm_general_settings', array() ), bdlm_default_general_settings() );

		if ( empty( $delivery ) ) {
			$delivery = $general['delivery_time_text'] ?? '';
		}

		if ( empty( $unit ) && $product->has_weight() ) {
			$unit = $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit', 'kg' );
		}

		if ( empty( $unit ) && empty( $delivery ) && empty( $min_qty_note ) ) {
			return;
		}

		echo '<div class="bdlm-single-meta">';
		if ( ! empty( $unit ) ) {
			printf( '<span class="bdlm-single-meta__unit">%s</span>', esc_html( $unit ) );
		}
		if ( ! empty( $min_qty_note ) ) {
			printf( '<span class="bdlm-single-meta__min-qty">%s</span>', esc_html__( 'Min.', 'bd-local-market' ) . ' ' . esc_html( $min_qty_note ) );
		}
		if ( ! empty( $delivery ) ) {
			printf(
				'<span class="bdlm-single-meta__delivery"><span aria-hidden="true">🚚</span> %s %s</span>',
				esc_html__( 'Delivery', 'bd-local-market' ),
				esc_html( $delivery )
			);
		}
		echo '</div>';
	}

	// =========================================================================
	// WC Admin: Extra product meta fields
	// =========================================================================

	/**
	 * Add BD Local Market meta fields to the WC product general tab.
	 */
	public function add_product_meta_fields() {
		global $post;
		echo '<div class="options_group bdlm-options-group">';
		echo '<h4 style="padding:10px 12px;color:#00A651;border-top:1px solid #eee;margin:0;">'
			. esc_html__( 'BD Local Market', 'bd-local-market' )
			. '</h4>';

		// Unit / Quantity label.
		woocommerce_wp_text_input( array(
			'id'          => '_bdlm_unit',
			'label'       => __( 'Unit / Quantity Label', 'bd-local-market' ),
			'placeholder' => __( 'e.g. Per 1 kg, Per Piece, Per Dozen', 'bd-local-market' ),
			'desc_tip'    => true,
			'description' => __( 'Shown below the product title on the card and single page.', 'bd-local-market' ),
			'value'       => get_post_meta( $post->ID, '_bdlm_unit', true ),
		) );

		// Minimum quantity note.
		woocommerce_wp_text_input( array(
			'id'          => '_bdlm_min_qty',
			'label'       => __( 'Minimum Quantity Note', 'bd-local-market' ),
			'placeholder' => __( 'e.g. 500g, 2 pieces', 'bd-local-market' ),
			'desc_tip'    => true,
			'description' => __( 'Optional note shown on the card for loose/bulk items. Prefixed with "Min."', 'bd-local-market' ),
			'value'       => get_post_meta( $post->ID, '_bdlm_min_qty', true ),
		) );

		// Delivery time override.
		woocommerce_wp_text_input( array(
			'id'          => '_bdlm_delivery_time',
			'label'       => __( 'Delivery Time Badge', 'bd-local-market' ),
			'placeholder' => __( 'e.g. Express: 1 hr', 'bd-local-market' ),
			'desc_tip'    => true,
			'description' => __( 'Leave blank to use the global default set in BD Local Market Settings.', 'bd-local-market' ),
			'value'       => get_post_meta( $post->ID, '_bdlm_delivery_time', true ),
		) );

		// Badge type: taka vs percent.
		woocommerce_wp_select( array(
			'id'          => '_bdlm_badge_type',
			'label'       => __( 'Discount Badge Type', 'bd-local-market' ),
			'desc_tip'    => true,
			'description' => __( 'Controls whether the sale badge shows "৳X OFF" or "X% OFF".', 'bd-local-market' ),
			'value'       => get_post_meta( $post->ID, '_bdlm_badge_type', true ) ?: 'percent',
			'options'     => array(
				'percent' => __( 'Percentage (e.g. 20% OFF)', 'bd-local-market' ),
				'taka'    => __( 'Taka amount (e.g. ৳30 OFF)', 'bd-local-market' ),
			),
		) );

		echo '</div>';
	}

	/**
	 * Save all BD Local Market product meta fields.
	 *
	 * @param int $post_id Product post ID.
	 */
	public function save_product_meta_fields( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$fields = array(
			'_bdlm_unit'          => 'sanitize_text_field',
			'_bdlm_min_qty'       => 'sanitize_text_field',
			'_bdlm_delivery_time' => 'sanitize_text_field',
			'_bdlm_badge_type'    => 'sanitize_text_field',
		);

		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) );
				update_post_meta( $post_id, $key, $value );
			}
		}
		// phpcs:enable
	}
}
