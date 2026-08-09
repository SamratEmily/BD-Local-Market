<?php
/**
 * BDLM Settings — Admin settings page with 4 tabbed sections.
 *
 * Registers settings using the WordPress Settings API.
 * Located at: WooCommerce → BD Local Market Settings
 *
 * Tabs:
 *   - General            (?tab=general)
 *   - Homepage Sections  (?tab=homepage_sections)
 *   - Checkout Fields    (?tab=checkout_fields)
 *   - Product Card Style (?tab=product_card)
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Settings
 */
class BDLM_Settings {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'bd-local-market-settings';

	/**
	 * Available tabs definition.
	 *
	 * @var array
	 */
	private $tabs = array();

	/**
	 * Constructor — register all hooks.
	 */
	public function __construct() {
		$this->tabs = array(
			'general'           => __( 'General',           'bd-local-market' ),
			'homepage_sections' => __( 'Homepage Sections', 'bd-local-market' ),
			'checkout_fields'   => __( 'Checkout Fields',   'bd-local-market' ),
			'product_card'      => __( 'Product Card Style','bd-local-market' ),
		);

		add_action( 'admin_menu',       array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init',       array( $this, 'register_all_settings' ) );
		add_filter( 'plugin_action_links_bd-local-market/bd-local-market.php', array( $this, 'add_settings_link' ) );
	}

	// =========================================================================
	// Admin Menu
	// =========================================================================

	/**
	 * Register the submenu page under WooCommerce.
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'BD Local Market Settings', 'bd-local-market' ),
			__( 'BD Local Market', 'bd-local-market' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	// =========================================================================
	// Settings Registration (WordPress Settings API)
	// =========================================================================

	/**
	 * Register settings, sections, and fields for all tabs.
	 */
	public function register_all_settings() {
		$this->register_general_settings();
		$this->register_homepage_settings();
		$this->register_checkout_settings();
		$this->register_product_card_settings();
	}

	// -------------------------------------------------------------------------
	// TAB 1: General
	// -------------------------------------------------------------------------

	private function register_general_settings() {
		register_setting(
			'bdlm_general',           // Option group (used in settings_fields()).
			'bdlm_general_settings',  // Option name in wp_options.
			array(
				'sanitize_callback' => array( $this, 'sanitize_general' ),
				'default'           => bdlm_default_general_settings(),
			)
		);

		// --- Section: Store Identity ---
		add_settings_section(
			'bdlm_general_store',
			__( 'Store Settings', 'bd-local-market' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Core settings applied site-wide.', 'bd-local-market' ) . '</p>';
			},
			self::PAGE_SLUG . '_general'
		);

		add_settings_field(
			'currency_symbol',
			__( 'Currency Symbol', 'bd-local-market' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG . '_general',
			'bdlm_general_store',
			array(
				'option'      => 'bdlm_general_settings',
				'key'         => 'currency_symbol',
				'default'     => '৳',
				'description' => __( 'Displayed in savings badges and price labels. Default: ৳', 'bd-local-market' ),
				'size'        => 'small-text',
			)
		);

		add_settings_field(
			'delivery_time_text',
			__( 'Default Delivery Time', 'bd-local-market' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG . '_general',
			'bdlm_general_store',
			array(
				'option'      => 'bdlm_general_settings',
				'key'         => 'delivery_time_text',
				'default'     => '1-2 hours',
				'placeholder' => '1-2 hours',
				'description' => __( 'Shown on every product card delivery badge. Can be overridden per product.', 'bd-local-market' ),
			)
		);

		add_settings_field(
			'free_shipping_threshold_text',
			__( 'Free Shipping Notice Text', 'bd-local-market' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG . '_general',
			'bdlm_general_store',
			array(
				'option'      => 'bdlm_general_settings',
				'key'         => 'free_shipping_threshold_text',
				'default'     => 'Minimum order ৳500',
				'description' => __( 'Displayed in the cart and checkout page. Leave blank to hide.', 'bd-local-market' ),
				'size'        => 'large-text',
			)
		);

		// --- Section: Search ---
		add_settings_section(
			'bdlm_general_search',
			__( 'Live Search', 'bd-local-market' ),
			null,
			self::PAGE_SLUG . '_general'
		);

		add_settings_field(
			'enable_live_search',
			__( 'Enable Live Search', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			self::PAGE_SLUG . '_general',
			'bdlm_general_search',
			array(
				'option'      => 'bdlm_general_settings',
				'key'         => 'enable_live_search',
				'label'       => __( 'Show instant product suggestions as the user types', 'bd-local-market' ),
			)
		);

		add_settings_field(
			'search_results_limit',
			__( 'Max Search Results', 'bd-local-market' ),
			array( $this, 'field_number' ),
			self::PAGE_SLUG . '_general',
			'bdlm_general_search',
			array(
				'option'      => 'bdlm_general_settings',
				'key'         => 'search_results_limit',
				'default'     => '8',
				'min'         => 1,
				'max'         => 20,
				'description' => __( 'Number of products shown in the search dropdown (1–20).', 'bd-local-market' ),
			)
		);

		// --- Section: Flash Deals ---
		add_settings_section(
			'bdlm_general_deals',
			__( 'Flash Deals Timer', 'bd-local-market' ),
			null,
			self::PAGE_SLUG . '_general'
		);

		add_settings_field(
			'deal_end_time',
			__( 'Deal End Date & Time', 'bd-local-market' ),
			array( $this, 'field_datetime' ),
			self::PAGE_SLUG . '_general',
			'bdlm_general_deals',
			array(
				'option'      => 'bdlm_general_settings',
				'key'         => 'deal_end_time',
				'description' => __( 'Sets the countdown clock on the [bdlm_deals] section. Leave blank to hide.', 'bd-local-market' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// TAB 2: Homepage Sections
	// -------------------------------------------------------------------------

	private function register_homepage_settings() {
		register_setting(
			'bdlm_homepage',
			'bdlm_homepage_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_homepage' ),
				'default'           => bdlm_default_homepage_settings(),
			)
		);

		$page = self::PAGE_SLUG . '_homepage_sections';

		// --- Section: Hero Banner ---
		add_settings_section( 'bdlm_hero', __( '🎯 Hero Banner', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_hero', 'hero',
			array( 'title' => true, 'limit' => false, 'extra' => array(
				array( 'key' => 'hero_subtitle', 'label' => __( 'Subtitle', 'bd-local-market' ), 'type' => 'textarea' ),
				array( 'key' => 'hero_cta_text', 'label' => __( 'CTA Button Text', 'bd-local-market' ), 'type' => 'text', 'default' => 'Shop Now' ),
				array( 'key' => 'hero_cta_url',  'label' => __( 'CTA Button URL', 'bd-local-market' ),  'type' => 'url' ),
			) )
		);

		// --- Section: Categories ---
		add_settings_section( 'bdlm_categories', __( '📂 Shop by Category', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_categories', 'categories', array(
			'title' => true,
			'limit' => true,
			'extra' => array(
				array(
					'key'         => 'featured_categories',
					'label'       => __( 'Featured Categories (Slugs/IDs)', 'bd-local-market' ),
					'type'        => 'text',
					'description' => __( 'Comma-separated category slugs (e.g. "fruits-and-vegetables, dairy-eggs") to feature. Leave blank for all top categories.', 'bd-local-market' ),
				),
			),
		) );

		// --- Section: Recommended ---
		add_settings_section( 'bdlm_recommended', __( '⭐ Recommended For You', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_recommended', 'recommended', array( 'title' => true, 'limit' => true ) );

		// --- Section: Deals ---
		add_settings_section( 'bdlm_deals', __( '🔥 Flash Deals', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_deals', 'deals', array( 'title' => true, 'limit' => true ) );

		// --- Section: Trending ---
		add_settings_section( 'bdlm_trending', __( '📈 Hot & Trending', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_trending', 'trending', array( 'title' => true, 'limit' => true ) );

		// --- Section: Featured ---
		add_settings_section( 'bdlm_featured', __( '✨ Featured Finds', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_featured', 'featured', array( 'title' => true, 'limit' => true ) );

		// --- Section: Brands ---
		add_settings_section( 'bdlm_brands', __( '🏷️ Shop by Brand', 'bd-local-market' ), null, $page );
		$this->add_section_fields( $page, 'bdlm_brands', 'brands', array( 'title' => true, 'limit' => true ) );
	}

	/**
	 * Helper: add Enable + Title + Limit fields for a homepage section.
	 *
	 * @param string $page    Admin page slug.
	 * @param string $section Settings section ID.
	 * @param string $prefix  Field key prefix (e.g. 'hero').
	 * @param array  $opts    Options: title (bool), limit (bool), extra (array of extra fields).
	 */
	private function add_section_fields( $page, $section, $prefix, $opts ) {
		// Enable toggle.
		add_settings_field(
			"enable_{$prefix}",
			__( 'Enable Section', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array(
				'option' => 'bdlm_homepage_settings',
				'key'    => "enable_{$prefix}",
				'label'  => __( 'Show this section on the homepage', 'bd-local-market' ),
			)
		);

		// Title.
		if ( ! empty( $opts['title'] ) ) {
			add_settings_field(
				"{$prefix}_title",
				__( 'Section Title', 'bd-local-market' ),
				array( $this, 'field_text' ),
				$page,
				$section,
				array(
					'option' => 'bdlm_homepage_settings',
					'key'    => "{$prefix}_title",
				)
			);
		}

		// Product limit.
		if ( ! empty( $opts['limit'] ) ) {
			add_settings_field(
				"{$prefix}_limit",
				__( 'Number of Products', 'bd-local-market' ),
				array( $this, 'field_number' ),
				$page,
				$section,
				array(
					'option'      => 'bdlm_homepage_settings',
					'key'         => "{$prefix}_limit",
					'min'         => 1,
					'max'         => 40,
					'description' => __( 'Max products to display in this section.', 'bd-local-market' ),
				)
			);
		}

		// Extra fields (hero-specific).
		if ( ! empty( $opts['extra'] ) ) {
			foreach ( $opts['extra'] as $extra ) {
				$type   = $extra['type'] ?? 'text';
				$method = 'field_' . $type;
				if ( ! method_exists( $this, $method ) ) {
					$method = 'field_text';
				}
				add_settings_field(
					$extra['key'],
					$extra['label'],
					array( $this, $method ),
					$page,
					$section,
					array(
						'option'  => 'bdlm_homepage_settings',
						'key'     => $extra['key'],
						'default' => $extra['default'] ?? '',
					)
				);
			}
		}
	}

	// -------------------------------------------------------------------------
	// TAB 3: Checkout Fields
	// -------------------------------------------------------------------------

	private function register_checkout_settings() {
		register_setting(
			'bdlm_checkout',
			'bdlm_checkout_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_checkout' ),
				'default'           => bdlm_default_checkout_settings(),
			)
		);

		$page = self::PAGE_SLUG . '_checkout_fields';

		// --- Section: Guest Checkout ---
		add_settings_section(
			'bdlm_checkout_guest',
			__( 'Guest Checkout', 'bd-local-market' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Settings for customers checking out without an account.', 'bd-local-market' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'enable_guest_notice',
			__( 'Show Guest Notice', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_checkout_guest',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'enable_guest_notice',
				'label' => __( 'Display a friendly notice above the checkout form for guest users', 'bd-local-market' ) )
		);

		add_settings_field(
			'guest_notice_text',
			__( 'Guest Notice Text', 'bd-local-market' ),
			array( $this, 'field_textarea' ),
			$page, 'bdlm_checkout_guest',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'guest_notice_text',
				'description' => __( 'The message shown in the green notice box above the checkout form.', 'bd-local-market' ) )
		);

		add_settings_field(
			'address_required',
			__( 'Require Delivery Address', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_checkout_guest',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'address_required',
				'label' => __( 'Make address fields required (disabled = address is optional for guests)', 'bd-local-market' ) )
		);

		// --- Section: Phone Validation ---
		add_settings_section(
			'bdlm_checkout_phone',
			__( 'Phone Number', 'bd-local-market' ),
			null, $page
		);

		add_settings_field(
			'require_phone',
			__( 'Require Phone Number', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_checkout_phone',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'require_phone',
				'label' => __( 'Phone number is required at checkout', 'bd-local-market' ) )
		);

		add_settings_field(
			'enable_phone_validation',
			__( 'BD Phone Format Validation', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_checkout_phone',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'enable_phone_validation',
				'label' => __( 'Validate number as Bangladeshi mobile format (01XXXXXXXXX / +8801XXXXXXXXX)', 'bd-local-market' ) )
		);

		add_settings_field(
			'phone_placeholder',
			__( 'Phone Field Placeholder', 'bd-local-market' ),
			array( $this, 'field_text' ),
			$page, 'bdlm_checkout_phone',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'phone_placeholder',
				'default' => '01XXXXXXXXX', 'size' => 'regular-text' )
		);

		// --- Section: Delivery Charges ---
		add_settings_section(
			'bdlm_checkout_delivery',
			__( '🚚 Delivery Charges (Lohagara Area)', 'bd-local-market' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Configure delivery charges for Inside and Outside Lohagara areas.', 'bd-local-market' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'fee_inside_lohagara',
			__( 'Inside Lohagara Charge (৳)', 'bd-local-market' ),
			array( $this, 'field_number' ),
			$page, 'bdlm_checkout_delivery',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'fee_inside_lohagara',
				'default' => '30', 'min' => 0, 'max' => 1000,
				'description' => __( 'Delivery charge in Taka for deliveries inside Lohagara.', 'bd-local-market' ) )
		);

		add_settings_field(
			'fee_outside_lohagara',
			__( 'Outside Lohagara Charge (৳)', 'bd-local-market' ),
			array( $this, 'field_number' ),
			$page, 'bdlm_checkout_delivery',
			array( 'option' => 'bdlm_checkout_settings', 'key' => 'fee_outside_lohagara',
				'default' => '60', 'min' => 0, 'max' => 1000,
				'description' => __( 'Delivery charge in Taka for deliveries outside Lohagara.', 'bd-local-market' ) )
		);
	}

	// -------------------------------------------------------------------------
	// TAB 4: Product Card Style
	// -------------------------------------------------------------------------

	private function register_product_card_settings() {
		register_setting(
			'bdlm_product_card',
			'bdlm_product_card_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_product_card' ),
				'default'           => bdlm_default_product_card_settings(),
			)
		);

		$page = self::PAGE_SLUG . '_product_card';

		// --- Section: Badges ---
		add_settings_section(
			'bdlm_card_badges',
			__( 'Product Badges', 'bd-local-market' ),
			null, $page
		);

		add_settings_field(
			'show_savings_badge',
			__( 'Show Savings Badge', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_card_badges',
			array( 'option' => 'bdlm_product_card_settings', 'key' => 'show_savings_badge',
				'label' => __( 'Show "–XX%" discount badge on sale products', 'bd-local-market' ) )
		);

		add_settings_field(
			'show_delivery_badge',
			__( 'Show Delivery Badge', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_card_badges',
			array( 'option' => 'bdlm_product_card_settings', 'key' => 'show_delivery_badge',
				'label' => __( 'Show estimated delivery time badge on each card', 'bd-local-market' ) )
		);

		add_settings_field(
			'show_unit_info',
			__( 'Show Unit / Weight Info', 'bd-local-market' ),
			array( $this, 'field_checkbox' ),
			$page, 'bdlm_card_badges',
			array( 'option' => 'bdlm_product_card_settings', 'key' => 'show_unit_info',
				'label' => __( 'Show unit/weight info below product title (e.g. "500g", "1 dozen")', 'bd-local-market' ) )
		);

		add_settings_field(
			'badge_style',
			__( 'Badge Shape Style', 'bd-local-market' ),
			array( $this, 'field_select' ),
			$page, 'bdlm_card_badges',
			array(
				'option'  => 'bdlm_product_card_settings',
				'key'     => 'badge_style',
				'choices' => array(
					'pill'   => __( 'Pill (rounded)', 'bd-local-market' ),
					'square' => __( 'Square (sharp corners)', 'bd-local-market' ),
					'ribbon' => __( 'Ribbon (diagonal)', 'bd-local-market' ),
				),
			)
		);

		// --- Section: Button Text ---
		add_settings_section(
			'bdlm_card_button',
			__( 'Add to Cart Button', 'bd-local-market' ),
			null, $page
		);

		add_settings_field(
			'add_to_bag_text',
			__( '"Add to Cart" Button Label', 'bd-local-market' ),
			array( $this, 'field_text' ),
			$page, 'bdlm_card_button',
			array( 'option' => 'bdlm_product_card_settings', 'key' => 'add_to_bag_text',
				'default' => 'Add to Bag', 'placeholder' => 'Add to Bag' )
		);

		add_settings_field(
			'adding_text',
			__( 'While Adding Label', 'bd-local-market' ),
			array( $this, 'field_text' ),
			$page, 'bdlm_card_button',
			array( 'option' => 'bdlm_product_card_settings', 'key' => 'adding_text',
				'default' => 'Adding…', 'placeholder' => 'Adding…',
				'description' => __( 'Shown while the AJAX add-to-cart request is in progress.', 'bd-local-market' ) )
		);

		add_settings_field(
			'added_text',
			__( 'After Added Label', 'bd-local-market' ),
			array( $this, 'field_text' ),
			$page, 'bdlm_card_button',
			array( 'option' => 'bdlm_product_card_settings', 'key' => 'added_text',
				'default' => '✓ Added', 'placeholder' => '✓ Added',
				'description' => __( 'Shown briefly after a product is successfully added.', 'bd-local-market' ) )
		);
	}

	// =========================================================================
	// Sanitization Callbacks
	// =========================================================================

	/** Sanitize General settings. */
	public function sanitize_general( $input ) {
		$clean = bdlm_default_general_settings();
		$clean['currency_symbol']              = sanitize_text_field( $input['currency_symbol'] ?? '৳' );
		$clean['delivery_time_text']           = sanitize_text_field( $input['delivery_time_text'] ?? '1-2 hours' );
		$clean['free_shipping_threshold_text'] = sanitize_text_field( $input['free_shipping_threshold_text'] ?? '' );
		$clean['enable_live_search']           = ! empty( $input['enable_live_search'] ) ? '1' : '0';
		$clean['search_results_limit']         = min( 20, max( 1, absint( $input['search_results_limit'] ?? 8 ) ) );
		$clean['deal_end_time']                = sanitize_text_field( $input['deal_end_time'] ?? '' );
		return $clean;
	}

	/** Sanitize Homepage Section settings. */
	public function sanitize_homepage( $input ) {
		$clean    = bdlm_default_homepage_settings();
		$sections = array( 'hero', 'categories', 'recommended', 'deals', 'trending', 'featured', 'brands' );

		foreach ( $sections as $s ) {
			$clean[ "enable_{$s}" ] = ! empty( $input[ "enable_{$s}" ] ) ? '1' : '0';
			if ( isset( $input[ "{$s}_title" ] ) ) {
				$clean[ "{$s}_title" ] = sanitize_text_field( $input[ "{$s}_title" ] );
			}
			if ( isset( $input[ "{$s}_limit" ] ) ) {
				$clean[ "{$s}_limit" ] = min( 40, max( 1, absint( $input[ "{$s}_limit" ] ) ) );
			}
		}

		// Hero extras.
		$clean['hero_subtitle']       = sanitize_textarea_field( $input['hero_subtitle'] ?? '' );
		$clean['hero_cta_text']       = sanitize_text_field( $input['hero_cta_text'] ?? 'Shop Now' );
		$clean['hero_cta_url']        = esc_url_raw( $input['hero_cta_url'] ?? '' );
		$clean['featured_categories'] = sanitize_text_field( $input['featured_categories'] ?? '' );

		return $clean;
	}

	/** Sanitize Checkout settings. */
	public function sanitize_checkout( $input ) {
		$clean = bdlm_default_checkout_settings();
		$clean['enable_guest_notice']     = ! empty( $input['enable_guest_notice'] ) ? '1' : '0';
		$clean['enable_phone_validation'] = ! empty( $input['enable_phone_validation'] ) ? '1' : '0';
		$clean['require_phone']           = ! empty( $input['require_phone'] ) ? '1' : '0';
		$clean['address_required']        = ! empty( $input['address_required'] ) ? '1' : '0';
		$clean['phone_placeholder']       = sanitize_text_field( $input['phone_placeholder'] ?? '01XXXXXXXXX' );
		$clean['guest_notice_text']       = sanitize_textarea_field( $input['guest_notice_text'] ?? '' );
		$clean['fee_inside_lohagara']     = absint( $input['fee_inside_lohagara'] ?? 30 );
		$clean['fee_outside_lohagara']    = absint( $input['fee_outside_lohagara'] ?? 60 );
		return $clean;
	}

	/** Sanitize Product Card settings. */
	public function sanitize_product_card( $input ) {
		$clean = bdlm_default_product_card_settings();
		$clean['show_savings_badge']  = ! empty( $input['show_savings_badge'] ) ? '1' : '0';
		$clean['show_delivery_badge'] = ! empty( $input['show_delivery_badge'] ) ? '1' : '0';
		$clean['show_unit_info']      = ! empty( $input['show_unit_info'] ) ? '1' : '0';
		$clean['add_to_bag_text']     = sanitize_text_field( $input['add_to_bag_text'] ?? 'Add to Bag' );
		$clean['adding_text']         = sanitize_text_field( $input['adding_text'] ?? 'Adding…' );
		$clean['added_text']          = sanitize_text_field( $input['added_text'] ?? '✓ Added' );
		$allowed_styles               = array( 'pill', 'square', 'ribbon' );
		$clean['badge_style']         = in_array( $input['badge_style'] ?? 'pill', $allowed_styles, true )
			? $input['badge_style']
			: 'pill';
		return $clean;
	}

	// =========================================================================
	// Reusable Field Renderers
	// =========================================================================

	/**
	 * Get the current value of a setting field.
	 *
	 * @param string $option  Option name.
	 * @param string $key     Sub-key within option array.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	private function get_field_value( $option, $key, $default = '' ) {
		$options = (array) get_option( $option, array() );
		return $options[ $key ] ?? $default;
	}

	/** Render a text input. */
	public function field_text( $args ) {
		$value = $this->get_field_value( $args['option'], $args['key'], $args['default'] ?? '' );
		$size  = esc_attr( $args['size'] ?? 'regular-text' );
		$placeholder = esc_attr( $args['placeholder'] ?? '' );
		printf(
			'<input type="text" id="%1$s" name="%2$s[%3$s]" value="%4$s" class="%5$s" placeholder="%6$s" />',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] ),
			esc_attr( $value ),
			$size,
			$placeholder
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/** Render a URL input. */
	public function field_url( $args ) {
		$value = $this->get_field_value( $args['option'], $args['key'], $args['default'] ?? '' );
		printf(
			'<input type="url" id="%1$s" name="%2$s[%3$s]" value="%4$s" class="regular-text" placeholder="%5$s" />',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] ),
			esc_attr( $value ),
			esc_attr( $args['placeholder'] ?? 'https://' )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/** Render a textarea. */
	public function field_textarea( $args ) {
		$value = $this->get_field_value( $args['option'], $args['key'], $args['default'] ?? '' );
		printf(
			'<textarea id="%1$s" name="%2$s[%3$s]" rows="3" class="large-text">%4$s</textarea>',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] ),
			esc_textarea( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/** Render a checkbox. */
	public function field_checkbox( $args ) {
		$value = $this->get_field_value( $args['option'], $args['key'], '0' );
		printf(
			'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s[%3$s]" value="1" %4$s /> %5$s</label>',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] ),
			checked( '1', $value, false ),
			esc_html( $args['label'] ?? '' )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/** Render a number input. */
	public function field_number( $args ) {
		$value = $this->get_field_value( $args['option'], $args['key'], $args['default'] ?? 8 );
		printf(
			'<input type="number" id="%1$s" name="%2$s[%3$s]" value="%4$s" min="%5$s" max="%6$s" class="small-text" />',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] ),
			esc_attr( $value ),
			esc_attr( $args['min'] ?? 1 ),
			esc_attr( $args['max'] ?? 100 )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/** Render a datetime-local input. */
	public function field_datetime( $args ) {
		$value = $this->get_field_value( $args['option'], $args['key'], '' );
		printf(
			'<input type="datetime-local" id="%1$s" name="%2$s[%3$s]" value="%4$s" class="regular-text" />',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] ),
			esc_attr( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/** Render a select dropdown. */
	public function field_select( $args ) {
		$value   = $this->get_field_value( $args['option'], $args['key'], '' );
		$choices = $args['choices'] ?? array();

		printf(
			'<select id="%1$s" name="%2$s[%3$s]">',
			esc_attr( $args['key'] ),
			esc_attr( $args['option'] ),
			esc_attr( $args['key'] )
		);
		foreach ( $choices as $choice_val => $choice_label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $choice_val ),
				selected( $value, $choice_val, false ),
				esc_html( $choice_label )
			);
		}
		echo '</select>';
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	// =========================================================================
	// Admin Page Renderer
	// =========================================================================

	/**
	 * Render the full settings page with tab navigation.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bd-local-market' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		if ( ! array_key_exists( $active_tab, $this->tabs ) ) {
			$active_tab = 'general';
		}

		// Map tab slugs to option groups and page suffixes.
		$tab_config = array(
			'general'           => array( 'group' => 'bdlm_general',      'page_suffix' => '_general' ),
			'homepage_sections' => array( 'group' => 'bdlm_homepage',     'page_suffix' => '_homepage_sections' ),
			'checkout_fields'   => array( 'group' => 'bdlm_checkout',     'page_suffix' => '_checkout_fields' ),
			'product_card'      => array( 'group' => 'bdlm_product_card', 'page_suffix' => '_product_card' ),
		);

		$current = $tab_config[ $active_tab ];
		$page_id = self::PAGE_SLUG . $current['page_suffix'];
		?>
		<div class="wrap bdlm-admin-settings">

			<?php $this->render_page_header(); ?>

			<nav class="nav-tab-wrapper bdlm-tab-nav" aria-label="<?php esc_attr_e( 'Settings tabs', 'bd-local-market' ); ?>">
				<?php foreach ( $this->tabs as $tab_slug => $tab_label ) : ?>
					<?php
					$tab_url    = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab_slug );
					$is_active  = ( $tab_slug === $active_tab );
					$aria_sel   = $is_active ? ' aria-selected="true"' : '';
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>"
						class="nav-tab<?php echo $is_active ? ' nav-tab-active' : ''; ?>"
						<?php echo $aria_sel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="bdlm-settings-body">
				<?php settings_errors( 'bdlm_settings_errors' ); ?>

				<form method="post" action="options.php" novalidate>
					<?php
					settings_fields( $current['group'] );
					do_settings_sections( $page_id );
					submit_button( __( 'Save Settings', 'bd-local-market' ), 'primary', 'submit', true, array( 'id' => 'bdlm-save-btn' ) );
					?>
				</form>
			</div><!-- .bdlm-settings-body -->

			<?php $this->render_shortcode_reference(); ?>

		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Render the branded page header.
	 */
	private function render_page_header() {
		?>
		<div class="bdlm-admin-header">
			<div class="bdlm-admin-logo">
				<span aria-hidden="true">🛒</span>
				<div>
					<h1><?php esc_html_e( 'BD Local Market Settings', 'bd-local-market' ); ?></h1>
					<p><?php esc_html_e( 'Bangladeshi grocery store enhancements for WooCommerce', 'bd-local-market' ); ?></p>
				</div>
			</div>
			<span class="bdlm-version-badge">
				<?php /* translators: plugin version number */ ?>
				<?php printf( esc_html__( 'v%s', 'bd-local-market' ), esc_html( BDLM_VERSION ) ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * Render the shortcode reference table (collapsed by default on small screens).
	 */
	private function render_shortcode_reference() {
		$shortcodes = array(
			'[bdlm_hero]'              => __( 'Animated hero banner with CTA buttons', 'bd-local-market' ),
			'[bdlm_categories]'        => __( 'Category icon grid — attrs: limit, parent', 'bd-local-market' ),
			'[bdlm_recommended]'       => __( 'Top-selling products — attrs: limit, category', 'bd-local-market' ),
			'[bdlm_deals]'             => __( 'Flash deals with countdown timer — attr: limit', 'bd-local-market' ),
			'[bdlm_trending]'          => __( 'Hot & trending products — attrs: limit, category', 'bd-local-market' ),
			'[bdlm_featured]'          => __( 'WooCommerce featured products — attr: limit', 'bd-local-market' ),
			'[bdlm_brands]'            => __( 'Brand/category strip — attrs: limit, parent', 'bd-local-market' ),
		);
		?>
		<details class="bdlm-shortcode-reference">
			<summary><?php esc_html_e( '📋 Shortcode Reference', 'bd-local-market' ); ?></summary>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Shortcode', 'bd-local-market' ); ?></th>
						<th><?php esc_html_e( 'Description', 'bd-local-market' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $shortcodes as $sc => $desc ) : ?>
						<tr>
							<td><code><?php echo esc_html( $sc ); ?></code></td>
							<td><?php echo esc_html( $desc ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
		<?php
	}

	// =========================================================================
	// Plugin Action Links
	// =========================================================================

	/**
	 * Add a "Settings" quick link on the plugins list page.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url  = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'bd-local-market' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}
}
