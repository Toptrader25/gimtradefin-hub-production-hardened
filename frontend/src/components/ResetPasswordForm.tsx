'use client';

import Link from 'next/link';
import { useActionState } from 'react';
import { resetPasswordAction, ResetPasswordFormState } from '@/lib/auth-actions';

const initialState: ResetPasswordFormState = { status: 'idle' };
const inputClass =
  'w-full border border-line rounded-sm px-3 py-2.5 text-sm outline-none focus:border-ink bg-paper';
const labelClass = 'block text-xs font-medium text-ink mb-1.5';

export function ResetPasswordForm({ token, email }: { token: string; email: string }) {
  const [state, formAction, pending] = useActionState(resetPasswordAction, initialState);

  if (state.status === 'success') {
    return (
      <div className="border border-seal bg-seal-dim rounded-md p-5">
        <p className="font-mono text-[10px] tracking-widest text-seal mb-2">DONE</p>
        <p className="text-sm text-ink">{state.message}</p>
        <Link href="/login" className="inline-block mt-4 text-sm text-seal underline">
          Sign in →
        </Link>
      </div>
    );
  }

  return (
    <form action={formAction} className="space-y-5">
      <input type="hidden" name="token" value={token} />
      <input type="hidden" name="email" value={email} />

      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3">{state.message}</div>
      )}
      <div>
        <label className={labelClass}>New password</label>
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
        <label className={labelClass}>Confirm new password</label>
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
        {pending ? 'Resetting…' : 'Reset Password'}
      </button>
    </form>
  );
}
