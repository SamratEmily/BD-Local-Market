<?php
/**
 * Template: Shwapno-style Product Card
 *
 * Loaded by bd_local_product_card( $product ) and the [bd_product_grid] shortcode.
 * Also replaces the WooCommerce loop card on shop/category archive pages.
 *
 * Available variables:
 * @var WC_Product $product         The product object.
 * @var string     $currency        Currency symbol (e.g. ৳).
 * @var string     $delivery_text   Delivery time badge text.
 * @var string     $unit_label      Unit label (e.g. "Per 1 kg", "Per Piece").
 * @var string     $min_qty_note    Minimum quantity note (e.g. "Min. 500g").
 * @var string     $badge_type      Badge style: 'taka' | 'percent'.
 * @var string     $badge_style     CSS variant: 'pill' | 'square' | 'ribbon'.
 * @var bool       $show_delivery   Whether to show the delivery badge.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_id    = $product->get_id();
$product_url   = get_permalink( $product_id );
$product_name  = $product->get_name();
$is_on_sale    = $product->is_on_sale();
$regular_price = (float) $product->get_regular_price();
$sale_price    = (float) $product->get_sale_price();
$in_stock      = $product->is_in_stock();
$is_variable   = $product instanceof WC_Product_Variable;

// ---- Discount badge values ----
$discount_taka    = 0;
$discount_percent = 0;
if ( $is_on_sale && $regular_price > 0 && $sale_price > 0 ) {
	$discount_taka    = $regular_price - $sale_price;
	$discount_percent = round( ( $discount_taka / $regular_price ) * 100 );
}

// ---- Image ----
$image_id  = $product->get_image_id();
$image_src = $image_id
	? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
	: wc_placeholder_img_src( 'woocommerce_thumbnail' );
$image_alt = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : $product_name;
$image_alt = $image_alt ?: $product_name;

// ---- Category breadcrumb (first category) ----
$terms    = get_the_terms( $product_id, 'product_cat' );
$cat_name = '';
if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
	foreach ( $terms as $term ) {
		if ( get_option( 'default_product_cat' ) != $term->term_id ) {
			$cat_name = $term->name;
			break;
		}
	}
}

// ---- Add-to-cart data ----
$add_to_cart_text    = esc_attr( apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Add to Bag', 'bd-local-market' ), $product ) );
$add_to_cart_url     = esc_url( $product->add_to_cart_url() );
$add_to_cart_classes = implode( ' ', array_filter( array(
	'button',
	'bdlm-add-to-bag',
	'product_type_' . $product->get_type(),
	$product->is_purchasable() && $in_stock ? 'add_to_cart_button' : '',
	$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $in_stock ? 'ajax_add_to_cart' : '',
) ) );
?>
<div class="bdlm-product-card<?php echo $is_on_sale ? ' bdlm-product-card--on-sale' : ''; ?><?php echo ! $in_stock ? ' bdlm-product-card--out-of-stock' : ''; ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>">

	<!-- ===================== IMAGE AREA ===================== -->
	<div class="bdlm-product-card__image-wrap">
		<a href="<?php echo esc_url( $product_url ); ?>"
			class="bdlm-product-card__image-link"
			tabindex="-1"
			aria-hidden="true">
			<img
				class="bdlm-product-card__image"
				src="<?php echo esc_url( $image_src ); ?>"
				alt="<?php echo esc_attr( $image_alt ); ?>"
				loading="lazy"
				decoding="async"
				width="300"
				height="300" />
		</a>

		<?php if ( $is_on_sale && ( $discount_taka > 0 || $discount_percent > 0 ) ) : ?>
			<?php if ( 'taka' === $badge_type ) : ?>
				<span class="bdlm-discount-badge bdlm-discount-badge--taka bdlm-discount-badge--<?php echo esc_attr( $badge_style ); ?>"
					aria-label="<?php printf( esc_attr__( '%s৳ off', 'bd-local-market' ), number_format( $discount_taka, 0 ) ); ?>">
					<?php echo esc_html( $currency ); ?><?php echo esc_html( number_format( $discount_taka, 0 ) ); ?> <?php esc_html_e( 'OFF', 'bd-local-market' ); ?>
				</span>
			<?php else : ?>
				<span class="bdlm-discount-badge bdlm-discount-badge--percent bdlm-discount-badge--<?php echo esc_attr( $badge_style ); ?>"
					aria-label="<?php printf( esc_attr__( '%d%% off', 'bd-local-market' ), $discount_percent ); ?>">
					<?php echo esc_html( $discount_percent ); ?>% <?php esc_html_e( 'OFF', 'bd-local-market' ); ?>
				</span>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! $in_stock ) : ?>
			<span class="bdlm-out-of-stock-overlay">
				<?php esc_html_e( 'Out of Stock', 'bd-local-market' ); ?>
			</span>
		<?php endif; ?>
	</div><!-- .bdlm-product-card__image-wrap -->

	<!-- ===================== BODY ===================== -->
	<div class="bdlm-product-card__body">

		<?php if ( $cat_name ) : ?>
			<span class="bdlm-product-card__category"><?php echo esc_html( $cat_name ); ?></span>
		<?php endif; ?>

		<h3 class="bdlm-product-card__title">
			<a href="<?php echo esc_url( $product_url ); ?>"
				class="bdlm-product-card__title-link"
				title="<?php echo esc_attr( $product_name ); ?>">
				<?php echo esc_html( $product_name ); ?>
			</a>
		</h3>

		<?php if ( ! empty( $unit_label ) ) : ?>
			<span class="bdlm-product-card__unit"><?php echo esc_html( $unit_label ); ?></span>
		<?php endif; ?>

		<?php if ( ! empty( $min_qty_note ) ) : ?>
			<span class="bdlm-product-card__min-qty">
				<?php
				/* translators: minimum quantity note */
				printf( esc_html__( 'Min. %s', 'bd-local-market' ), esc_html( $min_qty_note ) );
				?>
			</span>
		<?php endif; ?>

		<!-- Price block -->
		<div class="bdlm-product-card__price-wrap">
			<?php if ( $is_on_sale && $sale_price > 0 ) : ?>
				<span class="bdlm-product-card__sale-price">
					<?php echo esc_html( $currency ); ?><?php echo esc_html( number_format( $sale_price, 0 ) ); ?>
				</span>
				<span class="bdlm-product-card__regular-price">
					<del><?php echo esc_html( $currency ); ?><?php echo esc_html( number_format( $regular_price, 0 ) ); ?></del>
				</span>
			<?php elseif ( $is_variable ) : ?>
				<?php /* For variable products, use WC's price HTML */ ?>
				<span class="bdlm-product-card__sale-price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
				</span>
			<?php else : ?>
				<span class="bdlm-product-card__sale-price">
					<?php echo esc_html( $currency ); ?><?php echo esc_html( number_format( (float) $product->get_price(), 0 ) ); ?>
				</span>
			<?php endif; ?>
		</div><!-- .bdlm-product-card__price-wrap -->

		<?php if ( $show_delivery && ! empty( $delivery_text ) ) : ?>
			<span class="bdlm-product-card__delivery">
				<span class="bdlm-product-card__delivery-icon" aria-hidden="true">🚚</span>
				<?php echo esc_html__( 'Delivery', 'bd-local-market' ); ?> <?php echo esc_html( $delivery_text ); ?>
			</span>
		<?php endif; ?>

	</div><!-- .bdlm-product-card__body -->

	<!-- ===================== FOOTER ===================== -->
	<div class="bdlm-product-card__footer">
		<?php if ( $in_stock ) : ?>
			<?php if ( $is_variable ) : ?>
				<a href="<?php echo esc_url( $product_url ); ?>"
					class="bdlm-add-to-bag bdlm-add-to-bag--select button"
					aria-label="<?php printf( esc_attr__( 'Select options for %s', 'bd-local-market' ), esc_attr( $product_name ) ); ?>">
					<span class="bdlm-add-to-bag__icon" aria-hidden="true">🛍</span>
					<?php esc_html_e( 'Select Options', 'bd-local-market' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo $add_to_cart_url; ?>"
					data-product_id="<?php echo esc_attr( $product_id ); ?>"
					data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
					data-quantity="1"
					class="<?php echo esc_attr( $add_to_cart_classes ); ?>"
					aria-label="<?php printf( esc_attr__( 'Add %s to your bag', 'bd-local-market' ), esc_attr( $product_name ) ); ?>"
					rel="nofollow">
					<span class="bdlm-add-to-bag__icon" aria-hidden="true">🛍</span>
					<span class="bdlm-add-to-bag__text"><?php echo esc_html( $add_to_cart_text ); ?></span>
				</a>
			<?php endif; ?>
		<?php else : ?>
			<span class="bdlm-add-to-bag bdlm-add-to-bag--disabled" aria-disabled="true">
				<?php esc_html_e( 'Out of Stock', 'bd-local-market' ); ?>
			</span>
		<?php endif; ?>
	</div><!-- .bdlm-product-card__footer -->

</div><!-- .bdlm-product-card -->
