=== Flooring Calculator for WooCommerce ===
Contributors: contexlabs
Tags: flooring, square meter calculator, area calculator, coverage calculator, tile calculator
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Square meter calculator for WooCommerce flooring shops: customers enter their room size, the product page shows packs, waste and total price.

== Description ==

Flooring Calculator for WooCommerce turns the area a customer needs into the number of packs, rolls, panels, pieces or containers your shop sells, and shows the total price while they type. It is built for shops selling laminate, vinyl, parquet, tiles, underlay, adhesives, skirting boards and wall panels, where customers know their room in square meters but the product is sold per pack.

No calculate button and no formulas to write: choose a quantity type, enter the size of one pack, done. The calculation works in both directions, so changing the number of packs updates the area as well.

= How it works =

A customer needs 37.5 m² of laminate. One pack holds 1.74 m².

* 37.5 m² ÷ 1.74 m² = 21.6, rounded up to **22 packs**, which cover 38.28 m².
* With 10 % waste ticked: 41.25 m² ÷ 1.74 m² = 23.7, rounded up to **24 packs**.
* The total price updates instantly, the cart shows "Area: 38.28 m²" next to the line item.

The same logic covers the other product groups:

* **Skirting boards**: a 5 × 4 m room has an 18 m perimeter, at 2.40 m per board that is 8 boards.
* **Adhesive**: 50 m² at 1 kg per m² in 25 kg buckets is 2 containers.
* **Underlay rolls and wall panels**: area divided by the size of one roll or panel, rounded up.

= Quantity types =

Each product gets one quantity type. The type decides which fields the customer sees.

* **Square meters / packs**: laminate, vinyl, parquet, tiles, carpet tiles
* **Square meters / rolls**: underlay, insulation, rolled goods
* **Square meters / panels**: acoustic and wall panels, sizes per variation supported
* **Meters / pieces**: skirting boards and profiles sold per piece of fixed length
* **Meters**: goods sold by the running meter
* **Coverage / container**: adhesives and fillers, from coverage in kg per m² and container size
* **Pieces**: the plain WooCommerce quantity field

= Features =

* Two way calculation: area to packs and packs to area, always rounded up to whole units
* Waste allowance per checkbox, with a shop default for area products and one for skirting boards
* Waste percentage per product, for example 15 % for herringbone and 5 % for click vinyl
* Room calculator: length × width for floors, perimeter for skirting boards
* Live total price including tax, with regular price and saving on sale items
* Size per variation for products whose variations differ in size
* Area, length or weight shown in cart, checkout, order details and order emails, and saved with the order
* Stock shown in m² instead of packs (optional)
* Classic and block based cart and checkout, High-Performance Order Storage (HPOS)
* Product list column, filter "Size per unit missing", quick edit and bulk edit for hundreds of products at once
* Respects minimum and maximum quantities and stock limits
* German translations included (formal and informal)

= Works with your theme =

The calculator uses the standard WooCommerce add to cart hooks and wraps the theme's quantity field instead of replacing it, so the theme's plus and minus buttons keep working. Tested with Storefront and Woodmart, on desktop and mobile.

= Privacy =

No external requests, no tracking, no personal data stored. The entered area is kept in the browser's session storage for the current visit only. There is no Pro version and no upsell.

== Installation ==

1. Install and activate WooCommerce.
2. Install the plugin through Plugins, Add New, or upload the ZIP file, then activate it.
3. Go to WooCommerce, Settings, Products, Flooring calculator to set the waste defaults and choose which quantity types your shop uses.
4. Edit a product. Under Product data, General, choose a quantity type and enter the size per unit, for example 2.13 for a pack of 2.13 m².

For many products at once, filter the product list by quantity type and use bulk edit.

== Frequently Asked Questions ==

= How do I sell flooring by the square meter in WooCommerce? =

Keep the price per pack and let the customer enter square meters. Set the product's quantity type to "Square meters / packs" and enter the m² per pack. The product page then converts the area into packs and shows the total price, and the cart shows the delivered area.

= How many packs do I need for my room? =

Packs = room area ÷ m² per pack, rounded up to the next whole pack. With waste: room area × (1 + waste percentage) ÷ m² per pack, rounded up. The plugin does this live on the product page, including a room calculator for length × width.

= How much waste should I add for flooring? =

That depends on the product and the laying pattern. Common values are 5 % for straight laying of click vinyl or laminate and 10 to 15 % for diagonal laying, herringbone or tiles. Set a shop default and override it per product where needed.

= Can I set a different waste percentage per product? =

Yes. Each product has a "Waste (%)" field. Leave it empty to use the shop default, set 0 to hide the waste checkbox for that product. Quick edit and bulk edit change it for many products at once.

= Does it work for skirting boards? =

Yes. The customer enters running meters or the room size, the plugin calculates the perimeter and the number of boards. Skirting boards have their own waste default.

= How much adhesive do I need per square meter? =

The plugin takes the coverage in kg per m² from the product and the container size, then calculates the required weight and the number of containers. If the manufacturer gives a range, enter the upper value so the amount is sufficient.

= Does it work with the block cart and checkout? =

Yes. The area, length or weight appears in the classic and the block based cart and checkout and is saved with each order line, so it also shows in the order screen and in order emails.

= Does it work with variable products? =

Yes. Variations can have their own size per unit, for example skirting boards in 2.40 m and 2.70 m lengths.

= Why does a product show no calculator? =

Either the quantity type is set to Pieces, or the size per unit is missing. For adhesives both coverage and container size are required. The product list has a filter "Size per unit missing" to find these products.

= Does the plugin send data to external services? =

No. It makes no external requests and stores no personal data.

= Is there a Pro version? =

No. All features are in this plugin.

= Can I add my own quantity type? =

Yes, through the filter `caqc_types`. The waste percentage can be changed with the filter `caqc_waste_percent`, the stock display with `caqc_stock_in_size`.

= I used custom fields for the calculation before. Can I keep the data? =

Map your old meta keys to the plugin's keys with the filter `caqc_migration_map`. On the next activation the plugin renames them once. Nothing happens without that filter.

= What happens to my product data when I delete the plugin? =

The plugin removes its own settings only. Sizes, waste values and quantity types stay with the products, so reinstalling restores everything.

== Screenshots ==

1. Flooring: enter the area, get the number of packs and the total price, optionally with waste.
2. Skirting boards: the room calculator turns length and width into pieces, with its own waste allowance.
3. Adhesive: coverage per square meter and container size give the number of containers.
4. Cart: delivered area, length or weight is shown with every line item and saved with the order.
5. Product data: quantity type, size of one sales unit and an optional waste percentage per product.
6. Settings: waste defaults for area and length products, total price, stock display and quantity types.
7. Mobile: the calculator stacks into one column with large touch targets.

== Changelog ==

= Unreleased =
* Waste per product: each product can set its own waste percentage, an empty field uses the shop default.
* Separate waste default for skirting boards and goods sold by the meter (off by default).
* Goods sold by the meter: waste checkbox inside the room calculator.
* Quick edit and bulk edit for the waste percentage.
* Adhesive: changing the number of containers updates the area to be glued.
* Cart, checkout and order show the total weight for adhesives.
* Layout: calculator, total price box and room calculator look consistent in more themes, also on mobile and on variable products.
* Prefilled fields are selected on tap, so typing replaces the old value.

= 1.0.3 =
* The unit stays behind the quantity field when the theme reorders the field (for example Woodmart on variable products).

= 1.0.2 =
* Hide the internal marker of saved order lines in the order screen.

= 1.0.1 =
* The area or length is now saved with each order line, so it shows in the order screen, in the order emails to the shop and on invoices.

= 1.0.0 =
* First public release.
