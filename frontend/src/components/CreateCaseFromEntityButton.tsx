'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { createVerificationCaseAction } from '@/lib/lead-hunter-actions';

export function CreateCaseFromEntityButton({ entityId }: { entityId: string }) {
  const [message, setMessage] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = () => {
    setMessage(null);
    startTransition(async () => {
      const result = await createVerificationCaseAction({ entity_id: entityId });
      setMessage(result.ok ? 'Verification case created.' : result.message || 'Failed.');
      router.refresh();
    });
  };

  return (
    <div className="flex flex-col items-end gap-1">
      <button
        onClick={handle}
        disabled={pending}
        className="px-4 py-2 rounded-sm text-sm font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
      >
        {pending ? 'Creating…' : 'Create Verification Case'}
      </button>
      {message && <p className="text-[10px] font-mono text-manifest">{message}</p>}
    </div>
  );
}
