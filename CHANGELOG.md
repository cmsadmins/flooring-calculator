# Changelog

All notable changes to Flooring Calculator for WooCommerce. The wp.org readme carries the same entries in short form.

## 1.0.5

- Mobile: the plus and minus buttons of the area field and the quantity field line up.
- The total price box and add to cart button no longer shift sideways when a product sample plugin adds margins to the cart form.

## 1.0.4

- Waste per product: each product can set its own waste percentage, an empty field uses the shop default.
- Separate waste default for skirting boards and goods sold by the meter (off by default).
- Goods sold by the meter: waste checkbox inside the room calculator.
- Quick edit and bulk edit for the waste percentage.
- The filter `caqc_waste_percent` now also receives the product and the waste group. It no longer applies to adhesive and piece products.
- Adhesive: changing the number of containers updates the area to be glued.
- Cart, checkout and order show the total weight for adhesives.
- Layout: calculator, total price box and room calculator look consistent in more themes, also on mobile and on variable products.
- Prefilled fields are selected on tap, so typing replaces the old value.

## 1.0.3

- The unit stays behind the quantity field when the theme reorders the field (for example Woodmart on variable products).

## 1.0.2

- Hide the internal marker of saved order lines in the order screen.

## 1.0.1

- The area or length is now saved with each order line, so it shows in the order screen, in the order emails to the shop and on invoices.

## 1.0.0

- First public release. Derived from the in-house plugin cms-quantity-calculator 1.3.0, English source strings, German translations bundled, no update checker.
