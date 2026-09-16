'use client';

import { useTransition } from 'react';
import { useRouter } from 'next/navigation';
import type { IntentAssessment } from '@/lib/entity-types';

export function IntentPanel({
  assessment,
  onAssess,
}: {
  assessment: IntentAssessment | null;
  onAssess: () => Promise<{ ok: boolean; message?: string }>;
}) {
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = () => {
    startTransition(async () => {
      await onAssess();
      router.refresh();
    });
  };

  return (
    <div className="border border-line rounded-md p-4">
      <div className="flex items-center justify-between mb-2">
        <p className="font-mono text-[10px] tracking-widest text-manifest">COMMERCIAL INTENT</p>
        <button
          onClick={handle}
          disabled={pending}
          className="text-xs text-seal underline disabled:opacity-50"
        >
          {pending ? 'Assessing…' : assessment ? 'Re-assess' : 'Run Assessment'}
        </button>
      </div>
      {assessment ? (
        <>
          <div className="flex items-baseline gap-2">
            <span className="font-mono text-2xl font-medium text-seal">{assessment.intent_score}</span>
            <span className="font-mono text-xs text-manifest">/100</span>
            <span className="font-mono text-[10px] uppercase text-manifest ml-2">{assessment.intent_stage}</span>
          </div>
          <p className="font-mono text-[10px] text-manifest mt-1">
            Buying temperature: {assessment.buying_temperature}/100 · Confidence:{' '}
            {Math.round(assessment.confidence * 100)}% · {assessment.independent_source_count} independent source
            {assessment.independent_source_count === 1 ? '' : 's'}
          </p>
          {assessment.top_signals && assessment.top_signals.length > 0 && (
            <ul className="text-xs text-ink mt-2 space-y-0.5">
              {assessment.top_signals.slice(0, 3).map((s, i) => (
                <li key={i}>• {s.signal}</li>
              ))}
            </ul>
          )}
        </>
      ) : (
        <p className="text-sm text-manifest italic">Not yet assessed.</p>
      )}
    </div>
  );
}
