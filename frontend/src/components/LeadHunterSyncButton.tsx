'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { syncLeadHunterAction } from '@/lib/admin-actions';

export function LeadHunterSyncButton() {
  const [message, setMessage] = useState<string | null>(null);
  const [isError, setIsError] = useState(false);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = () => {
    setMessage(null);
    startTransition(async () => {
      const result = await syncLeadHunterAction();
      setIsError(!result.ok);
      setMessage(result.message || (result.ok ? 'Done.' : 'Something went wrong.'));
      if (result.ok) router.refresh();
    });
  };

  return (
    <div className="border border-line bg-paper rounded-md p-5">
      <p className="font-mono text-[10px] tracking-widest text-manifest mb-1">LEAD HUNTER</p>
      <p className="text-sm text-manifest mb-3">
        Pull newly verified discoveries into the review queue as <span className="font-mono">verified</span> —
        still requires a manual Publish here before going live.
      </p>
      <button
        onClick={handle}
        disabled={pending}
        className="px-4 py-2 rounded-sm text-sm font-medium border border-ink text-ink hover:bg-ink hover:text-paper transition-colors disabled:opacity-50"
      >
        {pending ? 'Syncing…' : 'Sync New Discoveries'}
      </button>
      {message && (
        <p className={`text-xs font-mono mt-2 ${isError ? 'text-[#A63D3D]' : 'text-seal'}`}>{message}</p>
      )}
    </div>
  );
}
