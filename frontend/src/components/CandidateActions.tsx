'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { resolveCandidateAction, createVerificationCaseAction } from '@/lib/lead-hunter-actions';
import { assessCandidateRiskAction, assessCandidateIntentAction } from '@/lib/entity-actions';

export function CandidateActions({ candidateId }: { candidateId: string }) {
  const [message, setMessage] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const run = (fn: () => Promise<{ ok: boolean; message?: string }>, doneText: string) => {
    setMessage(null);
    startTransition(async () => {
      const result = await fn();
      setMessage(result.ok ? doneText : result.message || 'Failed.');
      router.refresh();
    });
  };

  return (
    <div className="flex flex-col items-end gap-1 shrink-0">
      <div className="flex flex-wrap justify-end gap-2 max-w-[280px]">
        <button
          disabled={pending}
          onClick={() => run(() => assessCandidateRiskAction(candidateId), 'Risk assessed.')}
          className="px-3 py-1.5 rounded-sm text-xs font-medium border border-line text-manifest hover:border-ink disabled:opacity-50 transition-colors"
        >
          Assess Risk
        </button>
        <button
          disabled={pending}
          onClick={() => run(() => assessCandidateIntentAction(candidateId), 'Intent assessed.')}
          className="px-3 py-1.5 rounded-sm text-xs font-medium border border-line text-manifest hover:border-ink disabled:opacity-50 transition-colors"
        >
          Assess Intent
        </button>
        <button
          disabled={pending}
          onClick={() => run(() => resolveCandidateAction(candidateId), 'Resolved to entity.')}
          className="px-3 py-1.5 rounded-sm text-xs font-medium border border-line text-ink hover:border-ink disabled:opacity-50 transition-colors"
        >
          Resolve to Entity
        </button>
        <button
          disabled={pending}
          onClick={() => run(() => createVerificationCaseAction({ candidate_id: candidateId }), 'Case created.')}
          className="px-3 py-1.5 rounded-sm text-xs font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
        >
          Create Case
        </button>
      </div>
      {message && <p className="text-[10px] font-mono text-manifest">{message}</p>}
    </div>
  );
}
