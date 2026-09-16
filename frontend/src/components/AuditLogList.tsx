import { AuditLogEntry } from '@/lib/admin-api';

const ACTION_LABEL: Record<string, string> = {
  status_changed: 'Status changed',
  scores_updated: 'Scores updated',
  verification_changed: 'Verification changed',
  role_changed: 'Role changed',
};

function formatChanges(entry: AuditLogEntry): string {
  const c = entry.changes;
  if (!c) return '';
  if (entry.action === 'status_changed' || entry.action === 'verification_changed' || entry.action === 'role_changed') {
    const from = (c.from as string) ?? '—';
    const to = (c.to as string) ?? '—';
    return `${from} → ${to}`;
  }
  if (entry.action === 'scores_updated') {
    return 'See score panel for current values';
  }
  return JSON.stringify(c);
}

export function AuditLogList({ entries }: { entries: AuditLogEntry[] | null }) {
  if (entries === null) {
    return (
      <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
        Could not load audit history.
      </div>
    );
  }

  if (entries.length === 0) {
    return <p className="text-sm text-manifest italic">No history yet.</p>;
  }

  return (
    <div className="space-y-2">
      {entries.map((e) => (
        <div key={e.id} className="border border-line rounded-sm p-3 text-sm flex items-start justify-between gap-3">
          <div>
            <p className="text-ink">
              <span className="font-medium">{ACTION_LABEL[e.action] || e.action}</span>
              {formatChanges(e) && <span className="text-manifest"> — {formatChanges(e)}</span>}
              {e.action === 'status_changed' && e.changes?.notes ? (
                <span className="block text-xs text-manifest mt-1 italic">
                  &ldquo;{e.changes.notes as string}&rdquo;
                </span>
              ) : null}
            </p>
            <p className="font-mono text-[10px] text-manifest mt-1">
              {e.user ? e.user.name : 'System'} · {new Date(e.created_at).toLocaleString()}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
}
