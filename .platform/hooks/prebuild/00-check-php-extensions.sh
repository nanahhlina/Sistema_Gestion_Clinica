#!/bin/bash
set -euo pipefail

# The managed PHP platform already supplies these modules. Do not refresh yum
# metadata during each application deployment on small instances.
command -v php >/dev/null || { echo 'PHP is unavailable on the managed platform.' >&2; exit 1; }
php -r '
if (PHP_VERSION_ID < 80401) {
    fwrite(STDERR, "The locked dependencies require PHP 8.4.1 or newer.\n");
    exit(1);
}
$required = ["pdo_pgsql", "openssl", "mbstring", "dom", "xml", "fileinfo", "tokenizer", "ctype"];
$missing = array_values(array_filter($required, static fn ($extension) => !extension_loaded($extension)));
if ($missing) {
    fwrite(STDERR, "Missing PHP extensions: ".implode(", ", $missing).". Select a compatible managed PHP platform.\n");
    exit(1);
}
echo "PHP platform requirements verified; no system packages installed.\n";
'
