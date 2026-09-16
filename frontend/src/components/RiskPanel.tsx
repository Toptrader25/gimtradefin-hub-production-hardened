'use client';

import { useTransition } from 'react';
import { useRouter } from 'next/navigation';
import type { RiskAssessment } from '@/lib/entity-types';

const BAND_COLOR: Record<string, string> = {
  low: 'text-seal',
  guarded: 'text-signal',
  high: 'text-[#A63D3D]',
  critical: 'text-[#A63D3D]',
};

export function RiskPanel({
  assessment,
  onAssess,
}: {
  assessment: RiskAssessment | null;
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
        <p className="font-mono text-[10px] tracking-widest text-manifest">RISK</p>
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
            <span className={`font-mono text-2xl font-medium ${BAND_COLOR[assessment.risk_band] || 'text-ink'}`}>
              {assessment.risk_score}
            </span>
            <span className="font-mono text-xs text-manifest">/100</span>
            <span className={`font-mono text-[10px] uppercase ml-2 ${BAND_COLOR[assessment.risk_band] || ''}`}>
              {assessment.risk_band}
            </span>
          </div>
          <p className="font-mono text-[10px] text-manifest mt-1 uppercase">
            Decision: {assessment.decision} · {new Date(assessment.calculated_at).toLocaleDateString()}
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
