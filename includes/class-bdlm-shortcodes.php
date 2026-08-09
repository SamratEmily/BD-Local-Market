<?php
/**
 * BDLM Shortcodes — Homepage section shortcodes and grid helper functions.
 *
 * Shortcodes provided:
 * - [bd_product_grid category="slug" limit="8" columns="4"]
 * - [bd_recommended_products limit="12" title="Recommended for you"]
 * - [bd_deals_section title="Weekday Deals!!!" limit="8" show_countdown="yes"]
 * - [bd_hot_trending title="Hot & Trending Right Now 🔥" limit="8"]
 * - [bd_featured_finds title="Today’s Featured Finds" limit="8"]
 * - [bd_category_highlight category="fruits-and-vegetables" title="Fresh Fruits & Vegetables" limit="6"]
 * - [bd_promo_banner]
 *
 * Helper function:
 * - bd_local_render_homepage_section( $args )
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// Global Helper Function for Custom Homepage Sections
// ============================================================================

/**
 * Helper function to render a custom homepage section programmatically or in templates.
 *
 * @param array $args Section arguments.
 * @return string HTML output.
 */
function bd_local_render_homepage_section( $args = array() ) {
	$defaults = array(
		'title'          => '',
		'subtitle'       => '',
		'section_type'   => 'grid', // 'grid', 'deals', 'trending', 'recommended', 'featured', 'category'
		'category'       => '',
		'limit'          => 8,
		'columns'        => 4,
		'orderby'        => 'popularity',
		'order'          => 'DESC',
		'on_sale'        => false,
		'featured'       => false,
		'ids'            => '',
		'badge_type'     => 'percent',
		'show_countdown' => false,
		'end_time'       => '',
		'show_more_url'  => '',
		'show_more_text' => __( 'View all →', 'bd-local-market' ),
		'container_class'=> '',
	);

	$args = wp_parse_args( $args, $defaults );

	// Build query args
	$query_args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => absint( $args['limit'] ),
		'ignore_sticky_posts' => 1,
		'fields'              => 'ids',
	);

	// Presets based on section_type
	switch ( $args['section_type'] ) {
		case 'deals':
			$args['on_sale'] = true;
			$query_args['meta_key'] = '_sale_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = 'ASC';
			break;

		case 'trending':
		case 'recommended':
			$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = 'DESC';
			break;

		case 'featured':
			$args['featured'] = true;
			break;
	}

	// Custom orderby overrides if provided explicitly
	if ( ! empty( $args['orderby'] ) && ! in_array( $args['section_type'], array( 'deals', 'trending', 'recommended' ), true ) ) {
		if ( 'popularity' === $args['orderby'] ) {
			$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value_num';
		} else {
			$query_args['orderby'] = sanitize_key( $args['orderby'] );
		}
		$query_args['order'] = sanitize_key( strtoupper( $args['order'] ) );
	}

	// Filter by IDs
	if ( ! empty( $args['ids'] ) ) {
		$ids = array_map( 'absint', explode( ',', $args['ids'] ) );
		$query_args['post__in']       = $ids;
		$query_args['orderby']        = 'post__in';
		$query_args['posts_per_page'] = count( $ids );
	} else {
		$tax_query = array();

		if ( ! empty( $args['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $args['category'] ) ),
			);
		}

		if ( ! empty( $args['featured'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
			);
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( ! empty( $args['on_sale'] ) ) {
			$on_sale_ids = wc_get_product_ids_on_sale();
			if ( empty( $on_sale_ids ) ) {
				return ''; // No products on sale.
			}
			$query_args['post__in'] = $on_sale_ids;
		}
	}

	// Standard WC visibility filter
	$query_args['meta_query'] = WC()->query->get_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		return '';
	}

	$columns_class = 'bdlm-product-grid--cols-' . max( 2, min( 6, absint( $args['columns'] ) ) );
	$deal_end_attr = '';

	if ( $args['show_countdown'] ) {
		$end_time = $args['end_time'];
		if ( empty( $end_time ) ) {
			$general_settings = get_option( 'bdlm_general_settings', array() );
			$end_time         = $general_settings['deal_end_time'] ?? '';
		}
		if ( ! empty( $end_time ) ) {
			$deal_end_attr = ' data-deal-end="' . esc_attr( date( 'c', strtotime( $end_time ) ) ) . '"';
		}
	}

	ob_start();
	?>
	<section class="bdlm-section bdlm-homepage-section <?php echo esc_attr( $args['container_class'] ); ?><?php echo $args['show_countdown'] ? ' bdlm-deals-section' : ''; ?>"<?php echo $deal_end_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( ! empty( $args['title'] ) || $args['show_countdown'] ) : ?>
			<div class="bdlm-section-header">
				<div class="bdlm-section-header__left">
					<?php if ( ! empty( $args['title'] ) ) : ?>
						<h2 class="bdlm-section-title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>

					<?php if ( ! empty( $args['subtitle'] ) ) : ?>
						<p class="bdlm-section-subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>

				<div class="bdlm-section-header__right">
					<?php if ( $args['show_countdown'] ) : ?>
						<div class="bdlm-countdown" aria-label="<?php esc_attr_e( 'Deal countdown timer', 'bd-local-market' ); ?>">
							<span class="bdlm-countdown-label"><?php esc_html_e( 'Ends in:', 'bd-local-market' ); ?></span>
							<div class="bdlm-countdown-timer">
								<span class="bdlm-cd-box"><strong id="bdlm-cd-hours">00</strong><small><?php esc_html_e( 'HRS', 'bd-local-market' ); ?></small></span>
								<span class="bdlm-cd-colon">:</span>
								<span class="bdlm-cd-box"><strong id="bdlm-cd-mins">00</strong><small><?php esc_html_e( 'MIN', 'bd-local-market' ); ?></small></span>
								<span class="bdlm-cd-colon">:</span>
								<span class="bdlm-cd-box"><strong id="bdlm-cd-secs">00</strong><small><?php esc_html_e( 'SEC', 'bd-local-market' ); ?></small></span>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $args['show_more_url'] ) ) : ?>
						<a href="<?php echo esc_url( $args['show_more_url'] ); ?>" class="bdlm-section-link">
							<?php echo esc_html( $args['show_more_text'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="bdlm-product-grid <?php echo esc_attr( $columns_class ); ?>" role="list">
			<?php
			foreach ( $query->posts as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}
				echo '<div class="bdlm-product-grid__item" role="listitem">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo bd_local_product_card( $product, array( 'badge_type' => $args['badge_type'] ) );
				echo '</div>';
			}
			?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

// ============================================================================
// Class: BDLM_Shortcodes
// ============================================================================

/**
 * Class BDLM_Shortcodes
 */
class BDLM_Shortcodes {

	/**
	 * Constructor — register shortcodes.
	 */
	public function __construct() {
		// Canonical grid shortcode
		add_shortcode( 'bd_product_grid', array( $this, 'render_product_grid' ) );

		// Specialized homepage section shortcodes
		add_shortcode( 'bd_recommended_products', array( $this, 'render_recommended' ) );
		add_shortcode( 'bd_deals_section',        array( $this, 'render_deals' ) );
		add_shortcode( 'bd_hot_trending',         array( $this, 'render_trending' ) );
		add_shortcode( 'bd_featured_finds',       array( $this, 'render_featured' ) );
		add_shortcode( 'bd_category_highlight',   array( $this, 'render_category_highlight' ) );
		add_shortcode( 'bd_promo_banner',         array( $this, 'render_promo_banner' ) );

		// Shortcode aliases for backward compatibility
		add_shortcode( 'bdlm_recommended', array( $this, 'render_recommended' ) );
		add_shortcode( 'bdlm_deals',       array( $this, 'render_deals' ) );
		add_shortcode( 'bdlm_trending',    array( $this, 'render_trending' ) );
		add_shortcode( 'bdlm_featured',    array( $this, 'render_featured' ) );
	}

	/**
	 * [bd_product_grid] shortcode.
	 */
	public function render_product_grid( $atts ) {
		$atts = shortcode_atts(
			array(
				'category'   => '',
				'limit'      => 8,
				'columns'    => 4,
				'orderby'    => 'popularity',
				'order'      => 'DESC',
				'on_sale'    => 0,
				'featured'   => 0,
				'ids'        => '',
				'badge_type' => 'percent',
				'title'      => '',
				'show_more'  => '',
			),
			$atts,
			'bd_product_grid'
		);

		return bd_local_render_homepage_section( array(
			'title'         => $atts['title'],
			'category'      => $atts['category'],
			'limit'         => $atts['limit'],
			'columns'       => $atts['columns'],
			'orderby'       => $atts['orderby'],
			'order'         => $atts['order'],
			'on_sale'       => (bool) $atts['on_sale'],
			'featured'      => (bool) $atts['featured'],
			'ids'           => $atts['ids'],
			'badge_type'    => $atts['badge_type'],
			'show_more_url' => $atts['show_more'],
		) );
	}

	/**
	 * [bd_recommended_products limit="12" title="Recommended for you"]
	 */
	public function render_recommended( $atts ) {
		$hp = (array) get_option( 'bdlm_homepage_settings', array() );

		$atts = shortcode_atts(
			array(
				'limit'    => $hp['recommended_limit'] ?? 12,
				'title'    => $hp['recommended_title'] ?? __( 'Recommended for you', 'bd-local-market' ),
				'columns'  => 4,
				'category' => '',
			),
			$atts,
			'bd_recommended_products'
		);

		return bd_local_render_homepage_section( array(
			'section_type' => 'recommended',
			'title'        => $atts['title'],
			'limit'        => $atts['limit'],
			'columns'      => $atts['columns'],
			'category'     => $atts['category'],
		) );
	}

	/**
	 * [bd_deals_section title="Weekday Deals!!!" limit="8" show_countdown="yes"]
	 */
	public function render_deals( $atts ) {
		$hp = (array) get_option( 'bdlm_homepage_settings', array() );

		$atts = shortcode_atts(
			array(
				'limit'          => $hp['deals_limit'] ?? 8,
				'title'          => $hp['deals_title'] ?? __( 'Weekday Deals!!!', 'bd-local-market' ),
				'show_countdown' => 'yes',
				'columns'        => 4,
				'category'       => '',
			),
			$atts,
			'bd_deals_section'
		);

		return bd_local_render_homepage_section( array(
			'section_type'   => 'deals',
			'title'          => $atts['title'],
			'limit'          => $atts['limit'],
			'columns'        => $atts['columns'],
			'category'       => $atts['category'],
			'show_countdown' => ( 'yes' === strtolower( $atts['show_countdown'] ) ),
		) );
	}

	/**
	 * [bd_hot_trending title="Hot & Trending Right Now 🔥" limit="8"]
	 */
	public function render_trending( $atts ) {
		$hp = (array) get_option( 'bdlm_homepage_settings', array() );

		$atts = shortcode_atts(
			array(
				'limit'    => $hp['trending_limit'] ?? 8,
				'title'    => $hp['trending_title'] ?? __( 'Hot & Trending Right Now 🔥', 'bd-local-market' ),
				'columns'  => 4,
				'category' => '',
			),
			$atts,
			'bd_hot_trending'
		);

		return bd_local_render_homepage_section( array(
			'section_type' => 'trending',
			'title'        => $atts['title'],
			'limit'        => $atts['limit'],
			'columns'      => $atts['columns'],
			'category'     => $atts['category'],
		) );
	}

	/**
	 * [bd_featured_finds title="Today’s Featured Finds" limit="8"]
	 */
	public function render_featured( $atts ) {
		$hp = (array) get_option( 'bdlm_homepage_settings', array() );

		$atts = shortcode_atts(
			array(
				'limit'   => $hp['featured_limit'] ?? 8,
				'title'   => $hp['featured_title'] ?? __( 'Today’s Featured Finds', 'bd-local-market' ),
				'columns' => 4,
			),
			$atts,
			'bd_featured_finds'
		);

		return bd_local_render_homepage_section( array(
			'section_type' => 'featured',
			'title'        => $atts['title'],
			'limit'        => $atts['limit'],
			'columns'      => $atts['columns'],
		) );
	}

	/**
	 * [bd_category_highlight category="fruits-and-vegetables" title="Fresh Fruits & Vegetables" limit="6"]
	 */
	public function render_category_highlight( $atts ) {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'title'    => '',
				'limit'    => 6,
				'columns'  => 3,
			),
			$atts,
			'bd_category_highlight'
		);

		// If title is omitted, use category name
		if ( empty( $atts['title'] ) && ! empty( $atts['category'] ) ) {
			$term = get_term_by( 'slug', $atts['category'], 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$atts['title'] = $term->name;
			}
		}

		$show_more = ! empty( $atts['category'] ) ? get_term_link( $atts['category'], 'product_cat' ) : '';
		if ( is_wp_error( $show_more ) ) {
			$show_more = '';
		}

		return bd_local_render_homepage_section( array(
			'section_type'  => 'grid',
			'title'         => $atts['title'],
			'category'      => $atts['category'],
			'limit'         => $atts['limit'],
			'columns'       => $atts['columns'],
			'show_more_url' => $show_more,
		) );
	}

	/**
	 * [bd_promo_banner]
	 * Renders the top 4 benefit cards (Delivery, Free Shipping, Fresh Quality, COD).
	 */
	public function render_promo_banner() {
		$gen = (array) get_option( 'bdlm_general_settings', array() );
		$free_ship_text = ! empty( $gen['free_shipping_threshold_text'] )
			? $gen['free_shipping_threshold_text']
			: __( 'Free shipping over ৳1500', 'bd-local-market' );

		$delivery_text = ! empty( $gen['delivery_time_text'] )
			? $gen['delivery_time_text']
			: __( '60 Mins Delivery', 'bd-local-market' );

		$promos = array(
			array(
				'icon'  => '⚡',
				'title' => $delivery_text,
				'desc'  => __( 'Express grocery delivery to your door', 'bd-local-market' ),
			),
			array(
				'icon'  => '🚚',
				'title' => __( 'Free Shipping', 'bd-local-market' ),
				'desc'  => $free_ship_text,
			),
			array(
				'icon'  => '🥦',
				'title' => __( '100% Fresh Guaranteed', 'bd-local-market' ),
				'desc'  => __( 'Directly sourced from trusted local farmers', 'bd-local-market' ),
			),
			array(
				'icon'  => '💵',
				'title' => __( 'Cash on Delivery', 'bd-local-market' ),
				'desc'  => __( 'Pay easily when your order arrives', 'bd-local-market' ),
			),
		);

		ob_start();
		?>
		<div class="bdlm-promo-banner" role="region" aria-label="<?php esc_attr_e( 'Store Benefits', 'bd-local-market' ); ?>">
			<div class="bdlm-promo-grid">
				<?php foreach ( $promos as $item ) : ?>
					<div class="bdlm-promo-card">
						<div class="bdlm-promo-card__icon" aria-hidden="true"><?php echo esc_html( $item['icon'] ); ?></div>
						<div class="bdlm-promo-card__content">
							<h4 class="bdlm-promo-card__title"><?php echo esc_html( $item['title'] ); ?></h4>
							<p class="bdlm-promo-card__desc"><?php echo esc_html( $item['desc'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
