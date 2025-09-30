set -euo pipefail
SHARED=../../public/modules/contrib/helfi_platform_config/tests/e2e
pushd "$SHARED" >/dev/null
TGZ=$(npm pack --dry-run --silent)
popd >/dev/null
npm install -D "$SHARED/$TGZ"
