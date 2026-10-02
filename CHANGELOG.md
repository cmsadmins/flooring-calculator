# Changelog

## 1.3.0

Zwei Dinge, die den Vergleich zwischen Produkten leichter machen.

- Die eingegebene Fläche (oder Meterzahl) und der Verschnitt-Haken bleiben
  für die Dauer des Besuchs erhalten. Wer 37,5 m² eingibt und dann drei
  Böden vergleicht, sieht überall sofort den Gesamtpreis für seinen Raum,
  beim Kleber gleich die passende Gebindezahl. Gemerkt wird je Einheit
  (m² und m getrennt), im Browser, nicht auf dem Server
- Bei reduzierten Artikeln steht unter dem Gesamtpreis der reguläre Preis
  und die Ersparnis, etwa "statt 864,00 €, Sie sparen 156,00 €". Bei
  Varianten folgt die Angabe der gewählten Variante

## 1.2.1

- Klassische Kasse zeigte die Fläche zweimal (hinter der Menge und als
  Positionsdatum). Der Filter auf die Mengenspalte entfällt
- Fläche im Warenkorb mit zwei Nachkommastellen wie auf der Produktseite
- Klebstoffe fragen nach Gebinden, nicht nach Stück

## 1.2.0

Rückmeldungen aus dem ersten Kundentest.

- Verschnitt: die Eingabe bleibt stehen, daneben steht, was mit Aufschlag
  daraus wird ("inkl. 10 % Verschnitt: 55 m², 120 Pakete"). Wird die Menge
  bei gesetztem Haken geändert, zeigt das Flächenfeld den Wert ohne Aufschlag
- Meterware fragt "Wie viel Meter benötigen Sie?" und zeigt die Einheit
  neben dem Mengenfeld, auch bei Meter / Stückzahl in der linken Spalte
- Einstellung "Hinweis bei Meterware": fester Text unter dem Mengenfeld
  statt der Leistenlänge des Produkts, etwa für eine Spanne wie 2,40 bis 2,70 m

## 1.1.0

Leichter in einem weiteren Shop einzusetzen.

- Einstellungen unter WooCommerce, Produkte, Mengenrechner: Verschnitt,
  Gesamtpreis, Umrechnung des Lagerbestands und die Auswahl der Warengruppen
- Spalte "Mengenangabe" in der Produktliste, die auch zeigt, wo eine Größe fehlt
- Schnell- und Massenbearbeitung für Mengenangabe und Größe pro Stück
- Filter über der Produktliste, auch nach "Größe pro Stück fehlt"
- Die Datenübernahme aus einer früheren Lösung läuft nur noch, wenn wirklich
  alte Felder vorhanden sind. Ein frischer Shop merkt nichts davon.
- Das Umstellen einer ganzen Kategorie auf einen anderen Mengentyp ist jetzt ein
  eigener Aufruf mit Probelauf statt eines festen Schritts bei der Aktivierung
- Zwei Vergleiche gegen den Text "m²" durch Merkmale der Typtabelle ersetzt
- Neuer Filter caqc_migration_map für eigene Altfelder

## 1.0.0

Erste Fassung.

- Sieben Warengruppen über eine Typtabelle
- Berechnung in beide Richtungen, ohne Schaltfläche
- Gesamtpreis einschließlich Steuer auf der Produktseite
- Verschnitt von fünf Prozent, korrekt aufgeschlagen statt dividiert
- Raumrechner für Sockelleisten und Akustikpaneele
- Klebstoffe über Verbrauch je Quadratmeter und Gebindeinhalt
- Flächenangabe in Warenkorb, Kasse, Bestellung und Bestellbestätigung
- Lagerbestand in Quadratmetern statt Stück
- Einmalige Übernahme der Felder aus der früheren Child-Theme-Lösung
