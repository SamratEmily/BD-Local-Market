<?php
/**
 * BDLM Homepage — Shortcodes for all homepage sections.
 *
 * Shortcodes:
 * [bdlm_hero]
 * [bdlm_categories]
 * [bdlm_recommended]
 * [bdlm_deals]
 * [bdlm_trending]
 * [bdlm_featured]
 * [bdlm_brands]
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Homepage
 */
class BDLM_Homepage {

	/**
	 * Constructor — register all shortcodes.
	 */
	public function __construct() {
		$shortcodes = array(
			'bdlm_hero'        => 'render_hero',
			'bdlm_categories'  => 'render_categories',
			'bdlm_recommended' => 'render_recommended',
			'bdlm_deals'       => 'render_deals',
			'bdlm_trending'    => 'render_trending',
			'bdlm_featured'    => 'render_featured',
			'bdlm_brands'      => 'render_brands',
		);

		foreach ( $shortcodes as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}
	}

	// =========================================================================
	// Helper: Run a WC product query and return HTML output.
	// =========================================================================

	/**
	 * Run a WooCommerce product query and return the products loop HTML.
	 *
	 * @param array $args WP_Query args.
	 * @return string HTML output.
	 */
	private function get_products_html( $args ) {
		$defaults = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 8,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => 1,
		);

		$query_args = wp_parse_args( $args, $defaults );

		// WC visibility filtering.
		$query_args['meta_query'] = WC()->query->get_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_args['tax_query']  = WC()->query->get_tax_query();  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		$products = new WP_Query( $query_args );

		if ( ! $products->have_posts() ) {
			return '';
		}

		ob_start();

		woocommerce_product_loop_start();

		while ( $products->have_posts() ) {
			$products->the_post();
			wc_get_template_part( 'content', 'product' );
		}

		woocommerce_product_loop_end();

		wp_reset_postdata();

		return ob_get_clean();
	}

	// =========================================================================
	// HERO
	// =========================================================================

	/**
	 * [bdlm_hero] shortcode.
	 *
	 * Attributes: title, subtitle, cta_text, cta_url, bg_color (optional).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_hero( $atts ) {
		$settings = BDLM_Core::get_instance()->settings;

		$atts = shortcode_atts(
			array(
				'title'    => $settings['hero_title'] ?? __( 'Fresh Groceries, Delivered Fast', 'bd-local-market' ),
				'subtitle' => $settings['hero_subtitle'] ?? __( 'Shop fresh from our curated selection — delivered to your door.', 'bd-local-market' ),
				'cta_text' => $settings['hero_cta_text'] ?? __( 'Shop Now', 'bd-local-market' ),
				'cta_url'  => $settings['hero_cta_url'] ?? wc_get_page_permalink( 'shop' ),
				'bg_color' => '',
			),
			$atts,
			'bdlm_hero'
		);

		ob_start();
		include BDLM_PATH . 'templates/homepage-hero.php';
		return ob_get_clean();
	}

	// =========================================================================
	// CATEGORIES GRID
	// =========================================================================

	/**
	 * [bdlm_categories] shortcode.
	 *
	 * Attributes: limit (default 12), parent (default 0 for top-level).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_categories( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'  => 12,
				'parent' => 0,
				'title'  => __( 'Shop by Category', 'bd-local-market' ),
			),
			$atts,
			'bdlm_categories'
		);

		$hp = (array) get_option( 'bdlm_homepage_settings', array() );
		$featured_slugs = ! empty( $hp['featured_categories'] )
			? array_map( 'trim', explode( ',', $hp['featured_categories'] ) )
			: array();

		$term_args = array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'hide_empty' => true,
			'parent'     => (int) $atts['parent'],
			'number'     => (int) $atts['limit'],
			'exclude'    => get_option( 'default_product_cat' ),
		);

		if ( ! empty( $featured_slugs ) ) {
			$term_args['slug']    = $featured_slugs;
			$term_args['orderby'] = 'include';
			unset( $term_args['parent'] );
		}

		$categories = get_terms( $term_args );

		if ( is_wp_error( $categories ) || empty( $categories ) ) {
			return '';
		}

		ob_start();
		include BDLM_PATH . 'templates/homepage-categories.php';
		return ob_get_clean();
	}

	// =========================================================================
	// RECOMMENDED
	// =========================================================================

	/**
	 * [bdlm_recommended] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_recommended( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'category' => '',
				'orderby'  => 'popularity',
				'title'    => __( 'Recommended For You', 'bd-local-market' ),
			),
			$atts,
			'bdlm_recommended'
		);

		$query_args = array(
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
				),
			);
		}

		$products_html = $this->get_products_html( $query_args );

		if ( empty( $products_html ) ) {
			return '';
		}

		ob_start();
		include BDLM_PATH . 'templates/homepage-recommended.php';
		return ob_get_clean();
	}

	// =========================================================================
	// DEALS WITH COUNTDOWN
	// =========================================================================

	/**
	 * [bdlm_deals] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_deals( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 8,
				'title' => __( "Today's Best Deals", 'bd-local-market' ),
			),
			$atts,
			'bdlm_deals'
		);

		$query_args = array(
			'posts_per_page' => (int) $atts['limit'],
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => '_sale_price',
					'value'   => '',
					'compare' => '!=',
				),
				array(
					'key'     => '_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		);

		$products_html = $this->get_products_html( $query_args );

		if ( empty( $products_html ) ) {
			return '';
		}

		$deal_end_time = BDLM_Core::get_instance()->get_setting( 'deal_end_time', '' );

		ob_start();
		include BDLM_PATH . 'templates/homepage-deals.php';
		return ob_get_clean();
	}

	// =========================================================================
	// HOT & TRENDING
	// =========================================================================

	/**
	 * [bdlm_trending] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_trending( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'category' => '',
				'title'    => __( 'Hot & Trending 🔥', 'bd-local-market' ),
			),
			$atts,
			'bdlm_trending'
		);

		$query_args = array(
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'DESC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
				),
			);
		}

		$products_html = $this->get_products_html( $query_args );

		if ( empty( $products_html ) ) {
			return '';
		}

		ob_start();
		include BDLM_PATH . 'templates/homepage-trending.php';
		return ob_get_clean();
	}

	// =========================================================================
	// FEATURED FINDS
	// =========================================================================

	/**
	 * [bdlm_featured] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_featured( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 8,
				'title' => __( 'Featured Finds ✨', 'bd-local-market' ),
			),
			$atts,
			'bdlm_featured'
		);

		$query_args = array(
			'posts_per_page' => (int) $atts['limit'],
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
				),
			),
		);

		$products_html = $this->get_products_html( $query_args );

		if ( empty( $products_html ) ) {
			return '';
		}

		ob_start();
		include BDLM_PATH . 'templates/homepage-featured.php';
		return ob_get_clean();
	}

	// =========================================================================
	// BRAND / CATEGORY HIGHLIGHT STRIP
	// =========================================================================

	/**
	 * [bdlm_brands] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_brands( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'  => 10,
				'parent' => 0,
				'title'  => __( 'Shop by Brand', 'bd-local-market' ),
			),
			$atts,
			'bdlm_brands'
		);

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => true,
				'number'     => (int) $atts['limit'],
				'parent'     => (int) $atts['parent'],
			)
		);

		if ( is_wp_error( $categories ) || empty( $categories ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="bdlm-section bdlm-brands-section">
			<div class="bdlm-section-header">
				<h2 class="bdlm-section-title"><?php echo esc_html( $atts['title'] ); ?></h2>
			</div>
			<div class="bdlm-brands-strip">
				<?php foreach ( $categories as $cat ) : ?>
					<?php
					$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
					$image_url    = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : wc_placeholder_img_src();
					$cat_url      = get_term_link( $cat );
					?>
					<a href="<?php echo esc_url( $cat_url ); ?>" class="bdlm-brand-chip">
						<img src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $cat->name ); ?>"
							loading="lazy" />
						<span><?php echo esc_html( $cat->name ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
