<?php
/**
 * Template: Homepage Categories Grid.
 * Used by [bdlm_categories] shortcode.
 *
 * Variables:
 * @var array  $categories WP_Term[] product categories.
 * @var array  $atts       Shortcode attributes.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Category emoji map for visual flair (slug => emoji).
$cat_emojis = array(
	'vegetables'   => '🥦',
	'shabji'       => '🥦',
	'fruits'       => '🍎',
	'fol'          => '🍎',
	'fish'         => '🐟',
	'mach'         => '🐟',
	'meat'         => '🍖',
	'murgir-manso' => '🍗',
	'chicken'      => '🍗',
	'beef'         => '🥩',
	'dairy'        => '🥛',
	'doodh'        => '🥛',
	'eggs'         => '🥚',
	'dim'          => '🥚',
	'rice'         => '🌾',
	'chal'         => '🌾',
	'oil'          => '🫙',
	'tel'          => '🫙',
	'spices'       => '🌶️',
	'masala'       => '🌶️',
	'bread'        => '🍞',
	'roti'         => '🫓',
	'bakery'       => '🧁',
	'snacks'       => '🍿',
	'beverages'    => '🧃',
	'drinks'       => '🧃',
	'cleaning'     => '🧹',
	'personal-care' => '🧴',
	'baby'         => '👶',
	'frozen'       => '🧊',
	'organic'      => '🌿',
);
?>
<section class="bdlm-section bdlm-categories-section" aria-labelledby="bdlm-cats-heading">
	<div class="bdlm-section-header">
		<h2 class="bdlm-section-title" id="bdlm-cats-heading">
			<?php echo esc_html( $atts['title'] ); ?>
		</h2>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bdlm-section-link">
			<?php esc_html_e( 'View all →', 'bd-local-market' ); ?>
		</a>
	</div>

	<div class="bdlm-categories-grid">
		<?php foreach ( $categories as $category ) : ?>
			<?php
			$cat_link        = get_term_link( $category );
			$thumbnail_id    = get_term_meta( $category->term_id, 'thumbnail_id', true );
			$has_image       = ! empty( $thumbnail_id );
			$emoji           = $cat_emojis[ $category->slug ] ?? '🛒';
			?>
			<a href="<?php echo esc_url( $cat_link ); ?>"
				class="bdlm-category-tile"
				title="<?php echo esc_attr( $category->name ); ?>">
				<div class="bdlm-category-tile__image-wrap">
					<?php if ( $has_image ) : ?>
						<img
							src="<?php echo esc_url( wp_get_attachment_url( $thumbnail_id ) ); ?>"
							alt="<?php echo esc_attr( $category->name ); ?>"
							loading="lazy"
							class="bdlm-category-tile__img" />
					<?php else : ?>
						<span class="bdlm-category-tile__emoji" aria-hidden="true"><?php echo esc_html( $emoji ); ?></span>
					<?php endif; ?>
				</div>
				<span class="bdlm-category-tile__name"><?php echo esc_html( $category->name ); ?></span>
				<span class="bdlm-category-tile__count">
					<?php
					printf(
						/* translators: %d: product count */
						esc_html( _n( '%d item', '%d items', $category->count, 'bd-local-market' ) ),
						esc_html( $category->count )
					);
					?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
