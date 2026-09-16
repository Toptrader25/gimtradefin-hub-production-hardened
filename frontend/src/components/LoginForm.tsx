'use client';

import Link from 'next/link';
import { useActionState } from 'react';
import { loginAction, AuthFormState } from '@/lib/auth-actions';

const initialState: AuthFormState = { status: 'idle' };
const inputClass =
  'w-full border border-line rounded-sm px-3 py-2.5 text-sm outline-none focus:border-ink bg-paper';
const labelClass = 'block text-xs font-medium text-ink mb-1.5';

export function LoginForm() {
  const [state, formAction, pending] = useActionState(loginAction, initialState);

  return (
    <form action={formAction} className="space-y-5">
      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3">{state.message}</div>
      )}
      <div>
        <label className={labelClass}>Email</label>
        <input name="email" type="email" required autoComplete="email" className={inputClass} />
      </div>
      <div>
        <div className="flex items-center justify-between mb-1.5">
          <label className={labelClass + ' mb-0'}>Password</label>
          <Link href="/forgot-password" className="text-xs text-seal underline">
            Forgot password?
          </Link>
        </div>
        <input
          name="password"
          type="password"
          required
          autoComplete="current-password"
          className={inputClass}
        />
      </div>
      <button
        type="submit"
        disabled={pending}
        className="w-full bg-ink text-paper py-3 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-50"
      >
        {pending ? 'Signing in…' : 'Sign In'}
      </button>
      <p className="text-sm text-manifest text-center">
        Don&apos;t have an account?{' '}
        <Link href="/register" className="text-seal underline">
          Register
        </Link>
      </p>
    </form>
  );
}
