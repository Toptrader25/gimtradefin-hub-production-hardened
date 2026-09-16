'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { VerificationCheck } from '@/lib/lead-hunter-api';
import { recordCheckAction } from '@/lib/lead-hunter-actions';

const STATUS_OPTIONS = ['pending', 'verified', 'failed', 'not_applicable', 'needs_review'];

const STATUS_COLOR: Record<string, string> = {
  verified: 'text-seal',
  failed: 'text-[#A63D3D]',
  not_applicable: 'text-manifest',
  needs_review: 'text-signal',
  pending: 'text-manifest',
};

export function CaseChecklist({ caseId, checks }: { caseId: string; checks: VerificationCheck[] }) {
  const grouped = checks.reduce<Record<string, VerificationCheck[]>>((acc, c) => {
    (acc[c.category] ??= []).push(c);
    return acc;
  }, {});

  return (
    <div className="space-y-6">
      {Object.entries(grouped).map(([category, items]) => (
        <div key={category}>
          <p className="font-mono text-[10px] tracking-widest text-manifest uppercase mb-2">{category}</p>
          <div className="space-y-2">
            {items.map((check) => (
              <CheckRow key={check.id} caseId={caseId} check={check} />
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

function CheckRow({ caseId, check }: { caseId: string; check: VerificationCheck }) {
  const [status, setStatus] = useState(check.status);
  const [finding, setFinding] = useState(check.finding || '');
  const [reviewer, setReviewer] = useState('');
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handleSave = () => {
    if (!reviewer.trim()) return;
    startTransition(async () => {
      await recordCheckAction(caseId, check.check_code, status, reviewer, finding);
      router.refresh();
    });
  };

  return (
    <div className="border border-line rounded-sm p-3">
      <div className="flex items-center justify-between gap-3 mb-2">
        <span className="text-sm text-ink font-mono">{check.check_code}</span>
        <span className={`font-mono text-[10px] uppercase ${STATUS_COLOR[status] || ''}`}>{status}</span>
      </div>
      <div className="flex flex-wrap gap-2 items-center">
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="border border-line rounded-sm px-2 py-1 text-xs font-mono bg-paper outline-none focus:border-ink"
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        <input
          value={finding}
          onChange={(e) => setFinding(e.target.value)}
          placeholder="Finding (optional)"
          className="flex-1 min-w-[160px] border border-line rounded-sm px-2 py-1 text-xs outline-none focus:border-ink bg-paper"
        />
        <input
          value={reviewer}
          onChange={(e) => setReviewer(e.target.value)}
          placeholder="Your name"
          className="w-28 border border-line rounded-sm px-2 py-1 text-xs outline-none focus:border-ink bg-paper"
        />
        <button
          disabled={pending || !reviewer.trim()}
          onClick={handleSave}
          className="px-3 py-1 rounded-sm text-xs font-medium border border-ink text-ink hover:bg-ink hover:text-paper disabled:opacity-50 transition-colors"
        >
          {pending ? '…' : 'Save'}
        </button>
      </div>
    </div>
  );
}
