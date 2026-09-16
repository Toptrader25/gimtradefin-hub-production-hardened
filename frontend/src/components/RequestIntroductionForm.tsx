'use client';

import { useActionState } from 'react';
import { submitEnquiryAction, EnquiryFormState } from '@/lib/actions';

const initialState: EnquiryFormState = { status: 'idle' };

export function RequestIntroductionForm({ opportunityId }: { opportunityId: number }) {
  const actionWithId = submitEnquiryAction.bind(null, opportunityId);
  const [state, formAction, pending] = useActionState(actionWithId, initialState);

  if (state.status === 'success') {
    return (
      <div className="border border-seal bg-seal-dim rounded-md p-5">
        <p className="font-mono text-[10px] tracking-widest text-seal mb-1">REQUEST SENT</p>
        <p className="text-sm text-ink">{state.message}</p>
      </div>
    );
  }

  return (
    <form action={formAction} className="border border-line bg-paper rounded-md p-5 space-y-4">
      <div>
        <p className="font-mono text-[10px] tracking-widest text-manifest mb-1">REQUEST INTRODUCTION</p>
        <p className="text-sm text-manifest">
          GiMtradefin facilitates the introduction directly — your details are never shared automatically.
        </p>
      </div>

      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-3 py-2">{state.message}</div>
      )}

      <div className="grid sm:grid-cols-2 gap-3">
        <input
          name="enquirer_name"
          placeholder="Full name"
          required
          className="border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
        />
        <input
          name="enquirer_email"
          type="email"
          placeholder="Email"
          required
          className="border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
        />
      </div>
      <input
        name="enquirer_company"
        placeholder="Company (optional)"
        className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
      />
      <textarea
        name="message"
        placeholder="Anything specific GiMtradefin should know before facilitating the introduction?"
        rows={3}
        className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper resize-none"
      />
      <button
        type="submit"
        disabled={pending}
        className="w-full bg-ink text-paper py-2.5 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-50"
      >
        {pending ? 'Sending…' : 'Request Introduction'}
      </button>
    </form>
  );
}
