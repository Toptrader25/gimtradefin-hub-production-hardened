'use client';

import { useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { updateCompanyVerificationAction } from '@/lib/admin-actions';

export function CompanyVerifyActions({
  companyId,
  currentStatus,
}: {
  companyId: number;
  currentStatus: string;
}) {
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handle = (status: string) => {
    startTransition(async () => {
      const result = await updateCompanyVerificationAction(companyId, status);
      if (result.ok) router.refresh();
    });
  };

  return (
    <div className="flex gap-2 shrink-0">
      {currentStatus !== 'verified' && (
        <button
          disabled={pending}
          onClick={() => handle('verified')}
          className="px-3 py-1.5 rounded-sm text-xs font-medium bg-seal text-white hover:opacity-90 disabled:opacity-50 transition-opacity"
        >
          {pending ? '…' : 'Verify'}
        </button>
      )}
      {currentStatus === 'verified' && (
        <button
          disabled={pending}
          onClick={() => handle('unknown')}
          className="px-3 py-1.5 rounded-sm text-xs font-medium border border-line text-manifest hover:border-ink disabled:opacity-50 transition-colors"
        >
          {pending ? '…' : 'Unverify'}
        </button>
      )}
    </div>
  );
}
