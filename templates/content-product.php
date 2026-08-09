<?php
/**
 * Template Override: WooCommerce Content Product Loop Item.
 * Replaces standard WooCommerce / Astra loop item content with BD Local Market product card.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

// Ensure visibility
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'bdlm-product-grid__item', $product ); ?>>
	<?php
	// Render single clean Shwapno-style product card
	echo bd_local_product_card( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</li>
