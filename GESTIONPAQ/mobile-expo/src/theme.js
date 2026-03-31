export const palette = {
  background: '#eef3f4',
  backgroundWarm: '#f7f1e7',
  backgroundCool: '#edf4f5',
  surface: 'rgba(255, 253, 248, 0.84)',
  surfaceStrong: '#fffdf8',
  surfaceSoft: '#f4f8f7',
  surfaceMuted: '#e9efee',
  surfaceTint: 'rgba(15, 123, 108, 0.1)',
  surfaceAccent: 'rgba(255, 138, 61, 0.12)',
  surfaceDark: '#101a2b',
  surfaceDarkAlt: '#162131',
  text: '#1d2433',
  textMuted: '#5d6880',
  textSoft: '#7b8798',
  textOnDark: 'rgba(244, 248, 255, 0.94)',
  textMutedOnDark: 'rgba(224, 231, 243, 0.7)',
  line: 'rgba(26, 38, 62, 0.1)',
  lineStrong: 'rgba(26, 38, 62, 0.16)',
  brand: '#0f7b6c',
  brandDeep: '#0b5d52',
  accent: '#ff8a3d',
  accentDeep: '#d46c23',
  danger: '#cf4c4c',
  success: '#2e9a63',
  info: '#2e7db8',
  neutral: '#7b8798',
  white: '#ffffff',
  shadow: 'rgba(22, 33, 54, 0.14)',
  shadowDark: 'rgba(10, 21, 35, 0.28)',
};

export const spacing = {
  xxs: 4,
  xs: 6,
  sm: 10,
  md: 16,
  lg: 20,
  xl: 28,
  xxl: 36,
};

export const radius = {
  sm: 14,
  md: 18,
  lg: 24,
  xl: 30,
  pill: 999,
};

export const shadows = {
  soft: {
    shadowColor: palette.shadow,
    shadowOpacity: 0.1,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 6 },
    elevation: 4,
  },
  medium: {
    shadowColor: palette.shadow,
    shadowOpacity: 0.14,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 10 },
    elevation: 7,
  },
  floating: {
    shadowColor: palette.shadowDark,
    shadowOpacity: 0.24,
    shadowRadius: 26,
    shadowOffset: { width: 0, height: 14 },
    elevation: 12,
  },
};

function normalizeToken(value) {
  return String(value || '').trim().toLowerCase();
}

export function toneForStatus(value) {
  const normalized = normalizeToken(value);

  if (normalized.includes('entreg') || normalized.includes('complet')) {
    return 'success';
  }

  if (normalized.includes('ruta') || normalized.includes('ejec') || normalized.includes('transit')) {
    return 'info';
  }

  if (normalized.includes('asign') || normalized.includes('plan') || normalized.includes('registr')) {
    return 'brand';
  }

  if (normalized.includes('pend') || normalized.includes('esper') || normalized.includes('prep')) {
    return 'accent';
  }

  if (normalized.includes('cancel') || normalized.includes('error') || normalized.includes('rechaz')) {
    return 'danger';
  }

  return 'neutral';
}

export function roleLabel(role) {
  return {
    admin: 'Administrador',
    operator: 'Operador',
    supervisor: 'Supervisor',
    dispatcher: 'Despachador',
    customer: 'Cliente',
    driver: 'Conductor',
  }[normalizeToken(role)] || 'Usuario';
}

export function getInitials(name) {
  const initials = String(name || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((segment) => segment.charAt(0).toUpperCase())
    .join('');

  return initials || 'GP';
}