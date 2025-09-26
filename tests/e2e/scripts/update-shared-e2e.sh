set -euo pipefail
SHARED=../../public/modules/contrib/helfi_platform_config/tests/e2e
pushd "$SHARED" >/dev/null
npm version patch --no-git-tag-version
npm ci
npm run build
TGZ=$(npm pack --silent)
popd >/dev/null
npm i -D "$SHARED/$TGZ"
