'use client';

import { useState, useTransition } from 'react';
import { analyzeCandidateLanguageAction, LanguageAnalysis } from '@/lib/entity-actions';

export function LanguageAnalysisButton({ candidateId, text }: { candidateId: string; text: string }) {
  const [result, setResult] = useState<LanguageAnalysis | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();

  const handle = () => {
    setError(null);
    startTransition(async () => {
      const outcome = await analyzeCandidateLanguageAction(candidateId, text);
      if (outcome.ok) {
        setResult(outcome.analysis || null);
      } else {
        setError(outcome.message || 'Failed.');
      }
    });
  };

  return (
    <div className="flex flex-col items-end gap-1">
      <button
        onClick={handle}
        disabled={pending}
        className="px-3 py-1.5 rounded-sm text-xs font-medium border border-line text-manifest hover:border-ink disabled:opacity-50 transition-colors"
      >
        {pending ? 'Analyzing…' : 'Analyze Language'}
      </button>
      {result && (
        <p className="text-[10px] font-mono text-seal">
          {result.detected_language || 'unknown'}
          {result.sentiment ? ` · ${result.sentiment}` : ''}
        </p>
      )}
      {error && <p className="text-[10px] font-mono text-[#A63D3D]">{error}</p>}
    </div>
  );
}
