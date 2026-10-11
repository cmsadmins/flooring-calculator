=== Flooring Calculator for WooCommerce ===
Contributors: contexlabs
Tags: woocommerce, flooring, square meter calculator, area calculator, coverage calculator
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Square meter calculator for WooCommerce flooring shops: customers enter their room size, the product page shows packs, waste and total price.

== Description ==

Flooring Calculator for WooCommerce turns the area a customer needs into the number of packs, rolls, panels, pieces or containers your shop sells, and shows the total price while they type. It is built for shops selling laminate, vinyl, parquet, tiles, underlay, adhesives, skirting boards and wall panels, where customers know their room in square meters but the product is sold per pack.

No calculate button and no formulas to write: choose a quantity type, enter the size of one pack, done. The calculation works in both directions, so changing the number of packs updates the area as well.

Think of it as a free measurement price calculator made for flooring: an m2 calculator (m² to packs), a box calculator for tiles, a roll calculator for underlay and a coverage calculator for adhesives, all in one plugin, while the price stays per pack as set in your shop.

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

= Who it is for =

* Flooring shops: laminate, vinyl and LVT, parquet, linoleum, carpet tiles, cork
* Tile shops selling tiles per box
* Building material shops: underlay, insulation, adhesives, fillers, levelling compound
* Shops selling skirting boards, profiles, transition strips and wall or acoustic panels
* Any WooCommerce shop that sells per pack, roll, box or bucket while customers think in square meters or running meters

= Formulas used =

* Packs = area ÷ size per pack, rounded up
* With waste = area × (1 + waste %) ÷ size per pack, rounded up
* Skirting boards = 2 × (room length + room width) ÷ length per board, rounded up
* Adhesive containers = area × coverage in kg per m² ÷ container size in kg, rounded up
* Delivered area = packs × size per pack (shown in cart and saved with the order)

= Features on the product page =

* Two way calculation: enter the area to get packs, or change the packs to see the area they cover
* Always rounded up to whole packs, rolls, panels, pieces or containers, so the customer never orders too little
* Plus and minus buttons on the area field step by exactly one pack
* Hint below the quantity field, for example "1 pack = 1.74 m²" or the required adhesive weight "Required: 50 kg"
* Waste checkbox, for example "Add 10% for waste", with a line showing the area and packs including waste
* Room calculator: length × width for floors and panels, perimeter for skirting boards, area for adhesives
* Live total price including tax; on sale items also the regular total and the saving
* Notices when a minimum quantity applies or when stock is lower than the requested amount
* Variable products: each variation can have its own size per unit, the calculator switches with the selection
* Price suffix per unit on variations, for example "29.50 € / pack"
* Goods sold by the meter: a fixed note such as "Sold by the meter. Length per piece approx. 2.40 to 2.70 meters."
* The entered area is remembered during the visit, so comparing floors or adding the matching underlay and adhesive is instant
* Prefilled fields are selected on tap, typing replaces the old value
* Mobile friendly: the calculator stacks into one column with large touch targets

= Features in cart, checkout and orders =

* Delivered area, length or weight shown with every line item, for example "Area: 38.28 m²" or "equals 77.00 kg"
* Works in the classic cart and checkout and in the block based cart and checkout
* The value is saved with each order line, so it appears in the order screen, in order emails and on invoices
* Stock shown in m² instead of packs, for example "534.06 m² in stock" (optional)
* Respects minimum and maximum quantities and stock limits set by WooCommerce or other plugins

= Features for shop managers =

* Quantity type, size per unit and waste percentage per product, in the product data panel
* Waste percentage per product, for example 15 % for herringbone and 5 % for click vinyl, with shop defaults for area products and for skirting boards
* Product list column showing type and size, plus a filter "Size per unit missing" to find incomplete products
* Quick edit and bulk edit for quantity type, size per unit and waste, for hundreds of products at once
* Settings: waste defaults, total price on or off, stock display, note for goods sold by the meter, available quantity types
* Existing custom fields from a previous solution can be migrated once with a filter
* Uninstalling removes only the plugin's settings, product data stays

= For developers =

* Filters `caqc_types` (own quantity types), `caqc_waste_percent` (waste per product or group), `caqc_stock_in_size` (stock display) and `caqc_migration_map` (migration of old meta keys)
* No template overrides: the calculator uses the standard add to cart hooks
* Compatible with High-Performance Order Storage (HPOS) and the cart and checkout blocks
* PHP 7.4 to 8.5, WordPress 6.5 or later, WooCommerce 8.0 or later
* Translation ready, German included (formal and informal)

= Works with your theme =

The calculator uses the standard WooCommerce add to cart hooks and wraps the theme's quantity field instead of replacing it, so the theme's plus and minus buttons keep working. Tested with Storefront and Woodmart, on desktop and mobile.

= Privacy =

No external requests, no tracking, no personal data stored. The entered area is kept in the browser's session storage for the current visit only. There is no Pro version and no upsell.

= Support =

Questions and bug reports are welcome in the support forum on wordpress.org. For anything else, write to flooring-calculator@cms-admins.de. Setup help and custom development for WooCommerce shops: [CMS ADMINS](https://www.cms-admins.de/).

= Disclaimer =

The calculator gives an estimate based on the sizes, coverage values and waste percentages entered by the shop. Shop owners are responsible for correct product data and prices and should advise customers to check quantities before laying. The plugin is provided "as is", without warranty of any kind, as stated in the GNU General Public License.

= Trademarks =

WooCommerce is a registered trademark of Automattic Inc. This plugin is not affiliated with or endorsed by Automattic or WooCommerce.

= Copyright =

Copyright (C) 2026 Patrick Schlesinger, [cms-admins.de](https://www.cms-admins.de/). Released under the GNU General Public License v2 or later.

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

= Can I use it as a tile calculator? =

Yes. Tiles sold per box work like flooring packs: enter the m² per box, the customer enters the wall or floor area and gets the number of boxes, with an optional waste allowance for cuts.

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

= 1.0.4 =
* Waste per product: each product can set its own waste percentage, an empty field uses the shop default.
* Separate waste default for skirting boards and goods sold by the meter (off by default).
* Goods sold by the meter: waste checkbox inside the room calculator.
* Quick edit and bulk edit for the waste percentage.
* The filter `caqc_waste_percent` now also receives the product and the waste group. It no longer applies to adhesive and piece products.
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

== Upgrade Notice ==

= 1.0.4 =
Waste percentage per product and for skirting boards, adhesive weight in the cart, layout fixes for mobile and variable products. Requires WordPress 6.5.
