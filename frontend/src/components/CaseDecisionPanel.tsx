'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { decideCaseAction } from '@/lib/lead-hunter-actions';

const DECISIONS: Array<{ value: string; label: string; style: string }> = [
  { value: 'approve', label: 'Approve', style: 'bg-seal text-white hover:opacity-90' },
  { value: 'approve_with_conditions', label: 'Approve with Conditions', style: 'border border-seal text-seal hover:bg-seal-dim' },
  { value: 'request_more_evidence', label: 'Request More Evidence', style: 'border border-line text-ink hover:border-ink' },
  { value: 'hold', label: 'Hold', style: 'border border-line text-manifest hover:border-ink' },
  { value: 'reject', label: 'Reject', style: 'bg-[#A63D3D] text-white hover:opacity-90' },
];

export function CaseDecisionPanel({
  caseId,
  gates,
}: {
  caseId: string;
  gates: { critical_unresolved: number; required_failed: number; evidence_ready: boolean };
}) {
  const [reviewer, setReviewer] = useState('');
  const [reason, setReason] = useState('');
  const [confirming, setConfirming] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const commit = (decision: string) => {
    if (!reviewer.trim()) {
      setError('Your name is required to record a decision.');
      return;
    }
    setError(null);
    setConfirming(null);
    startTransition(async () => {
      const result = await decideCaseAction(caseId, decision, reviewer, reason);
      if (result.ok) {
        setSuccess(`Decision recorded: ${decision}.`);
        router.refresh();
      } else {
        setError(result.message || 'Could not record decision.');
      }
    });
  };

  const gatesBlocking = gates.critical_unresolved > 0 || gates.required_failed > 0 || !gates.evidence_ready;

  return (
    <div className="border-t border-line pt-6">
      <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">DECISION</p>

      {gatesBlocking && (
        <div className="border border-signal bg-signal-dim rounded-sm p-3 mb-4 text-xs text-ink font-mono">
          {gates.critical_unresolved > 0 && <p>{gates.critical_unresolved} critical check(s) still unresolved.</p>}
          {gates.required_failed > 0 && <p>{gates.required_failed} required check(s) failed.</p>}
          {!gates.evidence_ready && <p>Evidence checks not yet complete.</p>}
          <p className="mt-1 text-manifest">Approval may be rejected by the backend until these clear.</p>
        </div>
      )}

      {error && <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3 mb-3">{error}</div>}
      {success && (
        <div className="text-sm text-ink bg-seal-dim border border-seal rounded-sm px-4 py-3 mb-3">{success}</div>
      )}

      <div className="grid sm:grid-cols-2 gap-3 mb-3">
        <input
          value={reviewer}
          onChange={(e) => setReviewer(e.target.value)}
          placeholder="Your name (required)"
          className="border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
        />
      </div>
      <textarea
        value={reason}
        onChange={(e) => setReason(e.target.value)}
        placeholder="Reason (recommended)"
        rows={2}
        className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper resize-none mb-3"
      />

      <div className="flex flex-wrap gap-2">
        {DECISIONS.map((d) =>
          confirming === d.value ? (
            <div key={d.value} className="flex items-center gap-2 border border-signal bg-signal-dim rounded-sm px-2 py-1">
              <span className="text-xs text-ink">Confirm {d.label.toLowerCase()}?</span>
              <button
                disabled={pending}
                onClick={() => commit(d.value)}
                className={`px-3 py-1 rounded-sm text-xs font-medium disabled:opacity-50 ${d.style}`}
              >
                {pending ? '…' : 'Confirm'}
              </button>
              <button onClick={() => setConfirming(null)} className="px-3 py-1 rounded-sm text-xs text-manifest hover:text-ink">
                Cancel
              </button>
            </div>
          ) : (
            <button
              key={d.value}
              disabled={pending}
              onClick={() => setConfirming(d.value)}
              className={`px-4 py-2 rounded-sm text-sm font-medium transition-colors disabled:opacity-50 ${d.style}`}
            >
              {d.label}
            </button>
          )
        )}
      </div>
    </div>
  );
}
