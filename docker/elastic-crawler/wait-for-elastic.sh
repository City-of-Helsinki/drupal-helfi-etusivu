#!/usr/bin/env bash
#
# Waits until Elasticsearch is actually ready to serve queries before
# handing off to the real command.
#
# compose.override.yaml's `depends_on: elastic: condition: service_healthy`
# only waits for Docker to observe elastic's own healthcheck pass once; by
# the time this container's entrypoint runs, elastic can still briefly
# refuse connections from other containers (a race, not a config error).
# This performs the same cluster-health check again, from here, right
# before the crawl actually starts.
set -euo pipefail

host="${ELASTIC_HOST:-elastic}"
port="${ELASTIC_PORT:-9200}"
max_attempts="${ELASTIC_WAIT_MAX_ATTEMPTS:-60}"
sleep_seconds="${ELASTIC_WAIT_INTERVAL:-1}"

echo "Waiting for Elasticsearch at ${host}:${port} to become ready..."

for ((i = 1; i <= max_attempts; i++)); do
  if { exec 3<>"/dev/tcp/${host}/${port}"; } 2>/dev/null; then
    printf 'GET /_cluster/health?wait_for_status=green&timeout=1s HTTP/1.1\r\nHost: %s\r\nConnection: close\r\n\r\n' "$host" >&3
    response="$(cat <&3)"
    exec 3>&- 3<&-

    if grep -q '"status":"green"' <<<"$response"; then
      echo "Elasticsearch is ready."
      exec "$@"
    fi
  fi

  echo "Attempt ${i}/${max_attempts}: Elasticsearch not ready yet, retrying in ${sleep_seconds}s..."
  sleep "$sleep_seconds"
done

echo "Timed out waiting for Elasticsearch to become ready." >&2
exit 1
