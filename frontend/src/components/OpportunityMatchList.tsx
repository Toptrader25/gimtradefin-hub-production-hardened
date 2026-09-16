'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import type { OpportunityMatch } from '@/lib/entity-types';
import { recordMatchFeedbackAction } from '@/lib/entity-actions';
import { MatchExplain } from '@/components/MatchExplain';

const DECISIONS = ['good_match', 'poor_match', 'review'];

export function OpportunityMatchList({ matches }: { matches: OpportunityMatch[] }) {
  if (matches.length === 0) {
    return <p className="text-sm text-manifest italic">No matches computed yet.</p>;
  }

  return (
    <div className="space-y-2">
      {matches.map((m) => (
        <MatchRow key={m.id} match={m} />
      ))}
    </div>
  );
}

function MatchRow({ match }: { match: OpportunityMatch }) {
  const [showFeedback, setShowFeedback] = useState(false);
  const [notes, setNotes] = useState('');
  const [sent, setSent] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const submit = (decision: string) => {
    startTransition(async () => {
      const result = await recordMatchFeedbackAction(match.id, decision, notes);
      setSent(result.ok ? `Recorded: ${decision}` : result.message || 'Failed.');
      router.refresh();
    });
  };

  return (
    <div className="border border-line rounded-sm p-3">
      <div className="flex items-center justify-between gap-3">
        <div>
          <p className="text-sm text-ink font-mono">{match.matched_entity_name || match.matched_entity_id}</p>
          {match.match_type && <p className="font-mono text-[10px] text-manifest mt-0.5">{match.match_type}</p>}
        </div>
        <span className="font-mono text-sm text-seal shrink-0">{Math.round(match.score)}</span>
      </div>

      <MatchExplain matchId={match.id} />

      {!showFeedback ? (
        <button onClick={() => setShowFeedback(true)} className="text-xs text-seal underline mt-2">
          Give feedback
        </button>
      ) : (
        <div className="mt-3 space-y-2">
          {sent && <p className="text-xs font-mono text-manifest">{sent}</p>}
          <div className="flex gap-2">
            <input
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Notes (optional)"
              className="flex-1 border border-line rounded-sm px-2 py-1 text-xs outline-none focus:border-ink bg-paper"
            />
          </div>
          <div className="flex gap-2">
            {DECISIONS.map((d) => (
              <button
                key={d}
                disabled={pending}
                onClick={() => submit(d)}
                className="px-2 py-1 rounded-sm text-[10px] font-mono uppercase border border-line text-ink hover:border-ink disabled:opacity-50 transition-colors"
              >
                {d.replace('_', ' ')}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
