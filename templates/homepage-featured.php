<?php
/**
 * Template: Homepage Featured Finds.
 * Used by [bdlm_featured] shortcode.
 *
 * @package BD_Local_Market
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="bdlm-section bdlm-featured-section" aria-labelledby="bdlm-feat-heading">
	<div class="bdlm-section-header">
		<h2 class="bdlm-section-title" id="bdlm-feat-heading">
			<?php echo esc_html( $atts['title'] ); ?>
		</h2>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) . '?orderby=rating' ); ?>" class="bdlm-section-link">
			<?php esc_html_e( 'Explore more →', 'bd-local-market' ); ?>
		</a>
	</div>
	<div class="bdlm-products-scroll-wrap">
		<?php echo $products_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
