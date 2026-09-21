#!/bin/bash
#
# Baut das auslieferbare Paket.
#
#   ./build.sh            ZIP nach dist/
#   ./build.sh --no-zip   nur nach build/ kopieren
#   ./build.sh --clean    build/ und dist/ vorher leeren
#
# Das ZIP traegt den Ordner cms-quantity-calculator/ als oberste Ebene, so
# erwartet es sowohl WordPress als auch der Update-Server.

set -euo pipefail

SLUG="cms-quantity-calculator"
ROOT="$(cd "$(dirname "$0")" && pwd)"
BUILD="$ROOT/build/$SLUG"
DIST="$ROOT/dist"

MAKE_ZIP=1
CLEAN=0

for arg in "$@"; do
	case "$arg" in
		--no-zip) MAKE_ZIP=0 ;;
		--clean)  CLEAN=1 ;;
		*) echo "Unbekannte Option: $arg" >&2; exit 1 ;;
	esac
done

PHP_BIN="$(command -v php || true)"
[ -n "$PHP_BIN" ] || { echo "php fehlt im PATH" >&2; exit 1; }

PYTHON_BIN="$(command -v python3 || command -v python || true)"

# ------------------------------------------------------------------ Version

VERSION_HEADER=$(grep -m1 '^ \* Version:' "$ROOT/$SLUG.php" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
VERSION_CONST=$(grep -m1 "define( 'CAQC_VERSION'" "$ROOT/$SLUG.php" | sed "s/.*'CAQC_VERSION', '//; s/'.*//")

if [ "$VERSION_HEADER" != "$VERSION_CONST" ]; then
	echo "ABBRUCH: Header sagt $VERSION_HEADER, Konstante sagt $VERSION_CONST" >&2
	exit 1
fi

echo "Version: $VERSION_HEADER"

# ------------------------------------------------------------------ Syntax

fail=0

while IFS= read -r file; do
	if ! "$PHP_BIN" -l "$file" > /dev/null 2>&1; then
		echo "Syntaxfehler: $file" >&2
		"$PHP_BIN" -l "$file" >&2 || true
		fail=1
	fi
done < <(find "$ROOT" -name '*.php' -not -path '*/build/*' -not -path '*/dist/*' -not -path '*/vendor/*')

[ "$fail" -eq 0 ] || exit 1
echo "Syntax: in Ordnung"

# ------------------------------------------------------------------ Selbsttest

if command -v node > /dev/null 2>&1; then
	node "$ROOT/tests/caqc-math.test.js" > /dev/null || { echo "ABBRUCH: Rechentest gescheitert" >&2; exit 1; }
	echo "Rechentest: bestanden"
else
	echo "Hinweis: node fehlt, der Rechentest wurde uebersprungen"
fi

# ------------------------------------------------------------------ Kopieren

if [ "$CLEAN" -eq 1 ]; then
	rm -rf "$ROOT/build" "$DIST"
fi

rm -rf "$BUILD"
mkdir -p "$BUILD" "$DIST"

EXCLUDES=()

while IFS= read -r line; do
	[ -z "$line" ] && continue
	case "$line" in \#*) continue ;; esac
	EXCLUDES+=(--exclude="$line")
done < "$ROOT/.buildignore"

EXCLUDES+=(--exclude='build' --exclude='dist')

# tar statt rsync: läuft auch dort, wo rsync fehlt, etwa unter Git Bash.
( cd "$ROOT" && tar "${EXCLUDES[@]}" -cf - . ) | ( cd "$BUILD" && tar -xf - )

# Der Update-Checker kommt nicht von Composer und muss mit ins Paket.
if [ ! -f "$BUILD/vendor/plugin-update-checker/plugin-update-checker.php" ]; then
	echo "ABBRUCH: Der Plugin Update Checker fehlt im Paket" >&2
	exit 1
fi

echo "Paketinhalt: $(find "$BUILD" -type f | wc -l) Dateien"

# ------------------------------------------------------------------ Probe

"$PHP_BIN" -r '
	$dir = $argv[1];
	$header = file_get_contents($dir . "/cms-quantity-calculator.php");
	if (false === strpos($header, "Plugin Name:")) { echo "Kein Plugin-Header\n"; exit(1); }
	foreach (["includes/class-caqc-types.php", "includes/class-caqc-product.php", "assets/js/caqc-math.js", "assets/css/caqc.css"] as $f) {
		if (!file_exists($dir . "/" . $f)) { echo "Fehlt im Paket: $f\n"; exit(1); }
	}
	echo "Paketprobe: in Ordnung\n";
' "$BUILD"

# ------------------------------------------------------------------ ZIP

if [ "$MAKE_ZIP" -eq 1 ]; then
	ZIP="$DIST/$SLUG-$VERSION_HEADER.zip"
	rm -f "$ZIP"

	if command -v zip > /dev/null 2>&1; then
		( cd "$ROOT/build" && zip -rq "$ZIP" "$SLUG" )
	else
		# Ohne zip-Binary, etwa unter Git Bash, packt Python.
		"$PYTHON_BIN" -c '
import os, sys, zipfile
base, slug, target = sys.argv[1], sys.argv[2], sys.argv[3]
with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(os.path.join(base, slug)):
        dirs.sort(); files.sort()
        for name in files:
            full = os.path.join(root, name)
            zf.write(full, os.path.relpath(full, base).replace(os.sep, "/"))
' "$ROOT/build" "$SLUG" "$ZIP"
	fi

	if command -v sha256sum > /dev/null 2>&1; then
		( cd "$DIST" && sha256sum "$(basename "$ZIP")" > "$(basename "$ZIP").sha256" )
	fi

	echo "Paket: $ZIP ($(du -h "$ZIP" | cut -f1))"
	echo
	echo "Veroeffentlichen: als packages/$SLUG.zip auf update.cms-admins.de ablegen"
fi
