'use client';

import Link from 'next/link';
import { useActionState } from 'react';
import { forgotPasswordAction, ForgotPasswordFormState } from '@/lib/auth-actions';

const initialState: ForgotPasswordFormState = { status: 'idle' };
const inputClass =
  'w-full border border-line rounded-sm px-3 py-2.5 text-sm outline-none focus:border-ink bg-paper';
const labelClass = 'block text-xs font-medium text-ink mb-1.5';

export function ForgotPasswordForm() {
  const [state, formAction, pending] = useActionState(forgotPasswordAction, initialState);

  if (state.status === 'success') {
    return (
      <div className="border border-seal bg-seal-dim rounded-md p-5">
        <p className="font-mono text-[10px] tracking-widest text-seal mb-2">CHECK YOUR EMAIL</p>
        <p className="text-sm text-ink">{state.message}</p>
        <Link href="/login" className="inline-block mt-4 text-sm text-seal underline">
          Back to sign in
        </Link>
      </div>
    );
  }

  return (
    <form action={formAction} className="space-y-5">
      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3">{state.message}</div>
      )}
      <div>
        <label className={labelClass}>Email</label>
        <input name="email" type="email" required autoComplete="email" className={inputClass} />
      </div>
      <button
        type="submit"
        disabled={pending}
        className="w-full bg-ink text-paper py-3 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-50"
      >
        {pending ? 'Sending…' : 'Send Reset Link'}
      </button>
      <p className="text-sm text-manifest text-center">
        <Link href="/login" className="text-seal underline">
          Back to sign in
        </Link>
      </p>
    </form>
  );
}
