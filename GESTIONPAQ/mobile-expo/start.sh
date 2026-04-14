#!/bin/bash
# Inicio robusto de Expo + API para GESTIONPAQ Mobile.
# Detecta una base API valida, arranca Laravel si hace falta
# y genera .env con una base primaria correcta y respaldos.

set -euo pipefail

cd "$(dirname "$0")"

API_PORT="${GESTIONPAQ_API_PORT:-8010}"
API_LOG_FILE=".expo-api.log"
API_PID_FILE=".expo-api.pid"
ENV_FILE=".env"
NO_EXPO=0
EXPO_ARGS=()

for arg in "$@"; do
  case "$arg" in
    --no-expo)
      NO_EXPO=1
      ;;
    *)
      EXPO_ARGS+=("$arg")
      ;;
  esac
done

detect_ip() {
  ipconfig getifaddr en0 2>/dev/null \
    || ipconfig getifaddr en1 2>/dev/null \
    || ipconfig getifaddr en2 2>/dev/null \
    || ipconfig getifaddr en3 2>/dev/null \
    || echo ""
}

probe_api_base() {
  local base="${1%/}"
  local health_url="${base}/health"
  local response

  response="$(curl --silent --show-error --location --fail --max-time 2 --connect-timeout 1 -H 'Accept: application/json' "$health_url" 2>/dev/null || true)"

  if [ -z "$response" ]; then
    return 1
  fi

  echo "$response" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"ok"' \
    && echo "$response" | grep -Eq '"service"[[:space:]]*:[[:space:]]*"GESTIONPAQ API"'
}

wait_for_api_base() {
  local base="$1"
  local retries="${2:-12}"
  local attempt=1

  while [ "$attempt" -le "$retries" ]; do
    if probe_api_base "$base"; then
      return 0
    fi

    sleep 1
    attempt=$((attempt + 1))
  done

  return 1
}

dedupe_candidates() {
  local seen="|"
  local item

  for item in "$@"; do
    [ -n "$item" ] || continue

    case "$seen" in
      *"|$item|"*)
        ;;
      *)
        printf '%s\n' "$item"
        seen="${seen}${item}|"
        ;;
    esac
  done
}

join_by_comma() {
  local first=1
  local item

  for item in "$@"; do
    [ -n "$item" ] || continue

    if [ "$first" -eq 1 ]; then
      printf '%s' "$item"
      first=0
    else
      printf ',%s' "$item"
    fi
  done
}

start_artisan_server() {
  if [ -f "$API_PID_FILE" ]; then
    local existing_pid
    existing_pid="$(cat "$API_PID_FILE" 2>/dev/null || true)"

    if [ -n "$existing_pid" ] && kill -0 "$existing_pid" 2>/dev/null; then
      return 0
    fi

    rm -f "$API_PID_FILE"
  fi

  if lsof -ti "tcp:${API_PORT}" -sTCP:LISTEN >/dev/null 2>&1; then
    return 0
  fi

  echo "🛠️  No se detecto una API accesible. Arrancando Laravel en 0.0.0.0:${API_PORT}..."
  nohup php ../artisan serve --host 0.0.0.0 --port "$API_PORT" > "$API_LOG_FILE" 2>&1 &
  echo $! > "$API_PID_FILE"
}

IP="$(detect_ip)"

if [ -z "$IP" ]; then
  echo ""
  echo "⚠️  No se pudo detectar la IP local."
  echo "   Conectate a Wi-Fi o Ethernet y vuelve a intentar."
  echo ""
  exit 1
fi

echo ""
echo "✅ IP local detectada: $IP"

# Siempre arrancar artisan en :${API_PORT} — garantiza que el celular
# pueda conectarse a la API aunque Apache/XAMPP no sea accesible
# desde la red del dispositivo movil (mismo caso que el puerto 8081 de Expo).
start_artisan_server
echo "⏳ Esperando servidor artisan en :${API_PORT}..."
wait_for_api_base "http://${IP}:${API_PORT}/api" 15 || true

CANDIDATE_BASES=(
  "http://${IP}:${API_PORT}/api"
  "http://${IP}/Proyecto-Integrador-Definitivo-/GESTIONPAQ/public/api"
  "http://${IP}/GESTIONPAQ/public/api"
  "http://${IP}/api"
)

PRIMARY_BASE=""
for base in "${CANDIDATE_BASES[@]}"; do
  if probe_api_base "$base"; then
    PRIMARY_BASE="$base"
    break
  fi
done

if [ -z "$PRIMARY_BASE" ]; then
  echo ""
  echo "❌ No se pudo exponer una API valida para la app movil."
  echo "   Revise Apache/XAMPP o el log temporal: ${API_LOG_FILE}"
  echo ""
  exit 1
fi

if [ -z "$PRIMARY_BASE" ]; then
  echo ""
  echo "❌ No se pudo exponer una API valida para la app movil."
  echo "   Revise Apache/XAMPP o el log temporal: ${API_LOG_FILE}"
  echo ""
  exit 1
fi

UNIQUE_BASES=()
while IFS= read -r item; do
  UNIQUE_BASES+=("$item")
done < <(dedupe_candidates "$PRIMARY_BASE" "${CANDIDATE_BASES[@]}")

API_BASES_VALUE="$(join_by_comma "${UNIQUE_BASES[@]}")"

cat > "$ENV_FILE" <<EOF
# Generado automaticamente por start.sh — $(date)
# NO editar manualmente. Usa: bash start.sh
EXPO_PUBLIC_API_BASE=${PRIMARY_BASE}
EXPO_PUBLIC_API_BASES=${API_BASES_VALUE}
EOF

echo "🌐 API principal: ${PRIMARY_BASE}"
echo "🧭 Bases de respaldo: ${API_BASES_VALUE}"
echo "📝 ${ENV_FILE} actualizado correctamente."
echo ""

if [ "$NO_EXPO" -eq 1 ]; then
  exit 0
fi

echo "🚀 Iniciando Expo..."
echo ""
if [ ${#EXPO_ARGS[@]} -gt 0 ]; then
  npx expo start "${EXPO_ARGS[@]}"
else
  npx expo start
fi
