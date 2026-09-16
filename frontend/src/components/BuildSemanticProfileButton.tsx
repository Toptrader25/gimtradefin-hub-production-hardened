'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { buildSemanticProfileAction } from '@/lib/entity-actions';

export function BuildSemanticProfileButton({ entityId }: { entityId: string }) {
  const [message, setMessage] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = () => {
    setMessage(null);
    startTransition(async () => {
      const result = await buildSemanticProfileAction(entityId);
      setMessage(result.ok ? 'Built.' : result.message || 'Failed.');
      router.refresh();
    });
  };

  return (
    <div className="flex items-center gap-2">
      <button onClick={handle} disabled={pending} className="text-xs text-seal underline disabled:opacity-50">
        {pending ? 'Building…' : 'Build semantic profile'}
      </button>
      {message && <span className="text-[10px] font-mono text-manifest">{message}</span>}
    </div>
  );
}
