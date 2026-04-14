import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE, API_BASES } from './config';

export const STORAGE_KEYS = {
  token: 'logistichub.mobile.token',
  user: 'logistichub.mobile.user',
  apiBase: 'logistichub.mobile.apiBase',
};

let activeApiBase = API_BASE;
const verifiedApiBases = new Set();
const REQUEST_TIMEOUT_MS = 10000;
const PROBE_TIMEOUT_MS = 5000;
const HEALTH_ENDPOINT = '/health';

function buildUrl(base, endpoint) {
  const normalized = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  return `${base.replace(/\/$/, '')}${normalized}`;
}

function looksLikeHtml(payload) {
  return typeof payload === 'string' && payload.trim().startsWith('<!DOCTYPE html');
}

function candidateApiBases() {
  return [...new Set([activeApiBase, ...API_BASES].filter(Boolean))];
}

async function parseResponse(response) {
  const text = await response.text();

  if (!text) {
    return null;
  }

  try {
    return JSON.parse(text);
  } catch (error) {
    return text;
  }
}

function buildApiError(message, options = {}) {
  const error = new Error(message);
  error.retryable = Boolean(options.retryable);
  error.status = options.status;
  return error;
}

function payloadLooksHealthy(payload) {
  return Boolean(
    payload &&
    typeof payload === 'object' &&
    String(payload.status || '').toLowerCase() === 'ok' &&
    String(payload.service || '').toLowerCase().includes('gestionpaq'),
  );
}

async function fetchWithTimeout(url, options = {}, timeoutMs = REQUEST_TIMEOUT_MS) {
  const controller = typeof AbortController === 'function' ? new AbortController() : null;
  const timeoutId = setTimeout(() => controller?.abort(), timeoutMs);

  try {
    return await fetch(url, {
      ...options,
      signal: controller?.signal,
    });
  } catch (error) {
    if (error?.name === 'AbortError') {
      throw buildApiError('La API tardo demasiado en responder.', { retryable: true });
    }

    throw error;
  } finally {
    clearTimeout(timeoutId);
  }
}

async function rememberApiBase(base) {
  activeApiBase = base;
  verifiedApiBases.add(base);
  await AsyncStorage.setItem(STORAGE_KEYS.apiBase, base);
}

async function probeApiBase(base) {
  if (verifiedApiBases.has(base)) {
    return true;
  }

  try {
    const response = await fetchWithTimeout(buildUrl(base, HEALTH_ENDPOINT), {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
    }, PROBE_TIMEOUT_MS);
    const payload = await parseResponse(response);

    if (!looksLikeHtml(payload) && response.ok && payloadLooksHealthy(payload)) {
      verifiedApiBases.add(base);
      return true;
    }
  } catch (error) {
    // Fallback below for older backends or partial deployments.
  }

  try {
    const response = await fetchWithTimeout(buildUrl(base, '/auth/login'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({}),
    }, PROBE_TIMEOUT_MS);
    const payload = await parseResponse(response);

    if (looksLikeHtml(payload)) {
      return false;
    }

    if (response.status === 422 && payload && typeof payload === 'object') {
      verifiedApiBases.add(base);
      return true;
    }
  } catch (error) {
    return false;
  }

  return false;
}

function requestHeaders(options = {}) {
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(options.headers || {}),
  };

  if (options.token && !options.skipAuth) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  return headers;
}

function describeFailure(error, candidates) {
  const details = error?.message ? ` ${error.message}` : '';
  const attempts = candidates.slice(0, 4).join(', ');
  const suffix = attempts ? ` Bases probadas: ${attempts}.` : '';

  return `No fue posible conectar la app movil con la API.${details}${suffix}`;
}

export async function hydrateApiBase() {
  const storedBase = await AsyncStorage.getItem(STORAGE_KEYS.apiBase);

  if (storedBase) {
    // Verificar que la URL guardada sigue respondiendo antes de usarla.
    // Si la IP del servidor cambió (nueva red/reinicio), descartarla
    // para que el sistema de auto-descubrimiento encuentre la correcta.
    const stillAlive = await probeApiBase(storedBase);

    if (stillAlive) {
      activeApiBase = storedBase;
      return activeApiBase;
    }

    // URL obsoleta — limpiar para no contaminar futuras sesiones
    await AsyncStorage.removeItem(STORAGE_KEYS.apiBase);
    verifiedApiBases.delete(storedBase);
  }

  return activeApiBase;
}

export async function apiRequest(endpoint, options = {}) {
  const headers = requestHeaders(options);
  const candidates = candidateApiBases();
  let lastError = null;

  for (const base of candidates) {
    try {
      const isReachable = await probeApiBase(base);

      if (!isReachable) {
        lastError = buildApiError(`La URL ${base} no expone la API esperada.`, { retryable: true });
        continue;
      }

      const response = await fetchWithTimeout(buildUrl(base, endpoint), {
        method: options.method || 'GET',
        headers,
        body: options.data !== undefined ? JSON.stringify(options.data) : undefined,
      });

      const payload = await parseResponse(response);

      if (looksLikeHtml(payload)) {
        throw buildApiError(`La URL ${base} no esta respondiendo como API JSON.`, { retryable: true, status: response.status });
      }

      if (!response.ok) {
        const message = payload && typeof payload === 'object' ? payload.message || payload.error : 'No fue posible completar la solicitud.';
        throw buildApiError(message, { retryable: false, status: response.status });
      }

      await rememberApiBase(base);

      return payload;
    } catch (error) {
      lastError = error;

      if (
        String(error?.message || '').includes('No autenticado') ||
        String(error?.message || '').includes('Sesion expirada') ||
        error?.retryable === false
      ) {
        throw error;
      }
    }
  }

  throw new Error(describeFailure(lastError, candidates));
}
