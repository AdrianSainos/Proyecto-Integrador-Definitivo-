import { NativeModules, Platform } from 'react-native';

const DEFAULT_PORTS = [8010, 8021, 8000];

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
    .map((entry) => (entry.endsWith('/api') ? entry : `${entry}/api`));
}

function basesFromHost(host, ports = DEFAULT_PORTS) {
  if (!host || ['localhost', '127.0.0.1'].includes(host)) {
    return [];
  }

  return ports.map((port) => `http://${host}:${port}/api`);
}

function unique(values) {
  return [...new Set(values.filter(Boolean))];
}

function resolveApiBases() {
  const envBases = envApiBases();
  const devHost = hostFromScriptUrl();
  const candidates = [];

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
    candidates.push(...DEFAULT_PORTS.map((port) => `http://10.0.2.2:${port}/api`));
  }

  candidates.push(...DEFAULT_PORTS.map((port) => `http://127.0.0.1:${port}/api`));

  return unique(candidates);
}

export const API_BASES = resolveApiBases();
export const API_BASE = API_BASES[0] || `http://127.0.0.1:${DEFAULT_PORTS[0]}/api`;
