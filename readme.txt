=== Flooring Calculator for WooCommerce ===
Contributors: contexlabs
Tags: flooring, square meter, area calculator, woocommerce, quantity
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customers think in square meters, you sell packs. Converts area, length and room size into packs, rolls or pieces and shows the total price live.

== Description ==

Customers buying flooring know the size of their room, not the number of packs. This plugin closes that gap right on the product page: enter the area, see the number of packs and the total price. Prefer to think in packs? Change the pack count and the area follows. No calculate button, everything updates while typing.

Built for flooring and building material shops: laminate, vinyl, parquet, linoleum, underlay, adhesives, skirting boards, profiles and wall panels.

= Quantity types =

Each product gets one quantity type. The type decides which fields the customer sees.

* **Square meters / packs**: laminate, vinyl, parquet, carpet tiles
* **Square meters / rolls**: underlay, insulation, rolled goods
* **Square meters / panels**: acoustic and wall panels, variation sizes supported
* **Meters / pieces**: skirting boards and profiles sold per piece of fixed length
* **Meters**: goods sold by the meter
* **Coverage / container**: adhesives and fillers, calculated from coverage in kg per m² and container size
* **Pieces**: plain quantity field, the default

= Features =

* Two-way calculation: area to packs and packs to area, rounded up to whole units
* Optional waste allowance per checkbox, percentage set in the settings
* Room calculator: length times width for area products, perimeter for skirting boards
* Live total price including tax, with the regular price and saving on sale items
* Size per variation, for products whose variations differ in size
* The entered area is remembered during the visit, so comparing products is instant
* Area or length shown in cart, checkout, order details and order emails
* Stock shown in m² instead of packs (optional)
* Works with the classic and the block based cart and checkout
* Quantity type column, filter, quick edit and bulk edit in the product list
* Respects minimum quantities and stock limits set by WooCommerce
* Compatible with High-Performance Order Storage (HPOS)

= Works with your theme =

The calculator hooks into the standard WooCommerce add to cart form and wraps the theme's quantity field instead of replacing it. Plus and minus buttons of the theme keep working.

= Privacy =

The plugin does not connect to any external service and stores no personal data. The entered area is kept in the browser's session storage for the current visit only.

== Installation ==

1. Install and activate WooCommerce.
2. Install the plugin through Plugins, Add New, or upload the ZIP file, then activate it.
3. Go to WooCommerce, Settings, Products, Flooring calculator to set the waste percentage and choose which quantity types your shop uses.
4. Edit a product. Under Product data, General, choose a quantity type and enter the size per unit, for example 2.13 for a pack of 2.13 m².

For many products at once, use bulk edit in the product list.

== Frequently Asked Questions ==

= Why does a product show no calculator? =

Either the quantity type is set to Pieces, or the size per unit is missing. For adhesives both coverage and container size are required. The product list has a filter "Size per unit missing" to find these products.

= How is the number of packs rounded? =

Always up to the next whole unit, so the customer never orders too little.

= Can I change the waste percentage? =

Yes, under WooCommerce, Settings, Products, Flooring calculator. Set it to 0 to hide the checkbox. Developers can also use the filter `caqc_waste_percent`.

= Are prices shown with or without tax? =

The total includes tax, matching the price display of the product page.

= Can I add my own quantity type? =

Yes, through the filter `caqc_types`. Each type is one array entry with labels, units and options for waste and the room calculator.

= I used custom fields for the calculation before. Can I keep the data? =

Map your old meta keys to the plugin's keys with the filter `caqc_migration_map`. On the next activation the plugin renames them once. Nothing happens without that filter.

= What happens to my product data when I delete the plugin? =

The plugin removes its own settings only. Sizes and quantity types stay with the products, so reinstalling restores everything.

== Changelog ==

= 1.0.0 =
* First public release.
