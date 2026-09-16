'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { startCaseAction } from '@/lib/lead-hunter-actions';

export function StartCaseButton({ caseId }: { caseId: string }) {
  const [reviewer, setReviewer] = useState('');
  const [showInput, setShowInput] = useState(false);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  if (!showInput) {
    return (
      <button
        onClick={() => setShowInput(true)}
        className="px-4 py-2 rounded-sm text-sm font-medium border border-ink text-ink hover:bg-ink hover:text-paper transition-colors shrink-0"
      >
        Start Review
      </button>
    );
  }

  return (
    <div className="flex items-center gap-2 shrink-0">
      <input
        value={reviewer}
        onChange={(e) => setReviewer(e.target.value)}
        placeholder="Your name"
        className="w-28 border border-line rounded-sm px-2 py-1.5 text-xs outline-none focus:border-ink bg-paper"
      />
      <button
        disabled={pending || !reviewer.trim()}
        onClick={() =>
          startTransition(async () => {
            await startCaseAction(caseId, reviewer);
            router.refresh();
          })
        }
        className="px-3 py-1.5 rounded-sm text-xs font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
      >
        {pending ? '…' : 'Confirm'}
      </button>
    </div>
  );
}
