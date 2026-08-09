<?php
/**
 * Template: Shwapno-Style Homepage Hero Section.
 * Used by [bdlm_hero] shortcode.
 *
 * Structure:
 * - Left Column: Vertical Category Navigation Sidebar
 * - Right Top: Promotional Main Banner / Slider
 * - Right Bottom: 5 Featured Quick Category Cards with Yellow Pills
 * - Bottom Bar: 4 Benefit Cards (60 Mins Delivery, Authorized Products, Support, Flexible Payments)
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fetch top 11 parent categories for left sidebar
$side_categories = get_terms( array(
	'taxonomy'   => 'product_cat',
	'parent'     => 0,
	'hide_empty' => false,
	'number'     => 11,
	'orderby'    => 'menu_order',
	'order'      => 'ASC',
	'exclude'    => get_option( 'default_product_cat' ),
) );

// Fallback icon map
$cat_icons = array(
	'food'               => '🍱',
	'baby-food-care'     => '🍼',
	'diapers'            => '👶',
	'home-cleaning'      => '🧹',
	'pet-care'           => '🐾',
	'beauty-health'      => '💄',
	'fashion-lifestyle'  => '👗',
	'home-kitchen'       => '🏠',
	'stationeries'       => '✏️',
	'toys-sports'        => '🧸',
	'gadget'             => '📱',
	'fruits-vegetables'  => '🥦',
	'dairy-eggs'         => '🥚',
	'beverages'          => '🧃',
	'meat-fish'          => '🥩',
);

// Featured quick categories for bottom 5 cards (can be customized via settings or fallback terms)
$quick_cats = get_terms( array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => false,
	'number'     => 5,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'exclude'    => get_option( 'default_product_cat' ),
) );
?>

<div class="bdlm-shwapno-hero-wrapper">
	<div class="bdlm-shwapno-hero-main">

		<!-- ================= 1. LEFT CATEGORY SIDEBAR ================= -->
		<aside class="bdlm-shwapno-sidebar" aria-label="<?php esc_attr_e( 'Shop by Category', 'bd-local-market' ); ?>">
			<div class="bdlm-shwapno-sidebar__header">
				<span class="bdlm-shwapno-sidebar__menu-icon" aria-hidden="true">☰</span>
				<span><?php esc_html_e( 'SHOP BY CATEGORY', 'bd-local-market' ); ?></span>
			</div>
			<ul class="bdlm-shwapno-sidebar__list">
				<?php if ( ! is_wp_error( $side_categories ) && ! empty( $side_categories ) ) : ?>
					<?php foreach ( $side_categories as $cat ) : ?>
						<?php
						$icon    = $cat_icons[ $cat->slug ] ?? '🛒';
						$cat_url = get_term_link( $cat );
						?>
						<li class="bdlm-shwapno-sidebar__item">
							<a href="<?php echo esc_url( $cat_url ); ?>" class="bdlm-shwapno-sidebar__link">
								<span class="bdlm-shwapno-sidebar__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
								<span class="bdlm-shwapno-sidebar__name"><?php echo esc_html( $cat->name ); ?></span>
								<span class="bdlm-shwapno-sidebar__arrow" aria-hidden="true">›</span>
							</a>
						</li>
					<?php endforeach; ?>
				<?php else : ?>
					<!-- Fallback default list if no WC categories created yet -->
					<?php
					$fallback_list = array(
						'Food'               => '🍱',
						'Baby Food & Care'   => '🍼',
						'Diapers'            => '👶',
						'Home Cleaning'      => '🧹',
						'Pet Care'           => '🐾',
						'Beauty & Health'    => '💄',
						'Fashion & Lifestyle'=> '👗',
						'Home & Kitchen'     => '🏠',
						'Stationeries'       => '✏️',
						'Toys & Sports'      => '🧸',
						'Gadget'             => '📱',
					);
					foreach ( $fallback_list as $name => $icon ) :
						?>
						<li class="bdlm-shwapno-sidebar__item">
							<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bdlm-shwapno-sidebar__link">
								<span class="bdlm-shwapno-sidebar__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
								<span class="bdlm-shwapno-sidebar__name"><?php echo esc_html( $name ); ?></span>
								<span class="bdlm-shwapno-sidebar__arrow" aria-hidden="true">›</span>
							</a>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</aside>

		<!-- ================= 2. RIGHT COLUMN (BANNER + QUICK CARDS) ================= -->
		<div class="bdlm-shwapno-content-col">

			<!-- MAIN PROMO BANNER / SLIDER -->
			<div class="bdlm-shwapno-banner" role="region" aria-label="<?php esc_attr_e( 'Promotional Banner', 'bd-local-market' ); ?>">
				<button class="bdlm-shwapno-banner__nav bdlm-shwapno-banner__nav--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'bd-local-market' ); ?>">‹</button>
				
				<div class="bdlm-shwapno-banner__slide">
					<div class="bdlm-shwapno-banner__visuals">
						<div class="bdlm-shwapno-banner__products-showcase">
							<span class="bdlm-banner-prod-item bdlm-prod-1">🧴</span>
							<span class="bdlm-banner-prod-item bdlm-prod-2">🧃</span>
							<span class="bdlm-banner-prod-item bdlm-prod-3">🫙</span>
							<span class="bdlm-banner-prod-item bdlm-prod-4">🥛</span>
							<span class="bdlm-banner-prod-item bdlm-prod-5">🧼</span>
						</div>
					</div>
					<div class="bdlm-shwapno-banner__promo-badge">
						<h2><?php echo esc_html( $atts['title'] ); ?></h2>
						<p><?php echo esc_html( $atts['subtitle'] ); ?></p>
						<div class="bdlm-shwapno-banner__discount-text">
							<span><?php esc_html_e( 'Get Up To', 'bd-local-market' ); ?></span>
							<strong>50% OFF</strong>
						</div>
						<a href="<?php echo esc_url( $atts['cta_url'] ); ?>" class="bdlm-shwapno-banner__cta-btn">
							<?php echo esc_html( $atts['cta_text'] ); ?>
						</a>
					</div>
				</div>

				<button class="bdlm-shwapno-banner__nav bdlm-shwapno-banner__nav--next" aria-label="<?php esc_attr_e( 'Next slide', 'bd-local-market' ); ?>">›</button>

				<!-- Slider indicator dots -->
				<div class="bdlm-shwapno-banner__dots">
					<span class="bdlm-dot active"></span>
					<span class="bdlm-dot"></span>
					<span class="bdlm-dot"></span>
					<span class="bdlm-dot"></span>
					<span class="bdlm-dot"></span>
				</div>
			</div>

			<!-- 5 FEATURED QUICK CATEGORY CARDS WITH YELLOW PILL BUTTONS -->
			<div class="bdlm-shwapno-quick-cats" role="region" aria-label="<?php esc_attr_e( 'Featured Categories', 'bd-local-market' ); ?>">
				<?php if ( ! is_wp_error( $quick_cats ) && ! empty( $quick_cats ) ) : ?>
					<?php foreach ( $quick_cats as $cat ) : ?>
						<?php
						$thumb_id  = get_term_meta( $cat->term_id, 'thumbnail_id', true );
						$img_url   = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : wc_placeholder_img_src( 'medium' );
						$cat_link  = get_term_link( $cat );
						?>
						<a href="<?php echo esc_url( $cat_link ); ?>" class="bdlm-shwapno-quick-card">
							<div class="bdlm-shwapno-quick-card__image-wrap">
								<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" loading="lazy" />
							</div>
							<div class="bdlm-shwapno-quick-card__badge">
								<?php echo esc_html( $cat->name ); ?>
							</div>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<!-- Fallback default 5 cards if no WC category images set yet -->
					<?php
					$fallback_cards = array(
						array( 'name' => 'Eggs',        'bg' => '#fef3d6', 'emoji' => '🥚' ),
						array( 'name' => 'Tea',         'bg' => '#e8f5e9', 'emoji' => '🍵' ),
						array( 'name' => 'Soft Drinks', 'bg' => '#e1f5fe', 'emoji' => '🥤' ),
						array( 'name' => 'Frozen',      'bg' => '#fff3e0', 'emoji' => '🍟' ),
						array( 'name' => 'Coffee',      'bg' => '#efebe9', 'emoji' => '☕' ),
					);
					foreach ( $fallback_cards as $card ) :
						?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="bdlm-shwapno-quick-card">
							<div class="bdlm-shwapno-quick-card__image-wrap" style="background:<?php echo esc_attr( $card['bg'] ); ?>;">
								<span class="bdlm-quick-card-emoji"><?php echo esc_html( $card['emoji'] ); ?></span>
							</div>
							<div class="bdlm-shwapno-quick-card__badge">
								<?php echo esc_html( $card['name'] ); ?>
							</div>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		</div><!-- .bdlm-shwapno-content-col -->

	</div><!-- .bdlm-shwapno-hero-main -->

	<!-- ================= 3. BOTTOM BENEFIT CARDS ================= -->
	<div class="bdlm-shwapno-benefits-wrap">
		<div class="bdlm-shwapno-benefit-card">
			<div class="bdlm-shwapno-benefit-card__icon bdlm-icon-red">⚡</div>
			<div class="bdlm-shwapno-benefit-card__text">
				<strong><?php esc_html_e( '60 Mins Delivery', 'bd-local-market' ); ?></strong>
				<span><?php esc_html_e( 'Free shipping over 1500Tk', 'bd-local-market' ); ?></span>
			</div>
		</div>

		<div class="bdlm-shwapno-benefit-card">
			<div class="bdlm-shwapno-benefit-card__icon bdlm-icon-red">🛡️</div>
			<div class="bdlm-shwapno-benefit-card__text">
				<strong><?php esc_html_e( 'Authorized Products', 'bd-local-market' ); ?></strong>
				<span><?php esc_html_e( 'within 30 days for an exchange', 'bd-local-market' ); ?></span>
			</div>
		</div>

		<div class="bdlm-shwapno-benefit-card">
			<div class="bdlm-shwapno-benefit-card__icon bdlm-icon-red">🎧</div>
			<div class="bdlm-shwapno-benefit-card__text">
				<strong><?php esc_html_e( 'Customer Service Support', 'bd-local-market' ); ?></strong>
				<span><?php esc_html_e( '8am to 10pm', 'bd-local-market' ); ?></span>
			</div>
		</div>

		<div class="bdlm-shwapno-benefit-card">
			<div class="bdlm-shwapno-benefit-card__icon bdlm-icon-red">💳</div>
			<div class="bdlm-shwapno-benefit-card__text">
				<strong><?php esc_html_e( 'Flexible Payments', 'bd-local-market' ); ?></strong>
				<span><?php esc_html_e( 'Pay with multiple credit cards', 'bd-local-market' ); ?></span>
			</div>
		</div>
	</div>

</div><!-- .bdlm-shwapno-hero-wrapper -->
