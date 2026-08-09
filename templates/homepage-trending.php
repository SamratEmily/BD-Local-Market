<?php
/**
 * Template: Homepage Hot & Trending.
 * Used by [bdlm_trending] shortcode.
 *
 * @package BD_Local_Market
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="bdlm-section bdlm-trending-section" aria-labelledby="bdlm-trend-heading">
	<div class="bdlm-section-header">
		<h2 class="bdlm-section-title" id="bdlm-trend-heading">
			<?php echo esc_html( $atts['title'] ); ?>
		</h2>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) . '?orderby=popularity' ); ?>" class="bdlm-section-link">
			<?php esc_html_e( 'See all →', 'bd-local-market' ); ?>
		</a>
	</div>
	<div class="bdlm-products-scroll-wrap">
		<?php echo $products_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
