'use client';

import { useActionState } from 'react';
import Link from 'next/link';
import { submitOpportunityAction, SubmitOpportunityFormState } from '@/lib/actions';

const initialState: SubmitOpportunityFormState = { status: 'idle' };

const inputClass =
  'w-full border border-line rounded-sm px-3 py-2.5 text-sm outline-none focus:border-ink bg-paper placeholder:text-manifest/60';
const labelClass = 'block text-xs font-medium text-ink mb-1.5';

export function SubmitOpportunityForm() {
  const [state, formAction, pending] = useActionState(submitOpportunityAction, initialState);

  if (state.status === 'success') {
    return (
      <div className="border border-seal bg-seal-dim rounded-md p-8 text-center">
        <p className="font-mono text-[10px] tracking-widest text-seal mb-2">SUBMITTED FOR REVIEW</p>
        <h2 className="font-display font-bold text-xl text-ink mb-3">Thank you.</h2>
        <p className="text-sm text-ink/80 max-w-md mx-auto leading-relaxed">{state.message}</p>
        <p className="text-xs text-manifest mt-4 max-w-md mx-auto">
          This will not appear publicly until a GiMtradefin reviewer has checked it against the
          evidence provided — see how that works on the homepage.
        </p>
        <Link href="/opportunities" className="inline-block mt-6 text-sm text-seal underline">
          Browse published opportunities →
        </Link>
      </div>
    );
  }

  return (
    <form action={formAction} className="space-y-8">
      {/* Honeypot — hidden from real users via CSS, bots fill it anyway */}
      <div className="absolute -left-[9999px]" aria-hidden="true">
        <label>
          Leave blank
          <input type="text" name="website" tabIndex={-1} autoComplete="off" />
        </label>
      </div>

      {state.status === 'error' && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3">{state.message}</div>
      )}

      <section>
        <p className="font-mono text-[10px] tracking-widest text-manifest mb-4">THE OPPORTUNITY</p>
        <div className="space-y-4">
          <div>
            <label className={labelClass}>Category *</label>
            <select name="category" required className={inputClass} defaultValue="">
              <option value="" disabled>Select one…</option>
              <option value="buying">Buying — I&apos;m sourcing something</option>
              <option value="selling">Selling — I have supply available</option>
              <option value="partnership">Partnership — distributor, JV, agent, investor</option>
            </select>
          </div>
          <div>
            <label className={labelClass}>Title *</label>
            <input
              name="title"
              required
              placeholder="e.g. Bulk buyer seeking refined palm oil, 500 MT/month"
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Full description *</label>
            <textarea
              name="description"
              required
              rows={5}
              placeholder="Include quantities, specifications, timelines, certifications required — the more evidence you give, the faster this can be verified."
              className={inputClass + ' resize-none'}
            />
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Quantity</label>
              <input name="quantity" placeholder="e.g. 1,000 MT" className={inputClass} />
            </div>
            <div>
              <label className={labelClass}>Country *</label>
              <input name="country" required placeholder="Where you're based" className={inputClass} />
            </div>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Preferred origin / market</label>
              <input name="preferred_origin" placeholder="e.g. West Africa, Ghana" className={inputClass} />
            </div>
            <div>
              <label className={labelClass}>Payment terms</label>
              <input name="payment_terms" placeholder="e.g. 60 days" className={inputClass} />
            </div>
          </div>
        </div>
      </section>

      <section>
        <p className="font-mono text-[10px] tracking-widest text-manifest mb-4">YOUR CONTACT DETAILS</p>
        <p className="text-xs text-manifest mb-4 -mt-2">
          Never published or shared automatically — only used by GiMtradefin to verify and follow up.
        </p>
        <div className="space-y-4">
          <div className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Full name *</label>
              <input name="submitted_by_name" required className={inputClass} />
            </div>
            <div>
              <label className={labelClass}>Email *</label>
              <input name="submitted_by_email" type="email" required className={inputClass} />
            </div>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Company</label>
              <input name="submitted_by_company" className={inputClass} />
            </div>
            <div>
              <label className={labelClass}>Phone / WhatsApp</label>
              <input name="submitted_by_phone" className={inputClass} />
            </div>
          </div>
        </div>
      </section>

      <button
        type="submit"
        disabled={pending}
        className="w-full bg-ink text-paper py-3.5 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-50"
      >
        {pending ? 'Submitting…' : 'Submit for Review'}
      </button>
    </form>
  );
}
