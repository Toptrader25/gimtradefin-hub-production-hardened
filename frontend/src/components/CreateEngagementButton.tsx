'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { createEngagementFromEntityAction } from '@/lib/engagement-actions';

export function CreateEngagementButton({ entityId, entityName }: { entityId: string; entityName: string }) {
  const [message, setMessage] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = () => {
    setMessage(null);
    startTransition(async () => {
      const result = await createEngagementFromEntityAction(entityId, `Engage: ${entityName}`);
      setMessage(result.ok ? 'Engagement opportunity created.' : result.message || 'Failed.');
      router.refresh();
    });
  };

  return (
    <div className="flex flex-col items-end gap-1">
      <button
        onClick={handle}
        disabled={pending}
        className="px-4 py-2 rounded-sm text-sm font-medium border border-ink text-ink hover:bg-ink hover:text-paper disabled:opacity-50 transition-colors"
      >
        {pending ? 'Creating…' : 'Start Engagement'}
      </button>
      {message && <p className="text-[10px] font-mono text-manifest">{message}</p>}
    </div>
  );
}
