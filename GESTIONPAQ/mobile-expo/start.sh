#!/bin/bash
# ============================================================
# start.sh — Inicio inteligente de Expo para LogisticHub
# Detecta la IP local automáticamente y actualiza .env
# Uso: bash start.sh [--android | --ios | --host lan | ...]
# ============================================================

set -e

# Asegurarse de estar en el directorio de este script (mobile-expo/)
cd "$(dirname "$0")"

# Detectar IP del Mac en la red local (en0 = Wi-Fi, en1 = Ethernet)
IP=$(ipconfig getifaddr en0 2>/dev/null \
  || ipconfig getifaddr en1 2>/dev/null \
  || ipconfig getifaddr en2 2>/dev/null \
  || ipconfig getifaddr en3 2>/dev/null \
  || echo "")

if [ -z "$IP" ]; then
  echo ""
  echo "⚠️  No se pudo detectar la IP local."
  echo "   Asegúrate de estar conectado a Wi-Fi o Ethernet."
  echo "   Se usará el .env actual si existe."
  echo ""
else
  echo ""
  echo "✅ IP local detectada: $IP"
  echo "   API: http://$IP/api"

  # Escribir .env con la IP actual (sobreescribe el anterior)
  cat > .env <<EOF
# Generado automáticamente por start.sh — $(date)
# NO editar manualmente. Usa: bash start.sh
EXPO_PUBLIC_API_BASE=http://$IP/api
EXPO_PUBLIC_API_BASES=http://$IP/api
EOF

  echo "📝 .env actualizado correctamente."
  echo ""
fi

echo "🚀 Iniciando Expo..."
echo ""
npx expo start "$@"
