import { API_BASE, API_BASES } from './config';

export const STORAGE_KEYS = {
  token: 'logistichub.mobile.token',
  user: 'logistichub.mobile.user',
};

let activeApiBase = API_BASE;

function buildUrl(base, endpoint) {
  const normalized = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  return `${base.replace(/\/$/, '')}${normalized}`;
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

export async function apiRequest(endpoint, options = {}) {
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(options.headers || {}),
  };

  if (options.token && !options.skipAuth) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  const candidates = [...new Set([activeApiBase, ...API_BASES].filter(Boolean))];
  let lastError = null;

  for (const base of candidates) {
    try {
      const response = await fetch(buildUrl(base, endpoint), {
        method: options.method || 'GET',
        headers,
        body: options.data !== undefined ? JSON.stringify(options.data) : undefined,
      });

      const payload = await parseResponse(response);

      if (typeof payload === 'string' && payload.trim().startsWith('<!DOCTYPE html')) {
        throw new Error(`La URL ${base} no esta respondiendo como API JSON.`);
      }

      if (!response.ok) {
        const message = payload && typeof payload === 'object' ? payload.message || payload.error : 'No fue posible completar la solicitud.';
        throw new Error(message);
      }

      activeApiBase = base;

      return payload;
    } catch (error) {
      lastError = error;

      if (String(error?.message || '').includes('No autenticado') || String(error?.message || '').includes('Sesion expirada')) {
        throw error;
      }
    }
  }

  const details = lastError?.message ? ` ${lastError.message}` : '';
  throw new Error(`No fue posible conectar la app movil con la API.${details}`);
}