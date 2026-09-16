'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { updateOpportunityStatusAction } from '@/lib/admin-actions';

const ACTIONS: Array<{ status: string; label: string; style: string; needsConfirm: boolean }> = [
  { status: 'reviewing', label: 'Mark In Review', style: 'border border-line text-ink hover:border-ink', needsConfirm: false },
  { status: 'verified', label: 'Mark Verified', style: 'bg-seal text-white hover:opacity-90', needsConfirm: false },
  { status: 'published', label: 'Publish', style: 'bg-ink text-paper hover:bg-ink/90', needsConfirm: true },
  { status: 'rejected', label: 'Reject', style: 'bg-[#A63D3D] text-white hover:opacity-90', needsConfirm: true },
  { status: 'expired', label: 'Mark Expired', style: 'border border-line text-manifest hover:border-ink', needsConfirm: false },
];

export function ReviewActions({
  opportunityId,
  currentStatus,
}: {
  opportunityId: number;
  currentStatus: string;
}) {
  const [notes, setNotes] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [confirming, setConfirming] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const commit = (status: string) => {
    setError(null);
    setConfirming(null);
    startTransition(async () => {
      const result = await updateOpportunityStatusAction(opportunityId, status, notes);
      if (result.ok) {
        setNotes('');
        router.refresh();
      } else {
        setError(result.message || 'Could not update status.');
      }
    });
  };

  const handleClick = (action: (typeof ACTIONS)[number]) => {
    if (action.needsConfirm && confirming !== action.status) {
      setConfirming(action.status);
      return;
    }
    commit(action.status);
  };

  return (
    <section className="border-t border-line pt-6">
      <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">REVIEW ACTION</p>

      {error && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3 mb-3">{error}</div>
      )}

      <textarea
        value={notes}
        onChange={(e) => setNotes(e.target.value)}
        placeholder="Optional notes for the audit log…"
        rows={2}
        className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper resize-none mb-3"
      />
      <div className="flex flex-wrap gap-2">
        {ACTIONS.filter((a) => a.status !== currentStatus).map((a) =>
          confirming === a.status ? (
            <div key={a.status} className="flex items-center gap-2 border border-signal bg-signal-dim rounded-sm px-2 py-1">
              <span className="text-xs text-ink">
                {a.status === 'published' ? 'Make this public?' : 'Reject this submission?'}
              </span>
              <button
                disabled={pending}
                onClick={() => commit(a.status)}
                className={`px-3 py-1 rounded-sm text-xs font-medium disabled:opacity-50 ${a.style}`}
              >
                {pending ? '…' : 'Confirm'}
              </button>
              <button
                disabled={pending}
                onClick={() => setConfirming(null)}
                className="px-3 py-1 rounded-sm text-xs text-manifest hover:text-ink"
              >
                Cancel
              </button>
            </div>
          ) : (
            <button
              key={a.status}
              disabled={pending}
              onClick={() => handleClick(a)}
              className={`px-4 py-2 rounded-sm text-sm font-medium transition-colors disabled:opacity-50 ${a.style}`}
            >
              {pending ? '…' : a.label}
            </button>
          )
        )}
      </div>
    </section>
  );
}
