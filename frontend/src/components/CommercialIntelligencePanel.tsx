'use client';

import { useTransition } from 'react';
import { useRouter } from 'next/navigation';
import type { CommercialProfile, CommercialFact, CommercialRole } from '@/lib/entity-types';
import { buildCommercialProfileAction } from '@/lib/entity-actions';

export function CommercialIntelligencePanel({
  entityId,
  profile,
  facts,
  roles,
}: {
  entityId: string;
  profile: CommercialProfile | null;
  facts: CommercialFact[];
  roles: CommercialRole[];
}) {
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = () => {
    startTransition(async () => {
      await buildCommercialProfileAction(entityId);
      router.refresh();
    });
  };

  return (
    <section className="border border-line rounded-md p-4">
      <div className="flex items-center justify-between mb-3">
        <p className="font-mono text-[10px] tracking-widest text-manifest">COMMERCIAL PROFILE</p>
        <button onClick={handle} disabled={pending} className="text-xs text-seal underline disabled:opacity-50">
          {pending ? 'Building…' : profile ? 'Rebuild' : 'Build Profile'}
        </button>
      </div>

      {profile ? (
        <>
          {profile.primary_role && (
            <p className="font-mono text-sm text-ink">
              Primary role: <span className="text-seal">{profile.primary_role}</span>
              {profile.confidence !== null && ` (${Math.round((profile.confidence as number) * 100)}% confidence)`}
            </p>
          )}
          {profile.summary && <p className="text-sm text-ink/80 mt-2">{profile.summary}</p>}
        </>
      ) : (
        <p className="text-sm text-manifest italic">No profile built yet.</p>
      )}

      {roles.length > 0 && (
        <div className="mt-3 flex flex-wrap gap-2">
          {roles.slice(0, 5).map((r) => (
            <span key={r.id} className="font-mono text-[10px] border border-line rounded-sm px-2 py-0.5 text-ink">
              {r.role} ({Math.round(r.confidence * 100)}%)
            </span>
          ))}
        </div>
      )}

      {facts.length > 0 && (
        <div className="mt-3 space-y-1">
          {facts.slice(0, 5).map((f) => (
            <p key={f.id} className="font-mono text-xs text-manifest">
              {f.fact_type}: {f.value ?? '—'}
            </p>
          ))}
        </div>
      )}
    </section>
  );
}
