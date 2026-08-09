<?php
/**
 * Template: Homepage Flash Deals with Countdown.
 * Used by [bdlm_deals] shortcode.
 *
 * Variables:
 * @var string $products_html  Rendered WC product loop HTML.
 * @var string $deal_end_time  Raw deal end time string (from settings).
 * @var array  $atts           Shortcode attributes.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_countdown = ! empty( $deal_end_time );
?>
<section class="bdlm-section bdlm-deals-section <?php echo $has_countdown ? 'bdlm-deals-section--has-countdown' : ''; ?>"
	aria-labelledby="bdlm-deals-heading"
	<?php if ( $has_countdown ) : ?>
		data-deal-end="<?php echo esc_attr( gmdate( 'c', strtotime( $deal_end_time ) ) ); ?>"
	<?php endif; ?>>

	<div class="bdlm-deals-header">
		<div class="bdlm-deals-header__left">
			<span class="bdlm-deals-fire">🔥</span>
			<h2 class="bdlm-section-title bdlm-section-title--light" id="bdlm-deals-heading">
				<?php echo esc_html( $atts['title'] ); ?>
			</h2>
		</div>

		<?php if ( $has_countdown ) : ?>
		<div class="bdlm-countdown" role="timer" aria-live="polite" aria-label="<?php esc_attr_e( 'Time remaining for deals', 'bd-local-market' ); ?>">
			<span class="bdlm-countdown__label"><?php esc_html_e( 'Ends in:', 'bd-local-market' ); ?></span>
			<div class="bdlm-countdown__display">
				<div class="bdlm-countdown__unit" id="bdlm-countdown-hours">
					<span class="bdlm-countdown__value" id="bdlm-cd-hours">00</span>
					<span class="bdlm-countdown__unit-label"><?php esc_html_e( 'HRS', 'bd-local-market' ); ?></span>
				</div>
				<span class="bdlm-countdown__sep">:</span>
				<div class="bdlm-countdown__unit">
					<span class="bdlm-countdown__value" id="bdlm-cd-mins">00</span>
					<span class="bdlm-countdown__unit-label"><?php esc_html_e( 'MIN', 'bd-local-market' ); ?></span>
				</div>
				<span class="bdlm-countdown__sep">:</span>
				<div class="bdlm-countdown__unit">
					<span class="bdlm-countdown__value" id="bdlm-cd-secs">00</span>
					<span class="bdlm-countdown__unit-label"><?php esc_html_e( 'SEC', 'bd-local-market' ); ?></span>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) . '?orderby=price' ); ?>"
			class="bdlm-btn bdlm-btn--outline-light">
			<?php esc_html_e( 'View all deals →', 'bd-local-market' ); ?>
		</a>
	</div>

	<div class="bdlm-products-scroll-wrap">
		<?php echo $products_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
