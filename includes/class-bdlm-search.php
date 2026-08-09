<?php
/**
 * BDLM Search — AJAX live search handler, search query expansion, and [bd_search] shortcode.
 *
 * Features:
 * - Extends product search to look in: Title, SKU, Short Description, Categories, Tags.
 * - Shortcode: [bd_search placeholder="..." button_text="Search"] for Astra header/pages.
 * - AJAX live search suggestions returning image, title, category, price, and sale badge.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Search
 */
class BDLM_Search {

	/**
	 * Constructor — register hooks and shortcode.
	 */
	public function __construct() {
		// AJAX handlers
		add_action( 'wp_ajax_bdlm_search',        array( $this, 'handle_search' ) );
		add_action( 'wp_ajax_nopriv_bdlm_search', array( $this, 'handle_search' ) );

		// Render overlay container in footer
		add_action( 'wp_footer', array( $this, 'render_search_overlay' ) );

		// Shortcode [bd_search]
		add_shortcode( 'bd_search', array( $this, 'render_search_shortcode' ) );

		// Modify standard WordPress product search query to include SKU, excerpt, categories, tags
		add_filter( 'posts_clauses', array( $this, 'enhance_search_query' ), 20, 2 );
	}

	// =========================================================================
	// Shortcode: [bd_search]
	// =========================================================================

	/**
	 * [bd_search] shortcode.
	 * Usage: [bd_search placeholder="Search for groceries, vegetables, fruits..." button_text="Search"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_search_shortcode( $atts ) {
		$gen = (array) get_option( 'bdlm_general_settings', array() );

		$atts = shortcode_atts(
			array(
				'placeholder' => $gen['search_placeholder'] ?? __( 'Search for groceries, vegetables, fruits...', 'bd-local-market' ),
				'button_text' => __( 'Search', 'bd-local-market' ),
				'class'       => '',
			),
			$atts,
			'bd_search'
		);

		ob_start();
		?>
		<div class="bdlm-search-bar-wrap <?php echo esc_attr( $atts['class'] ); ?>">
			<form role="search" method="get" class="bdlm-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="bdlm-search-input-wrap">
					<span class="bdlm-search-icon" aria-hidden="true">🔍</span>
					<input type="search"
						class="search-field bdlm-search-input"
						placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
						value="<?php echo get_search_query(); ?>"
						name="s"
						title="<?php esc_attr_e( 'Search for:', 'bd-local-market' ); ?>"
						autocomplete="off"
						aria-label="<?php esc_attr_e( 'Search products', 'bd-local-market' ); ?>" />
					<input type="hidden" name="post_type" value="product" />
				</div>
				<button type="submit" class="bdlm-search-submit button">
					<?php echo esc_html( $atts['button_text'] ); ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	// =========================================================================
	// Query Enhancements (Title, SKU, Excerpt, Category, Tag)
	// =========================================================================

	/**
	 * Modify WP_Query clauses to include SKU, excerpt, category, and tag terms in product searches.
	 *
	 * @param array    $clauses Query clauses.
	 * @param WP_Query $wp_query Query object.
	 * @return array Modified clauses.
	 */
	public function enhance_search_query( $clauses, $wp_query ) {
		global $wpdb;

		if ( is_admin() || ! $wp_query->is_main_query() || ! $wp_query->is_search() ) {
			return $clauses;
		}

		if ( 'product' !== $wp_query->get( 'post_type' ) && ! is_post_type_archive( 'product' ) ) {
			return $clauses;
		}

		$search_term = $wp_query->get( 's' );
		if ( empty( $search_term ) ) {
			return $clauses;
		}

		$term = $wpdb->esc_like( sanitize_text_field( $search_term ) );
		$like = '%' . $term . '%';

		// Join postmeta for SKU
		if ( false === strpos( $clauses['join'], 'bdlm_pm' ) ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} bdlm_pm ON ({$wpdb->posts}.ID = bdlm_pm.post_id AND bdlm_pm.meta_key = '_sku') ";
		}

		// Join term relationships & terms for category/tag search
		if ( false === strpos( $clauses['join'], 'bdlm_tr' ) ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} bdlm_tr ON ({$wpdb->posts}.ID = bdlm_tr.object_id) ";
			$clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} bdlm_tt ON (bdlm_tr.term_taxonomy_id = bdlm_tt.term_taxonomy_id AND bdlm_tt.taxonomy IN ('product_cat', 'product_tag')) ";
			$clauses['join'] .= " LEFT JOIN {$wpdb->terms} bdlm_t ON (bdlm_tt.term_id = bdlm_t.term_id) ";
		}

		// Expand WHERE clause
		$custom_where  = $wpdb->prepare(
			" OR (bdlm_pm.meta_value LIKE %s) OR ({$wpdb->posts}.post_excerpt LIKE %s) OR (bdlm_t.name LIKE %s)",
			$like,
			$like,
			$like
		);

		// Insert inside the post_title / post_content check
		if ( preg_match( '/\({$wpdb->posts}\.post_title\s+LIKE\s+[^)]+\)/i', $clauses['where'] ) ) {
			$clauses['where'] = preg_replace(
				'/(\({$wpdb->posts}\.post_title\s+LIKE\s+[^)]+\))/i',
				'($1 ' . $custom_where . ')',
				$clauses['where']
			);
		} else {
			$clauses['where'] .= $wpdb->prepare(
				" AND (({$wpdb->posts}.post_title LIKE %s) OR ({$wpdb->posts}.post_content LIKE %s) {$custom_where})",
				$like,
				$like
			);
		}

		$clauses['distinct'] = 'DISTINCT';

		return $clauses;
	}

	// =========================================================================
	// AJAX Live Search Handler
	// =========================================================================

	/**
	 * Handle AJAX live search request.
	 */
	public function handle_search() {
		// Verify nonce
		if ( ! check_ajax_referer( 'bdlm_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'bd-local-market' ) ), 403 );
			return;
		}

		$gen_settings = (array) get_option( 'bdlm_general_settings', array() );
		$limit        = isset( $gen_settings['search_results_limit'] ) ? absint( $gen_settings['search_results_limit'] ) : 8;
		$query        = isset( $_GET['query'] ) ? sanitize_text_field( wp_unslash( $_GET['query'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( strlen( $query ) < 2 ) {
			wp_send_json_success( array( 'results' => array() ) );
			return;
		}

		// Check transient cache
		$cache_key = 'bdlm_search_' . md5( $query . $limit );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			wp_send_json_success( array( 'results' => $cached ) );
			return;
		}

		// Query products matching title, SKU, excerpt, category, tag
		$product_ids = $this->query_matching_product_ids( $query, $limit );

		$results = array();
		if ( ! empty( $product_ids ) ) {
			foreach ( $product_ids as $pid ) {
				$product = wc_get_product( $pid );
				if ( ! $product || ! $product->is_visible() ) {
					continue;
				}

				// Thumbnail
				$thumb_id  = $product->get_image_id();
				$thumb_url = $thumb_id
					? wp_get_attachment_image_url( $thumb_id, 'thumbnail' )
					: wc_placeholder_img_src( 'thumbnail' );

				// Category name
				$terms    = get_the_terms( $product->get_id(), 'product_cat' );
				$cat_name = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';

				$results[] = array(
					'id'       => $product->get_id(),
					'title'    => $product->get_name(),
					'url'      => get_permalink( $product->get_id() ),
					'price'    => wp_strip_all_tags( $product->get_price_html() ),
					'thumb'    => $thumb_url,
					'category' => $cat_name,
					'on_sale'  => $product->is_on_sale(),
					'sku'      => $product->get_sku(),
				);
			}
		}

		// Cache for 5 minutes
		set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Execute SQL query searching across title, SKU, short description, categories, and tags.
	 *
	 * @param string $search Search query.
	 * @param int    $limit Max results.
	 * @return int[] Product IDs.
	 */
	private function query_matching_product_ids( $search, $limit = 8 ) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $search ) . '%';

		$sql = $wpdb->prepare(
			"SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_sku')
			LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
			LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy IN ('product_cat', 'product_tag'))
			LEFT JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
			WHERE p.post_type = 'product'
			  AND p.post_status = 'publish'
			  AND (
				  p.post_title LIKE %s
				  OR p.post_excerpt LIKE %s
				  OR pm.meta_value LIKE %s
				  OR t.name LIKE %s
			  )
			LIMIT %d",
			$like,
			$like,
			$like,
			$like,
			$limit
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'absint', $wpdb->get_col( $sql ) );
	}

	// =========================================================================
	// Render Overlay Container
	// =========================================================================

	/**
	 * Render the live search dropdown container in the footer.
	 */
	public function render_search_overlay() {
		$gen = (array) get_option( 'bdlm_general_settings', array() );
		if ( empty( $gen['enable_live_search'] ) ) {
			return;
		}
		?>
		<div id="bdlm-search-dropdown" class="bdlm-search-dropdown" role="listbox" aria-label="<?php esc_attr_e( 'Search suggestions', 'bd-local-market' ); ?>" hidden>
			<div id="bdlm-search-results" class="bdlm-search-results"></div>
			<a href="#" id="bdlm-search-view-all" class="bdlm-search-view-all" hidden>
				<?php esc_html_e( 'View all results →', 'bd-local-market' ); ?>
			</a>
		</div>
		<?php
	}
}
