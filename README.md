# CMS ADMINS Mengenrechner

Rechnet auf WooCommerce-Produktseiten zwischen Fläche, Länge und Verkaufseinheit
um und zeigt den Gesamtpreis sofort an.

## Aufbau

| Datei | Zweck |
| --- | --- |
| `includes/class-caqc-types.php` | Typtabelle, die einzige Stelle für eine neue Warengruppe |
| `includes/class-caqc-product.php` | Lesezugriff auf die Mengendaten, kennt als einzige die Meta-Keys |
| `includes/class-caqc-product-meta.php` | Felder im Produkt-Backend |
| `includes/class-caqc-render.php` | Markup auf der Produktseite, über WooCommerce-Hooks |
| `includes/class-caqc-cart.php` | Angabe in Warenkorb, Kasse, Bestellung, E-Mail |
| `includes/class-caqc-stock.php` | Lagerbestand in Quadratmetern |
| `includes/class-caqc-migrate.php` | Einmalige Übernahme alter Meta-Keys |
| `assets/js/caqc-math.js` | Reine Rechenfunktionen, ohne DOM |
| `assets/js/caqc-frontend.js` | Verdrahtung im Browser |

## Entwicklung

Der Plugin Update Checker liegt unter `vendor/plugin-update-checker/` und wird
nicht über Composer verwaltet, sondern von Hand gepflegt. Er steht in
`.gitignore`, gehört aber ins Auslieferungspaket. Nach einem frischen Klon also
einmal aus einem anderen CMS-ADMINS-Plugin herüberkopieren, sonst bricht
`build.sh` mit einem Hinweis ab.

Rechentest:

    node tests/caqc-math.test.js

Paket bauen:

    ./build.sh

Das Ergebnis liegt unter `dist/`. Veröffentlicht wird es, indem es als
`packages/cms-quantity-calculator.zip` auf `update.cms-admins.de` abgelegt wird.

## Eine Warengruppe ergänzen

Einen Eintrag in `CAQC_Types::all()` hinzufügen. Alles Weitere, Beschriftungen,
Einheiten, Verschnitt, Raumrechner und Preisanzeige, folgt daraus. Von außen
geht es auch über den Filter `caqc_types`.
