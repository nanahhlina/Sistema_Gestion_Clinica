#!/bin/bash
set -euo pipefail
umask 077
EXPECTED=22d30d9183ca17bd7ab9088ba168ed92f94bd4f917db80d62aef76ace4694801
S3_URI=s3://elasticbeanstalk-us-east-2-346560662291/private-config/Clinica_vida_salud/tls/22d30d9183ca17bd7ab9088ba168ed92f94bd4f917db80d62aef76ace4694801.json

fail() { printf '%s\n' "HTTPS certificate preparation failed: $1" >&2; exit 1; }
[[ "$(id -u)" == 0 ]] || fail 'root required'
BASE=/etc/pki/tls/vidasalud
DEST="$BASE/$EXPECTED"
command -v openssl >/dev/null || fail 'openssl unavailable'
command -v python3 >/dev/null || fail 'python3 unavailable'
command -v flock >/dev/null || fail 'flock unavailable'
[[ ! -L "$BASE" ]] || fail 'unexpected base symlink'
mkdir -p -- "$BASE"
chmod 700 "$BASE"
exec 9>"$BASE/.prepare.lock"
flock -x 9

validate_pair() {
    local pair="$1" fingerprint cert_public key_public
    [[ -d "$pair" && ! -L "$pair" ]] || return 1
    [[ -f "$pair/certificate.pem" && ! -L "$pair/certificate.pem" ]] || return 1
    [[ -f "$pair/private_key.pem" && ! -L "$pair/private_key.pem" ]] || return 1
    fingerprint="$(openssl x509 -in "$pair/certificate.pem" -noout -fingerprint -sha256 2>/dev/null | cut -d= -f2 | tr -d ':' | tr 'A-F' 'a-f')" || return 1
    [[ "$fingerprint" == "$EXPECTED" ]] || return 1
    openssl x509 -in "$pair/certificate.pem" -noout -checkhost clinica.petdiatria.lat >/dev/null 2>&1 || return 1
    openssl x509 -in "$pair/certificate.pem" -noout -checkend 86400 >/dev/null 2>&1 || return 1
    openssl pkey -in "$pair/private_key.pem" -passin pass: -check -noout >/dev/null 2>&1 || return 1
    cert_public="$(openssl x509 -in "$pair/certificate.pem" -pubkey -noout 2>/dev/null)" || return 1
    key_public="$(openssl pkey -in "$pair/private_key.pem" -passin pass: -pubout 2>/dev/null)" || return 1
    [[ -n "$cert_public" && "$cert_public" == "$key_public" ]] || return 1
}

if [[ -e "$DEST" || -L "$DEST" ]]; then
    validate_pair "$DEST" || fail 'existing immutable certificate is invalid'
    [[ "$(stat -c %a "$DEST")" == 700 ]] || fail 'existing directory permissions invalid'
    [[ "$(stat -c %a "$DEST/private_key.pem")" == 600 ]] || fail 'existing key permissions invalid'
    [[ "$(stat -c %a "$DEST/certificate.pem")" == 644 ]] || fail 'existing certificate permissions invalid'
    exit 0
fi

AWS_CLI="$(command -v aws)" || fail 'aws CLI unavailable'
TEMP="$(mktemp -d "$BASE/.download.XXXXXXXX")"
cleanup() { rm -rf -- "$TEMP"; }
trap cleanup EXIT
mkdir -m 700 "$TEMP/pair"
"$AWS_CLI" s3 cp "$S3_URI" "$TEMP/bundle.json" --region us-east-2 --only-show-errors >/dev/null || fail 'S3 download unsuccessful'
python3 - "$TEMP/bundle.json" "$TEMP/pair" <<'PY'
import json
import os
import pathlib
import sys

try:
    source = pathlib.Path(sys.argv[1])
    if source.stat().st_size > 262144:
        raise ValueError("oversized bundle")
    bundle = json.loads(source.read_text(encoding="utf-8"))
    if not isinstance(bundle, dict):
        raise ValueError("invalid bundle")
    for field, name in (("certificate", "certificate.pem"), ("private_key", "private_key.pem")):
        value = bundle.get(field)
        if not isinstance(value, str) or not value.strip() or len(value) > 131072:
            raise ValueError("invalid field")
        target = pathlib.Path(sys.argv[2]) / name
        fd = os.open(target, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
        with os.fdopen(fd, "w", encoding="ascii", newline="\n") as stream:
            stream.write(value)
            if not value.endswith("\n"):
                stream.write("\n")
except Exception:
    print("HTTPS certificate bundle could not be decoded", file=sys.stderr)
    sys.exit(1)
PY
validate_pair "$TEMP/pair" || fail 'downloaded certificate validation unsuccessful'
chmod 700 "$TEMP/pair"
chmod 600 "$TEMP/pair/private_key.pem"
chmod 644 "$TEMP/pair/certificate.pem"
# -n refuses to replace any existing destination; the lock serializes our hooks.
mv -T -n -- "$TEMP/pair" "$DEST"
validate_pair "$DEST" || fail 'installed certificate validation unsuccessful'
