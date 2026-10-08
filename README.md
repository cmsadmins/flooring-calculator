# Flooring Calculator for WooCommerce

Converts between area, length and sales unit on WooCommerce product pages and
shows the total price instantly. Built for flooring and building material shops.

Plugin page on wordpress.org: https://wordpress.org/plugins/flooring-calculator/

## Structure

| File | Purpose |
| --- | --- |
| `includes/class-caqc-types.php` | Quantity types, the only place a new type is defined |
| `includes/class-caqc-product.php` | Read access to the product data, the only class that knows the meta keys |
| `includes/class-caqc-product-meta.php` | Fields in the product editor |
| `includes/class-caqc-render.php` | Markup on the product page, via WooCommerce hooks |
| `includes/class-caqc-cart.php` | Area or length in cart, checkout, order and email |
| `includes/class-caqc-stock.php` | Stock text in the product's measure |
| `includes/class-caqc-admin.php` | Column, filter, quick and bulk edit in the product list |
| `includes/class-caqc-settings.php` | Settings under WooCommerce, Products |
| `includes/class-caqc-migrate.php` | One-time rename of legacy meta keys, opt-in via filter |
| `assets/js/caqc-math.js` | Pure calculation functions, no DOM |
| `assets/js/caqc-frontend.js` | Browser wiring |

## Filters

| Filter | Purpose |
| --- | --- |
| `caqc_types` | Add or change quantity types |
| `caqc_waste_percent` | Override the waste percentage |
| `caqc_stock_in_size` | Turn the stock conversion on or off per product |
| `caqc_migration_map` | Legacy meta key to plugin meta key, renamed once on activation |

Meta keys: `_caqc_type`, `_caqc_size_per_piece`, `_caqc_use_variation_size`,
`_caqc_variation_size`, `_caqc_consumption`, `_caqc_pack_size`.

## Development

    composer install
    vendor/bin/phpcs
    vendor/bin/phpstan analyse
    node tests/caqc-math.test.js

The workspace around this repository has `run.sh` for the Docker stack, the
Plugin Check and the release build.

## License

GPL-2.0-or-later, see `LICENSE`.
