<?php
/**
 * Template: Homepage Recommended Products.
 * Used by [bdlm_recommended] shortcode.
 *
 * Variables:
 * @var string $products_html Rendered WC product loop HTML.
 * @var array  $atts          Shortcode attributes.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="bdlm-section bdlm-recommended-section" aria-labelledby="bdlm-rec-heading">
	<div class="bdlm-section-header">
		<h2 class="bdlm-section-title" id="bdlm-rec-heading">
			<span class="bdlm-section-icon">⭐</span>
			<?php echo esc_html( $atts['title'] ); ?>
		</h2>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bdlm-section-link">
			<?php esc_html_e( 'See all →', 'bd-local-market' ); ?>
		</a>
	</div>
	<div class="bdlm-products-scroll-wrap">
		<?php echo $products_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
