'use client';

import Link from 'next/link';
import { useActionState } from 'react';
import { registerAction, AuthFormState } from '@/lib/auth-actions';

const initialState: AuthFormState = { status: 'idle' };
const inputClass =
  'w-full border border-line rounded-sm px-3 py-2.5 text-sm outline-none focus:border-ink bg-paper';
const labelClass = 'block text-xs font-medium text-ink mb-1.5';

export function RegisterForm() {
  const [state, formAction, pending] = useActionState(registerAction, initialState);

  return (
    <form action={formAction} className="space-y-5">
      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3">{state.message}</div>
      )}
      <div>
        <label className={labelClass}>Full name</label>
        <input name="name" required autoComplete="name" className={inputClass} />
      </div>
      <div>
        <label className={labelClass}>Email</label>
        <input name="email" type="email" required autoComplete="email" className={inputClass} />
      </div>
      <div>
        <label className={labelClass}>Password</label>
        <input
          name="password"
          type="password"
          required
          minLength={8}
          autoComplete="new-password"
          className={inputClass}
        />
        <p className="text-xs text-manifest mt-1">At least 8 characters.</p>
      </div>
      <div>
        <label className={labelClass}>Confirm password</label>
        <input
          name="password_confirmation"
          type="password"
          required
          autoComplete="new-password"
          className={inputClass}
        />
      </div>
      <button
        type="submit"
        disabled={pending}
        className="w-full bg-ink text-paper py-3 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-50"
      >
        {pending ? 'Creating account…' : 'Create Account'}
      </button>
      <p className="text-sm text-manifest text-center">
        Already have an account?{' '}
        <Link href="/login" className="text-seal underline">
          Sign in
        </Link>
      </p>
    </form>
  );
}
