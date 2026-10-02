=== CMS ADMINS Mengenrechner ===
Contributors: cmsadmins
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: Proprietary

Rechnet auf Produktseiten zwischen Fläche, Länge und Verkaufseinheit um und zeigt den Gesamtpreis sofort an.

== Beschreibung ==

Kunden denken in Quadratmetern, bestellt werden Pakete. Das Plugin schlägt diese
Brücke direkt auf der Produktseite: Fläche eingeben, Paketzahl erscheint, Preis
steht darunter. Wer lieber in Paketen denkt, tippt rechts und sieht links die
Fläche. Ein Knopf zum Berechnen entfällt.

Unterstützte Warengruppen:

* Quadratmeter und Pakete, etwa Parkett, Vinyl, Linoleum, Laminat
* Quadratmeter und Rollen, etwa Unterlagen
* Quadratmeter und Paneele, etwa Akustikpaneele
* Meter und Stückzahl, etwa Sockelleisten und Profile
* Laufende Meter
* Verbrauch und Gebinde, etwa Klebstoffe
* Reine Stückzahl

Dazu ein Verschnittaufschlag, ein Rechner für Raummaße und die Angabe der
Fläche in Warenkorb, Kasse, Bestellübersicht und Bestellbestätigung.

== Installation ==

1. Plugin hochladen und aktivieren.
2. Beim Aktivieren werden vorhandene Felder aus einer früheren Child-Theme-Lösung
   einmalig übernommen.
3. Je Produkt unter Produktdaten, Allgemein die Mengenangabe wählen und die
   Größe pro Stück eintragen.

== Häufige Fragen ==

= Warum zeigt ein Produkt keinen Rechner? =

Die Mengenangabe steht auf Stückzahl, oder die Größe pro Stück fehlt. Bei
Klebstoffen müssen Verbrauch und Gebindeinhalt gepflegt sein.

= Wie hoch ist der Verschnitt? =

Fünf Prozent. Der Wert lässt sich über die Option caqc_waste_percent oder den
Filter caqc_waste_percent ändern.

= Rechnet das Plugin mit Brutto- oder Nettopreisen? =

Angezeigt wird der Preis einschließlich Steuer, passend zu den Einstellungen
des Shops.

== Changelog ==

= 1.0.0 =
* Erste Fassung. Sieben Warengruppen, sofortige Berechnung in beide Richtungen,
  Gesamtpreis auf der Produktseite, Raumrechner für Leisten und Paneele,
  Klebstoffberechnung über Verbrauch und Gebinde.
