#!/bin/bash
# =============================================================================
# GESTIONPAQ - Script de configuración e inicio
# Ejecutar UNA VEZ al configurar un nuevo servidor o después de problemas.
# Uso: bash setup.sh
# =============================================================================

set -euo pipefail
cd "$(dirname "$0")"

PHP_BIN="${PHP_BIN:-/Applications/XAMPP/xamppfiles/bin/php}"
ARTISAN="$PHP_BIN artisan"

echo ""
echo "========================================="
echo "  GESTIONPAQ - Setup y reparación"
echo "========================================="
echo ""

# ─── 1. Verificar PHP ───────────────────────────────────────────────────────
if ! command -v "$PHP_BIN" &>/dev/null && ! command -v php &>/dev/null; then
  echo "ERROR: No se encontró PHP. Asegúrate de que XAMPP esté instalado."
  exit 1
fi

# Usar php del PATH si el de XAMPP no existe
if ! command -v "$PHP_BIN" &>/dev/null; then
  PHP_BIN="php"
  ARTISAN="$PHP_BIN artisan"
fi

echo "✅ PHP encontrado: $($PHP_BIN --version | head -1)"

# ─── 2. Crear .env si no existe ─────────────────────────────────────────────
if [ ! -f ".env" ]; then
  echo "📋 Creando .env desde .env.example..."
  cp .env.example .env
  echo "🔑 Generando APP_KEY..."
  $ARTISAN key:generate --force
  echo "✅ .env creado y APP_KEY generada"
else
  echo "✅ .env ya existe"
  # Asegurar que tenga APP_KEY
  if grep -q "APP_KEY=$" .env; then
    echo "🔑 Generando APP_KEY faltante..."
    $ARTISAN key:generate --force
  fi
fi

# ─── 3. Verificar DB_DATABASE ────────────────────────────────────────────────
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
if [ -z "$DB_NAME" ] || [ "$DB_NAME" = "logistica_normalizada" ]; then
  echo "⚠️  DB_DATABASE incorrecto ($DB_NAME). Corrigiendo a GestionPaq..."
  sed -i.bak 's/^DB_DATABASE=.*/DB_DATABASE=GestionPaq/' .env
  rm -f .env.bak
  echo "✅ DB_DATABASE corregido a GestionPaq"
fi

# ─── 4. Limpiar cachés ──────────────────────────────────────────────────────
echo ""
echo "🧹 Limpiando cachés de Laravel..."
$ARTISAN config:clear   2>/dev/null || true
$ARTISAN route:clear    2>/dev/null || true
$ARTISAN cache:clear    2>/dev/null || true
$ARTISAN view:clear     2>/dev/null || true
echo "✅ Cachés limpios"

# ─── 5. Permisos ────────────────────────────────────────────────────────────
echo ""
echo "🔒 Ajustando permisos..."
chmod -R 777 storage/ bootstrap/cache/ public/uploads/ 2>/dev/null || true
# Crear subdirectorios faltantes de uploads
mkdir -p public/uploads/evidences/photos
mkdir -p public/uploads/evidences/signatures
chmod -R 777 public/uploads/ 2>/dev/null || true
echo "✅ Permisos correctos"

# ─── 6. Verificar conexión a la base de datos ───────────────────────────────
echo ""
echo "🗄️  Verificando conexión a la base de datos..."
if $ARTISAN db:show 2>/dev/null | grep -q "GestionPaq"; then
  echo "✅ Base de datos GestionPaq accesible"
elif $ARTISAN migrate:status 2>/dev/null | grep -q "Ran\|Yes"; then
  echo "✅ Base de datos accesible"
else
  echo "⚠️  No se pudo verificar la BD. Asegúrate de que MySQL/XAMPP esté corriendo."
fi

# ─── 7. Verificar migración ─────────────────────────────────────────────────
echo ""
echo "📦 Verificando migraciones pendientes..."
PENDING=$($ARTISAN migrate:status 2>/dev/null | grep -c "No\|Pending" || echo "0")
if [ "$PENDING" -gt 0 ]; then
  echo "⚠️  Hay $PENDING migraciones pendientes. Ejecutando..."
  $ARTISAN migrate --force
  echo "✅ Migraciones aplicadas"
else
  echo "✅ No hay migraciones pendientes"
fi

# ─── 8. Resumen final ───────────────────────────────────────────────────────
echo ""
echo "========================================="
echo "  ✅ Setup completado exitosamente"
echo "========================================="
echo ""
echo "  Frontend web: http://localhost/logistichub/"
echo "  API proxy:    http://localhost/api/"
echo ""
echo "  Credenciales de acceso:"
echo "    Admin:      admin@gestionpaq.local   / admin123"
echo "    Operador:   operator@gestionpaq.local / oper123"
echo "    Supervisor: supervisor@gestionpaq.local / super123"
echo "    Conductor:  driver@gestionpaq.local  / driver123"
echo ""
echo "  Para iniciar app móvil: cd mobile-expo && bash start.sh"
echo ""
