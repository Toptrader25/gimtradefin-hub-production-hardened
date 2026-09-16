import 'server-only';

import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';
const TOKEN_COOKIE = 'gth_token';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: string;
}

export async function getAuthToken(): Promise<string | null> {
  const store = await cookies();
  return store.get(TOKEN_COOKIE)?.value ?? null;
}

/**
 * Stores the Sanctum token as an httpOnly cookie — never readable by
 * client-side JS, which is the whole point: it closes off the classic
 * "XSS steals the token from localStorage" attack path.
 */
export async function setAuthToken(token: string): Promise<void> {
  const store = await cookies();
  store.set(TOKEN_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: 60 * 60 * 24 * 30, // 30 days
  });
}

export async function clearAuthToken(): Promise<void> {
  const store = await cookies();
  store.delete(TOKEN_COOKIE);
}

/**
 * Returns the current signed-in user, or null if not authenticated or
 * the token is invalid/expired/revoked. Never throws — every caller can
 * render both states without try/catch.
 */
export async function getCurrentUser(): Promise<AuthUser | null> {
  const token = await getAuthToken();
  if (!token) return null;

  try {
    const res = await fetch(`${API_BASE_URL}/auth/me`, {
      headers: { Authorization: `Bearer ${token}` },
      cache: 'no-store', // auth state must never be stale
    });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

/**
 * Gate for any page that requires being signed in. Redirects to /login
 * if not. This is a UX convenience, not the real security boundary —
 * every API call these pages make still requires a valid Bearer token
 * server-side regardless of this check.
 */
export async function requireUser(): Promise<AuthUser> {
  const user = await getCurrentUser();
  if (!user) {
    redirect('/login');
  }
  return user;
}

/**
 * Gate for admin/reviewer-only pages. Same caveat as requireUser: this
 * makes the UI behave correctly, but the actual enforcement is the
 * 'reviewer' middleware on the Laravel side (EnsureUserIsReviewer) — a
 * signed-in "member" hitting the admin API directly gets a real 403
 * from the server regardless of what this function does.
 */
export async function requireReviewer(): Promise<AuthUser> {
  const user = await getCurrentUser();
  if (!user || (user.role !== 'admin' && user.role !== 'reviewer')) {
    redirect('/login');
  }
  return user;
}

/**
 * Stricter than requireReviewer — admin role only. Matches the backend's
 * EnsureUserIsAdmin middleware, which guards user/role management
 * separately from content review for the same reason: granting roles
 * is more sensitive than approving trade leads.
 */
export async function requireAdmin(): Promise<AuthUser> {
  const user = await getCurrentUser();
  if (!user || user.role !== 'admin') {
    redirect('/login');
  }
  return user;
}
