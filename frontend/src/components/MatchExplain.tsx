'use client';

import { useState } from 'react';
import type { MatchExplanation } from '@/lib/entity-types';
import { explainMatchAction } from '@/lib/entity-actions';

export function MatchExplain({ matchId }: { matchId: string }) {
  const [data, setData] = useState<MatchExplanation | null>(null);
  const [loading, setLoading] = useState(false);

  async function open() {
    setLoading(true);
    const r = await explainMatchAction(matchId);
    setData(r);
    setLoading(false);
  }

  return (
    <div className="mt-2">
      <button onClick={open} className="text-xs text-seal underline">
        {loading ? 'Loading…' : 'Explain match'}
      </button>
      {data && (
        <div className="mt-2 border border-line rounded-sm p-3 text-xs space-y-2">
          <p className="font-mono text-manifest">{data.narrative || 'Explainable match factors'}</p>
          {data.factors?.map((f, i) => (
            <div key={i} className="flex justify-between gap-3">
              <span>{f.factor}</span>
              <span className="font-mono">{Math.round(f.contribution)}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
