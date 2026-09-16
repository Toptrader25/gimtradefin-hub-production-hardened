'use client';

import { useActionState } from 'react';
import { changePasswordAction, ChangePasswordFormState } from '@/lib/auth-actions';

const initialState: ChangePasswordFormState = { status: 'idle' };
const inputClass =
  'w-full border border-line rounded-sm px-3 py-2.5 text-sm outline-none focus:border-ink bg-paper';
const labelClass = 'block text-xs font-medium text-ink mb-1.5';

export function ChangePasswordForm() {
  const [state, formAction, pending] = useActionState(changePasswordAction, initialState);

  return (
    <form action={formAction} className="space-y-5">
      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3">{state.message}</div>
      )}
      {state.status === 'success' && (
        <div className="text-sm text-ink bg-seal-dim border border-seal rounded-sm px-4 py-3">
          {state.message}
        </div>
      )}
      <div>
        <label className={labelClass}>Current password</label>
        <input
          name="current_password"
          type="password"
          required
          autoComplete="current-password"
          className={inputClass}
        />
      </div>
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
        {pending ? 'Updating…' : 'Update Password'}
      </button>
      <p className="text-xs text-manifest text-center">
        This signs out any other active sessions on other devices.
      </p>
    </form>
  );
}
