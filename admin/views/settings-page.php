<?php
/**
 * Admin settings page view for BD Local Market.
 *
 * @package BD_Local_Market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = (array) get_option( 'bdlm_settings', array() );

$get = function( $key, $default = '' ) use ( $settings ) {
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
};

// Handle save action nonce for standalone page.
$is_tab_page = isset( $_GET['tab'] ) && 'bd_local_market' === $_GET['tab']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="bdlm-admin-wrap wrap">

	<div class="bdlm-admin-header">
		<div class="bdlm-admin-logo">
			<span class="bdlm-admin-logo-icon">🛒</span>
			<div>
				<h1><?php esc_html_e( 'BD Local Market', 'bd-local-market' ); ?></h1>
				<p><?php esc_html_e( 'Bangladeshi grocery store enhancements for WooCommerce', 'bd-local-market' ); ?></p>
			</div>
		</div>
		<span class="bdlm-version-badge">v<?php echo esc_html( BDLM_VERSION ); ?></span>
	</div>

	<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'bd-local-market' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'bdlm_save_settings', 'bdlm_nonce' ); ?>
		<input type="hidden" name="action" value="bdlm_save_settings_standalone" />

		<?php if ( $is_tab_page ) : ?>
			<input type="hidden" name="save" value="bd_local_market" />
		<?php endif; ?>

		<div class="bdlm-settings-grid">

			<!-- =============================================================== -->
			<!-- SECTION: Product Cards -->
			<!-- =============================================================== -->
			<div class="bdlm-settings-card">
				<div class="bdlm-settings-card-header">
					<span class="bdlm-settings-icon">📦</span>
					<h2><?php esc_html_e( 'Product Card Settings', 'bd-local-market' ); ?></h2>
				</div>
				<div class="bdlm-settings-card-body">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="bdlm_delivery_time_text">
									<?php esc_html_e( 'Default Delivery Time Badge', 'bd-local-market' ); ?>
								</label>
							</th>
							<td>
								<input type="text"
									id="bdlm_delivery_time_text"
									name="bdlm_delivery_time_text"
									value="<?php echo esc_attr( $get( 'delivery_time_text', '2-4 hrs delivery' ) ); ?>"
									class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Shown on every product card. Can be overridden per product in the product editor.', 'bd-local-market' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bdlm_currency_symbol">
									<?php esc_html_e( 'Currency Symbol', 'bd-local-market' ); ?>
								</label>
							</th>
							<td>
								<input type="text"
									id="bdlm_currency_symbol"
									name="bdlm_currency_symbol"
									value="<?php echo esc_attr( $get( 'currency_symbol', '৳' ) ); ?>"
									class="small-text" />
								<p class="description">
									<?php esc_html_e( 'Used in savings badges (e.g. ৳). Default: ৳', 'bd-local-market' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- =============================================================== -->
			<!-- SECTION: Hero Banner -->
			<!-- =============================================================== -->
			<div class="bdlm-settings-card">
				<div class="bdlm-settings-card-header">
					<span class="bdlm-settings-icon">🎯</span>
					<h2><?php esc_html_e( 'Hero Banner', 'bd-local-market' ); ?></h2>
				</div>
				<div class="bdlm-settings-card-body">
					<p class="description bdlm-shortcode-hint">
						<?php
						printf(
							/* translators: shortcode example */
							esc_html__( 'Add %s to your homepage to display the hero section.', 'bd-local-market' ),
							'<code>[bdlm_hero]</code>'
						);
						?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="bdlm_hero_title"><?php esc_html_e( 'Hero Title', 'bd-local-market' ); ?></label>
							</th>
							<td>
								<input type="text"
									id="bdlm_hero_title"
									name="bdlm_hero_title"
									value="<?php echo esc_attr( $get( 'hero_title', __( 'Fresh Groceries, Delivered Fast', 'bd-local-market' ) ) ); ?>"
									class="large-text" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bdlm_hero_subtitle"><?php esc_html_e( 'Hero Subtitle', 'bd-local-market' ); ?></label>
							</th>
							<td>
								<textarea id="bdlm_hero_subtitle"
									name="bdlm_hero_subtitle"
									rows="2"
									class="large-text"><?php echo esc_textarea( $get( 'hero_subtitle', __( 'Shop fresh from our curated selection.', 'bd-local-market' ) ) ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bdlm_hero_cta_text"><?php esc_html_e( 'CTA Button Text', 'bd-local-market' ); ?></label>
							</th>
							<td>
								<input type="text"
									id="bdlm_hero_cta_text"
									name="bdlm_hero_cta_text"
									value="<?php echo esc_attr( $get( 'hero_cta_text', __( 'Shop Now', 'bd-local-market' ) ) ); ?>"
									class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bdlm_hero_cta_url"><?php esc_html_e( 'CTA Button URL', 'bd-local-market' ); ?></label>
							</th>
							<td>
								<input type="url"
									id="bdlm_hero_cta_url"
									name="bdlm_hero_cta_url"
									value="<?php echo esc_attr( $get( 'hero_cta_url', wc_get_page_permalink( 'shop' ) ) ); ?>"
									class="regular-text" />
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- =============================================================== -->
			<!-- SECTION: Flash Deals Countdown -->
			<!-- =============================================================== -->
			<div class="bdlm-settings-card">
				<div class="bdlm-settings-card-header">
					<span class="bdlm-settings-icon">⏱️</span>
					<h2><?php esc_html_e( 'Flash Deals Countdown', 'bd-local-market' ); ?></h2>
				</div>
				<div class="bdlm-settings-card-body">
					<p class="description bdlm-shortcode-hint">
						<?php
						printf(
							esc_html__( 'Add %s to your homepage.', 'bd-local-market' ),
							'<code>[bdlm_deals]</code>'
						);
						?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="bdlm_deal_end_time"><?php esc_html_e( 'Deal End Time', 'bd-local-market' ); ?></label>
							</th>
							<td>
								<input type="datetime-local"
									id="bdlm_deal_end_time"
									name="bdlm_deal_end_time"
									value="<?php echo esc_attr( $get( 'deal_end_time', '' ) ); ?>"
									class="regular-text" />
								<p class="description">
									<?php esc_html_e( 'Leave blank to hide the countdown. Countdown auto-resets when time expires.', 'bd-local-market' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- =============================================================== -->
			<!-- SECTION: Live Search -->
			<!-- =============================================================== -->
			<div class="bdlm-settings-card">
				<div class="bdlm-settings-card-header">
					<span class="bdlm-settings-icon">🔍</span>
					<h2><?php esc_html_e( 'Live Search', 'bd-local-market' ); ?></h2>
				</div>
				<div class="bdlm-settings-card-body">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Live Search', 'bd-local-market' ); ?></th>
							<td>
								<label for="bdlm_enable_live_search">
									<input type="checkbox"
										id="bdlm_enable_live_search"
										name="bdlm_enable_live_search"
										value="1"
										<?php checked( '1', $get( 'enable_live_search', '1' ) ); ?> />
									<?php esc_html_e( 'Show live search dropdown as user types', 'bd-local-market' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="bdlm_search_results_limit">
									<?php esc_html_e( 'Max Search Results', 'bd-local-market' ); ?>
								</label>
							</th>
							<td>
								<input type="number"
									id="bdlm_search_results_limit"
									name="bdlm_search_results_limit"
									value="<?php echo esc_attr( $get( 'search_results_limit', 8 ) ); ?>"
									min="1" max="20"
									class="small-text" />
								<p class="description"><?php esc_html_e( 'Number of products shown in the dropdown (1–20).', 'bd-local-market' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- =============================================================== -->
			<!-- SECTION: Checkout -->
			<!-- =============================================================== -->
			<div class="bdlm-settings-card">
				<div class="bdlm-settings-card-header">
					<span class="bdlm-settings-icon">✅</span>
					<h2><?php esc_html_e( 'Checkout', 'bd-local-market' ); ?></h2>
				</div>
				<div class="bdlm-settings-card-body">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'BD Phone Validation', 'bd-local-market' ); ?></th>
							<td>
								<label for="bdlm_enable_phone_validation">
									<input type="checkbox"
										id="bdlm_enable_phone_validation"
										name="bdlm_enable_phone_validation"
										value="1"
										<?php checked( '1', $get( 'enable_phone_validation', '1' ) ); ?> />
									<?php esc_html_e( 'Validate phone numbers as Bangladeshi mobile format (01XXXXXXXXX)', 'bd-local-market' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- =============================================================== -->
			<!-- SECTION: Shortcode Reference -->
			<!-- =============================================================== -->
			<div class="bdlm-settings-card bdlm-settings-card--info">
				<div class="bdlm-settings-card-header">
					<span class="bdlm-settings-icon">📋</span>
					<h2><?php esc_html_e( 'Shortcode Reference', 'bd-local-market' ); ?></h2>
				</div>
				<div class="bdlm-settings-card-body">
					<table class="widefat bdlm-shortcode-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Shortcode', 'bd-local-market' ); ?></th>
								<th><?php esc_html_e( 'Description', 'bd-local-market' ); ?></th>
								<th><?php esc_html_e( 'Common Attributes', 'bd-local-market' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$shortcodes = array(
								array( '[bdlm_hero]', __( 'Full-width hero banner', 'bd-local-market' ), 'title, subtitle, cta_text, cta_url' ),
								array( '[bdlm_categories]', __( 'Category grid with thumbnails', 'bd-local-market' ), 'limit="12" parent="0" title="..."' ),
								array( '[bdlm_recommended]', __( 'Recommended products (by sales)', 'bd-local-market' ), 'limit="10" category="slug" title="..."' ),
								array( '[bdlm_deals]', __( 'Flash deals with countdown timer', 'bd-local-market' ), 'limit="8" title="..."' ),
								array( '[bdlm_trending]', __( 'Hot & Trending (top selling)', 'bd-local-market' ), 'limit="10" category="slug"' ),
								array( '[bdlm_featured]', __( 'WooCommerce featured products', 'bd-local-market' ), 'limit="8" title="..."' ),
								array( '[bdlm_brands]', __( 'Brand / category highlight strip', 'bd-local-market' ), 'limit="10" parent="0"' ),
							);
							foreach ( $shortcodes as $sc ) :
								?>
								<tr>
									<td><code><?php echo esc_html( $sc[0] ); ?></code></td>
									<td><?php echo esc_html( $sc[1] ); ?></td>
									<td><code><?php echo esc_html( $sc[2] ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div><!-- .bdlm-settings-grid -->

		<div class="bdlm-settings-footer">
			<?php submit_button( __( 'Save BD Local Market Settings', 'bd-local-market' ), 'primary bdlm-save-btn', 'submit', false ); ?>
		</div>

	</form>
</div>
