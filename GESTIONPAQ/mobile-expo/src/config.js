import { NativeModules, Platform } from 'react-native';

const DEFAULT_PORTS = [8010, 8021, 8000];
const DEFAULT_API_PATHS = [
  '/api',
  '/GESTIONPAQ/public/api',
  '/Proyecto-Integrador-Definitivo-/GESTIONPAQ/public/api',
];

function normalizeApiBase(value) {
  const base = String(value || '').trim();

  if (!base) {
    return '';
  }

  if (/^https?:\/\//i.test(base)) {
    return base.replace(/\/$/, '');
  }

  return `http://${base.replace(/\/$/, '')}`;
}

function ensureApiPath(base) {
  return base.endsWith('/api') ? base : `${base}/api`;
}

function hostFromScriptUrl() {
  const scriptUrl = NativeModules?.SourceCode?.scriptURL || '';
  const match = scriptUrl.match(/^(?:https?|exp):\/\/([^/:]+)/i);

  return match ? match[1] : '';
}

function envApiBases() {
  const raw = [process.env.EXPO_PUBLIC_API_BASES, process.env.EXPO_PUBLIC_API_BASE]
    .filter(Boolean)
    .join(',');

  if (!raw) {
    return [];
  }

  return raw
    .split(',')
    .map((entry) => normalizeApiBase(entry))
    .filter(Boolean)
    .map((entry) => ensureApiPath(entry));
}

function buildHostBase(host, path, port = null) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  const portSegment = port ? `:${port}` : '';

  return `http://${host}${portSegment}${normalizedPath}`;
}

function basesFromHost(host, ports = DEFAULT_PORTS) {
  if (!host || ['localhost', '127.0.0.1'].includes(host)) {
    return [];
  }

  // Las rutas sin puerto (XAMPP/Apache en port 80) van PRIMERO
  // para no desperdiciar tiempo en timeouts de puertos incorrectos
  return [
    ...DEFAULT_API_PATHS.map((path) => buildHostBase(host, path)),
    ...ports.map((port) => buildHostBase(host, '/api', port)),
  ];
}

function unique(values) {
  return [...new Set(values.filter(Boolean))];
}

function resolveApiBases() {
  const envBases = envApiBases();
  const devHost = hostFromScriptUrl();
  const candidates = [];
  const shouldTryLoopback = Platform.OS === 'android' || ['localhost', '127.0.0.1'].includes(devHost);

  if (envBases.length) {
    const envHosts = envBases.map((entry) => {
      const match = entry.match(/^https?:\/\/([^/:]+)/i);
      return match ? match[1] : '';
    });

    candidates.push(
      ...envBases,
      ...envHosts.flatMap((host) => basesFromHost(host)),
    );
  }

  candidates.push(...basesFromHost(devHost));

  if (Platform.OS === 'android') {
    candidates.push(...basesFromHost('10.0.2.2'));
  }

  if (shouldTryLoopback) {
    candidates.push(
      ...DEFAULT_PORTS.map((port) => buildHostBase('127.0.0.1', '/api', port)),
      ...DEFAULT_API_PATHS.map((path) => buildHostBase('127.0.0.1', path)),
    );
  }

  return unique(candidates);
}

export const API_BASES = resolveApiBases();
export const API_BASE = API_BASES[0] || `http://127.0.0.1:${DEFAULT_PORTS[0]}/api`;
