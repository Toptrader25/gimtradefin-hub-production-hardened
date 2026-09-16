'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { updateOpportunityScoresAction } from '@/lib/admin-actions';

const FIELDS: Array<{ key: string; label: string }> = [
  { key: 'commercial_intent_score', label: 'Commercial Intent' },
  { key: 'source_reliability_score', label: 'Source Reliability' },
  { key: 'evidence_strength_score', label: 'Evidence Strength' },
  { key: 'recency_score', label: 'Recency' },
  { key: 'company_confidence_score', label: 'Company Confidence' },
  { key: 'match_potential_score', label: 'Match Potential' },
  { key: 'overall_score', label: 'Overall' },
];

type ScoreValues = Record<string, number | null>;

export function ScoreEditor({
  opportunityId,
  initialScores,
}: {
  opportunityId: number;
  initialScores: ScoreValues;
}) {
  const [scores, setScores] = useState<ScoreValues>(initialScores);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handleChange = (key: string, raw: string) => {
    setSaved(false);
    const value = raw === '' ? null : Math.max(0, Math.min(100, parseInt(raw, 10) || 0));
    setScores((prev) => ({ ...prev, [key]: value }));
  };

  const handleSave = () => {
    setError(null);
    startTransition(async () => {
      const result = await updateOpportunityScoresAction(opportunityId, scores);
      if (result.ok) {
        setSaved(true);
        router.refresh();
      } else {
        setError(result.message || 'Could not save scores.');
      }
    });
  };

  return (
    <section className="border-t border-line pt-6 mb-6">
      <p className="font-mono text-[10px] tracking-widest text-manifest mb-1">
        SCORES — MANUAL, UNTIL THE AI SCORING ENGINE EXISTS
      </p>
      <p className="text-xs text-manifest mb-4">
        Leave blank rather than guess. A missing score is more honest than a made-up one.
      </p>

      {error && (
        <div className="text-sm text-white bg-[#A63D3D] rounded-sm px-4 py-3 mb-3">{error}</div>
      )}

      <div className="grid sm:grid-cols-2 gap-3 mb-4">
        {FIELDS.map((f) => (
          <div key={f.key} className="flex items-center justify-between gap-3 border border-line rounded-sm px-3 py-2">
            <label htmlFor={f.key} className="text-sm text-ink">
              {f.label}
            </label>
            <input
              id={f.key}
              type="number"
              min={0}
              max={100}
              value={scores[f.key] ?? ''}
              onChange={(e) => handleChange(f.key, e.target.value)}
              placeholder="—"
              className="w-16 text-right font-mono text-sm border border-line rounded-sm px-2 py-1 outline-none focus:border-ink bg-paper"
            />
          </div>
        ))}
      </div>

      <button
        onClick={handleSave}
        disabled={pending}
        className="px-4 py-2 rounded-sm text-sm font-medium border border-ink text-ink hover:bg-ink hover:text-paper transition-colors disabled:opacity-50"
      >
        {pending ? 'Saving…' : saved ? 'Saved ✓' : 'Save Scores'}
      </button>
    </section>
  );
}
