# BD Local Market (Bangladeshi Supermarket WooCommerce Plugin)

**BD Local Market** is a production-ready WooCommerce plugin designed specifically for Bangladeshi grocery and supermarket e-commerce websites. Inspired by leading Bangladeshi online supermarkets like Shwapno, it provides a complete store aesthetic overhaul, enhanced product cards, guest checkout, live AJAX search, multi-level category navigation, and customizable homepage section shortcodes.

---

## Key Features

- **Shwapno-Style Product Cards:**
  - Savings / Discount badges (Percentage `20% OFF` or Taka amount `৳50 OFF`).
  - Unit/weight label below title (e.g. *Per 1 kg*, *Per Piece*, *500g*).
  - Minimum quantity requirement notes for loose items (e.g. *Min. 500g*).
  - Price display in Bangladeshi Taka (`৳`) with regular price strikethrough.
  - Estimated delivery time badge (e.g. `🚚 Delivery 1-2 hours`).
  - Styled "Add to Bag" button with live AJAX adding states.

- **Optimized Guest & Logged-In Checkout:**
  - **Guests:** Requires only **Name** and **Bangladeshi Phone Number** (`01XXXXXXXXX`). Address, Email, and Company fields are optional/hidden.
  - **Logged-In Users:** Auto-fills fields from user profile. Phone is required only if missing from user profile.
  - **Notice Banner:** Friendly "Quick Checkout" notification above checkout form.

- **Live AJAX Search:**
  - Prominent search bar shortcode `[bd_search]`.
  - Searches across Product Title, SKU, Short Description, Product Categories, and Tags.
  - Instant live dropdown suggestions showing product image, match highlight, price, and `SALE` badge.

- **Multi-Level Category Browsing:**
  - Collapsible CSS accordion shortcode `[bd_category_menu]`.
  - Subcategory navigation chips with category icons on archive pages.

- **Homepage Section Shortcodes:**
  - `[bd_recommended_products]`
  - `[bd_deals_section]` (with live countdown clock)
  - `[bd_hot_trending]`
  - `[bd_featured_finds]`
  - `[bd_category_highlight]`
  - `[bd_promo_banner]`

---

## Shortcode Reference

| Shortcode | Parameters / Defaults | Description |
|---|---|---|
| `[bd_product_grid]` | `category="" limit="8" columns="4" badge_type="percent"` | Displays custom product grid |
| `[bd_search]` | `placeholder="Search..." button_text="Search"` | Search bar form |
| `[bd_category_menu]` | `title="All Categories" depth="3" show_count="yes"` | Multi-level category menu |
| `[bd_recommended_products]` | `limit="12" title="Recommended for you"` | Top-selling recommended products |
| `[bd_deals_section]` | `limit="8" title="Weekday Deals!!!" show_countdown="yes"` | Flash deals section |
| `[bd_hot_trending]` | `limit="8" title="Hot & Trending Right Now 🔥"` | Popular trending products |
| `[bd_featured_finds]` | `limit="8" title="Today’s Featured Finds"` | Featured products grid |
| `[bd_category_highlight]` | `category="slug" title="Fresh Produce" limit="6"` | Category-specific product grid |
| `[bd_promo_banner]` | (None) | Benefit boxes (60 Mins Delivery, Free Shipping, etc.) |

---

## Brand Color Customization (CSS Variables)

To match your brand's color palette (e.g. **Shwapno Red `#DF0000`**), add the following CSS to **Appearance → Customize → Additional CSS** or your theme's `style.css`:

```css
:root {
  /* Swap primary green for Shwapno Red */
  --bdlm-green:        #DF0000;
  --bdlm-green-dark:   #B30000;
  --bdlm-green-light:  #FFEEEE;
  --bdlm-orange:       #FF6200;
  --bdlm-text:         #1A2B1A;
}
```

---

## Theme & Astra Compatibility Notes

- **Astra Theme:** Fully compatible with Astra Free Theme. Automatically applies Astra full-width container classes on shop and category archive pages.
- **Custom Layouts & Page Builders:** Shortcodes can be placed directly inside Astra Hooks, Custom Layouts, Elementor, Divi, or Gutenberg blocks.
- **HPOS Ready:** Declares compatibility with WooCommerce High-Performance Order Storage.

---

## Translation & Internationalization (i18n)

- Textdomain: `bd-local-market`
- Translation Template: `languages/bd-local-market.pot`
- Pre-translated strings included for Bengali (`bn_BD`) and English.

---

## Developer API

Use the built-in helper function to render product cards or custom homepage sections programmatically:

```php
// Render a single product card
echo bd_local_product_card( $product_or_id, array( 'badge_type' => 'taka' ) );

// Render a custom product section
echo bd_local_render_homepage_section( array(
    'title'    => 'Organic Produce',
    'category' => 'organic',
    'limit'    => 8,
    'columns'  => 4,
) );
```
