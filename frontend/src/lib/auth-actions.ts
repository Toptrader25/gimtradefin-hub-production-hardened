'use server';

import { redirect } from 'next/navigation';
import { setAuthToken, clearAuthToken, getAuthToken } from './auth';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1';

export interface AuthFormState {
  status: 'idle' | 'error';
  message?: string;
}

export async function loginAction(
  _prevState: AuthFormState,
  formData: FormData
): Promise<AuthFormState> {
  const email = String(formData.get('email') || '').trim();
  const password = String(formData.get('password') || '');

  if (!email || !password) {
    return { status: 'error', message: 'Email and password are required.' };
  }

  let token: string;
  try {
    const res = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });

    if (!res.ok) {
      if (res.status === 429) {
        return { status: 'error', message: 'Too many attempts. Please wait a moment and try again.' };
      }
      const body = await res.json().catch(() => null);
      return { status: 'error', message: body?.errors?.email?.[0] || body?.message || 'Invalid email or password.' };
    }

    const data = await res.json();
    token = data.token;
  } catch {
    return { status: 'error', message: 'Could not reach the API. Please try again shortly.' };
  }

  await setAuthToken(token);
  redirect('/');
}

export async function registerAction(
  _prevState: AuthFormState,
  formData: FormData
): Promise<AuthFormState> {
  const name = String(formData.get('name') || '').trim();
  const email = String(formData.get('email') || '').trim();
  const password = String(formData.get('password') || '');
  const passwordConfirmation = String(formData.get('password_confirmation') || '');

  if (!name || !email || !password) {
    return { status: 'error', message: 'All fields are required.' };
  }
  if (password.length < 8) {
    return { status: 'error', message: 'Password must be at least 8 characters.' };
  }
  if (password !== passwordConfirmation) {
    return { status: 'error', message: 'Passwords do not match.' };
  }

  let token: string;
  try {
    const res = await fetch(`${API_BASE_URL}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      }),
    });

    if (!res.ok) {
      if (res.status === 429) {
        return { status: 'error', message: 'Too many attempts. Please wait a moment and try again.' };
      }
      const body = await res.json().catch(() => null);
      const firstError = body?.errors ? (Object.values(body.errors)[0] as string[]) : null;
      return { status: 'error', message: firstError?.[0] || body?.message || 'Could not create account.' };
    }

    const data = await res.json();
    token = data.token;
  } catch {
    return { status: 'error', message: 'Could not reach the API. Please try again shortly.' };
  }

  await setAuthToken(token);
  redirect('/');
}

export async function logoutAction(): Promise<void> {
  const token = await getAuthToken();
  if (token) {
    try {
      await fetch(`${API_BASE_URL}/auth/logout`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
      });
    } catch {
      // Best-effort server-side revocation — clear the local cookie
      // regardless, so the user is signed out on this device either way.
    }
  }
  await clearAuthToken();
  redirect('/');
}

export interface ForgotPasswordFormState {
  status: 'idle' | 'success' | 'error';
  message?: string;
}

export async function forgotPasswordAction(
  _prevState: ForgotPasswordFormState,
  formData: FormData
): Promise<ForgotPasswordFormState> {
  const email = String(formData.get('email') || '').trim();
  if (!email) {
    return { status: 'error', message: 'Email is required.' };
  }

  try {
    const res = await fetch(`${API_BASE_URL}/auth/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email }),
    });

    if (res.status === 429) {
      return { status: 'error', message: 'Too many attempts. Please wait a moment and try again.' };
    }

    // Deliberately treat this as success even on some non-2xx cases —
    // the backend always returns the same message whether or not the
    // email exists, so the frontend shouldn't reveal anything either.
    const data = await res.json().catch(() => null);
    return { status: 'success', message: data?.status || 'If that email exists, a reset link has been sent.' };
  } catch {
    return { status: 'error', message: 'Could not reach the API. Please try again shortly.' };
  }
}

export interface ResetPasswordFormState {
  status: 'idle' | 'success' | 'error';
  message?: string;
}

export async function resetPasswordAction(
  _prevState: ResetPasswordFormState,
  formData: FormData
): Promise<ResetPasswordFormState> {
  const token = String(formData.get('token') || '');
  const email = String(formData.get('email') || '');
  const password = String(formData.get('password') || '');
  const passwordConfirmation = String(formData.get('password_confirmation') || '');

  if (!token || !email) {
    return { status: 'error', message: 'This reset link is invalid or incomplete. Request a new one.' };
  }
  if (password.length < 8) {
    return { status: 'error', message: 'Password must be at least 8 characters.' };
  }
  if (password !== passwordConfirmation) {
    return { status: 'error', message: 'Passwords do not match.' };
  }

  try {
    const res = await fetch(`${API_BASE_URL}/auth/reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, email, password, password_confirmation: passwordConfirmation }),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return { status: 'error', message: body?.message || 'This reset link is invalid or has expired.' };
    }

    return { status: 'success', message: 'Password reset. You can now sign in with your new password.' };
  } catch {
    return { status: 'error', message: 'Could not reach the API. Please try again shortly.' };
  }
}

export interface ChangePasswordFormState {
  status: 'idle' | 'success' | 'error';
  message?: string;
}

export async function changePasswordAction(
  _prevState: ChangePasswordFormState,
  formData: FormData
): Promise<ChangePasswordFormState> {
  const currentPassword = String(formData.get('current_password') || '');
  const password = String(formData.get('password') || '');
  const passwordConfirmation = String(formData.get('password_confirmation') || '');

  if (!currentPassword || !password) {
    return { status: 'error', message: 'All fields are required.' };
  }
  if (password.length < 8) {
    return { status: 'error', message: 'New password must be at least 8 characters.' };
  }
  if (password !== passwordConfirmation) {
    return { status: 'error', message: 'New passwords do not match.' };
  }

  const token = await getAuthToken();
  if (!token) {
    return { status: 'error', message: 'Not signed in.' };
  }

  try {
    const res = await fetch(`${API_BASE_URL}/me/password`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      }),
    });

    if (!res.ok) {
      const body = await res.json().catch(() => null);
      return {
        status: 'error',
        message: body?.errors?.current_password?.[0] || body?.message || 'Could not update password.',
      };
    }

    return { status: 'success', message: 'Password updated.' };
  } catch {
    return { status: 'error', message: 'Could not reach the API.' };
  }
}
