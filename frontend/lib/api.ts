import axios from 'axios';

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

export const api = axios.create({
  baseURL: BASE_URL,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});

let csrfFetched = false;

export async function ensureCsrfCookie(): Promise<void> {
  if (csrfFetched) return;
  await api.get('/sanctum/csrf-cookie');
  csrfFetched = true;
}
