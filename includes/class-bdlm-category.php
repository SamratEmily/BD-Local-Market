<?php
/**
 * BDLM Category — Category browsing & page enhancements.
 *
 * Provides:
 * - Sub-category navigation chips with thumbnails on archive pages.
 * - Category description banner.
 * - Hierarchical category menu shortcode [bd_category_menu].
 * - Default sort by popularity on category pages.
 * - Full-width layout on category pages (Astra hook).
 * - SEO Breadcrumb schema.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BDLM_Category
 */
class BDLM_Category {

	/**
	 * Constructor — register hooks and shortcodes.
	 */
	public function __construct() {
		// Sub-category chips above product grid.
		add_action( 'woocommerce_before_shop_loop', array( $this, 'render_subcategory_chips' ), 5 );

		// Category banner/description below chips.
		add_action( 'woocommerce_before_shop_loop', array( $this, 'render_category_banner' ), 8 );

		// Default sort by popularity.
		add_filter( 'woocommerce_default_catalog_orderby', array( $this, 'default_sort_by_popularity' ) );

		// Astra full-width layout.
		add_filter( 'body_class', array( $this, 'add_archive_layout_class' ) );

		// Breadcrumb schema.
		add_action( 'wp_head', array( $this, 'output_category_schema' ) );

		// Shortcode [bd_category_menu]
		add_shortcode( 'bd_category_menu', array( $this, 'render_category_menu_shortcode' ) );
	}

	// =========================================================================
	// Shortcode: [bd_category_menu]
	// =========================================================================

	/**
	 * Render hierarchical category menu shortcode.
	 * Usage: [bd_category_menu title="All Categories" depth="3" show_count="yes"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_category_menu_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'      => __( 'All Categories', 'bd-local-market' ),
				'depth'      => 3,
				'show_count' => 'yes',
				'style'      => 'accordion', // 'accordion' | 'flat'
			),
			$atts,
			'bd_category_menu'
		);

		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'hide_empty' => true,
			'exclude'    => get_option( 'default_product_cat' ),
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		// Build parent-child hierarchy map
		$hierarchy = array();
		foreach ( $terms as $term ) {
			$parent_id = (int) $term->parent;
			$hierarchy[ $parent_id ][] = $term;
		}

		$show_count = ( 'yes' === strtolower( $atts['show_count'] ) );
		$max_depth  = max( 1, min( 5, absint( $atts['depth'] ) ) );

		ob_start();
		?>
		<nav class="bdlm-category-menu bdlm-category-menu--<?php echo esc_attr( $atts['style'] ); ?>" aria-label="<?php echo esc_attr( $atts['title'] ); ?>">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h3 class="bdlm-category-menu__header">
					<span class="bdlm-category-menu__icon" aria-hidden="true">📂</span>
					<?php echo esc_html( $atts['title'] ); ?>
				</h3>
			<?php endif; ?>

			<div class="bdlm-category-menu__tree">
				<?php echo $this->render_menu_tree( $hierarchy, 0, 1, $max_depth, $show_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Recursive helper to output HTML tree of categories.
	 *
	 * @param array $hierarchy Parent-to-children map.
	 * @param int   $parent_id Current parent ID.
	 * @param int   $current_depth Current recursion depth.
	 * @param int   $max_depth Maximum allowed depth.
	 * @param bool  $show_count Whether to display product count.
	 * @return string HTML output.
	 */
	private function render_menu_tree( $hierarchy, $parent_id, $current_depth, $max_depth, $show_count ) {
		if ( empty( $hierarchy[ $parent_id ] ) || $current_depth > $max_depth ) {
			return '';
		}

		$current_term_id = is_tax( 'product_cat' ) ? get_queried_object_id() : 0;
		$output          = '<ul class="bdlm-cat-level bdlm-cat-level-' . esc_attr( $current_depth ) . '">';

		foreach ( $hierarchy[ $parent_id ] as $term ) {
			$has_children = ! empty( $hierarchy[ $term->term_id ] ) && ( $current_depth < $max_depth );
			$is_current   = ( $term->term_id === $current_term_id );

			// Thumbnail
			$thumb_id  = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';

			$count_html = $show_count ? '<span class="bdlm-cat-count">(' . esc_html( $term->count ) . ')</span>' : '';
			$term_url   = get_term_link( $term );

			$item_classes = array( 'bdlm-cat-item' );
			if ( $is_current ) {
				$item_classes[] = 'bdlm-cat-item--active';
			}
			if ( $has_children ) {
				$item_classes[] = 'bdlm-cat-item--has-children';
			}

			$output .= '<li class="' . esc_attr( implode( ' ', $item_classes ) ) . '">';

			if ( $has_children ) {
				$is_open = ( $is_current || $this->term_is_ancestor_of_current( $term->term_id, $hierarchy ) ) ? ' open' : '';
				$output .= '<details' . $is_open . '>';
				$output .= '<summary class="bdlm-cat-summary">';
				if ( $thumb_src ) {
					$output .= '<img src="' . esc_url( $thumb_src ) . '" alt="" class="bdlm-cat-icon" loading="lazy" width="20" height="20" />';
				}
				$output .= '<a href="' . esc_url( $term_url ) . '" class="bdlm-cat-link">' . esc_html( $term->name ) . '</a> ' . $count_html;
				$output .= '</summary>';

				// Subtree
				$output .= $this->render_menu_tree( $hierarchy, $term->term_id, $current_depth + 1, $max_depth, $show_count );
				$output .= '</details>';
			} else {
				$output .= '<div class="bdlm-cat-row">';
				if ( $thumb_src ) {
					$output .= '<img src="' . esc_url( $thumb_src ) . '" alt="" class="bdlm-cat-icon" loading="lazy" width="20" height="20" />';
				}
				$output .= '<a href="' . esc_url( $term_url ) . '" class="bdlm-cat-link">' . esc_html( $term->name ) . '</a> ' . $count_html;
				$output .= '</div>';
			}

			$output .= '</li>';
		}

		$output .= '</ul>';
		return $output;
	}

	/**
	 * Check if a given term ID is an ancestor of the currently queried product_cat.
	 */
	private function term_is_ancestor_of_current( $term_id, $hierarchy ) {
		if ( ! is_tax( 'product_cat' ) ) {
			return false;
		}
		$current_id = get_queried_object_id();
		$ancestors  = get_ancestors( $current_id, 'product_cat' );
		return in_array( $term_id, $ancestors, true );
	}

	// =========================================================================
	// Sub-category Chips with Icons on Archive Pages
	// =========================================================================

	/**
	 * Render sub-category navigation chips on product archive pages.
	 */
	public function render_subcategory_chips() {
		if ( ! is_product_taxonomy() || ! is_tax( 'product_cat' ) ) {
			return;
		}

		$current_term = get_queried_object();
		if ( ! $current_term ) {
			return;
		}

		// Get children first
		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $current_term->term_id,
				'hide_empty' => true,
				'orderby'    => 'menu_order',
			)
		);

		if ( is_wp_error( $children ) || empty( $children ) ) {
			// Try siblings
			$children = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => $current_term->parent,
					'hide_empty' => true,
					'orderby'    => 'menu_order',
				)
			);
		}

		if ( is_wp_error( $children ) || count( $children ) <= 1 ) {
			return;
		}

		?>
		<nav class="bdlm-subcategory-nav" aria-label="<?php esc_attr_e( 'Subcategories', 'bd-local-market' ); ?>">
			<div class="bdlm-subcategory-chips">
				<?php foreach ( $children as $child ) : ?>
					<?php
					$is_active = ( $child->term_id === $current_term->term_id ) ? 'bdlm-chip--active' : '';
					$chip_url  = get_term_link( $child );
					$thumb_id  = get_term_meta( $child->term_id, 'thumbnail_id', true );
					$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
					?>
					<a href="<?php echo esc_url( $chip_url ); ?>"
						class="bdlm-chip <?php echo esc_attr( $is_active ); ?>"
						<?php echo $is_active ? 'aria-current="page"' : ''; ?>>
						<?php if ( $thumb_src ) : ?>
							<img src="<?php echo esc_url( $thumb_src ); ?>" alt="" class="bdlm-chip-img" loading="lazy" width="24" height="24" />
						<?php endif; ?>
						<span class="bdlm-chip-title"><?php echo esc_html( $child->name ); ?></span>
						<span class="bdlm-chip-count">(<?php echo esc_html( $child->count ); ?>)</span>
					</a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
	}

	/**
	 * Render category description banner.
	 */
	public function render_category_banner() {
		if ( ! is_tax( 'product_cat' ) ) {
			return;
		}

		$current_term = get_queried_object();
		if ( ! $current_term || empty( $current_term->description ) ) {
			return;
		}

		?>
		<div class="bdlm-category-banner">
			<div class="bdlm-category-description"><?php echo wp_kses_post( wpautop( $current_term->description ) ); ?></div>
		</div>
		<?php
	}

	/**
	 * Default sort by popularity on category and shop pages.
	 */
	public function default_sort_by_popularity( $sort ) {
		if ( is_tax( 'product_cat' ) || is_shop() ) {
			return 'popularity';
		}
		return $sort;
	}

	/**
	 * Add full-width layout body class.
	 */
	public function add_archive_layout_class( $classes ) {
		if ( is_product_taxonomy() || is_shop() ) {
			$classes[] = 'ast-full-width-layout';
			$classes[] = 'bdlm-archive-page';
		}
		return $classes;
	}

	/**
	 * Output BreadcrumbList schema.
	 */
	public function output_category_schema() {
		if ( ! is_tax( 'product_cat' ) ) {
			return;
		}

		$current_term = get_queried_object();
		if ( ! $current_term ) {
			return;
		}

		$items   = array();
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		);
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => __( 'Shop', 'bd-local-market' ),
			'item'     => wc_get_page_permalink( 'shop' ),
		);
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 3,
			'name'     => $current_term->name,
			'item'     => get_term_link( $current_term ),
		);

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
