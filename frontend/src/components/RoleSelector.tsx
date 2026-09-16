'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import { updateUserRoleAction } from '@/lib/admin-actions';

const ROLES = ['member', 'reviewer', 'admin'];

export function RoleSelector({
  userId,
  currentRole,
  isSelf,
}: {
  userId: number;
  currentRole: string;
  isSelf: boolean;
}) {
  const [role, setRole] = useState(currentRole);
  const [error, setError] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  const handleChange = (newRole: string) => {
    setError(null);
    const previous = role;
    setRole(newRole);
    startTransition(async () => {
      const result = await updateUserRoleAction(userId, newRole);
      if (!result.ok) {
        setRole(previous);
        setError(result.message || 'Could not update role.');
      } else {
        router.refresh();
      }
    });
  };

  return (
    <div className="flex flex-col items-end gap-1">
      <select
        value={role}
        disabled={pending || (isSelf && role === 'admin')}
        onChange={(e) => handleChange(e.target.value)}
        className="border border-line rounded-sm px-2 py-1 text-xs font-mono uppercase bg-paper outline-none focus:border-ink disabled:opacity-50"
        title={isSelf && role === 'admin' ? "You can't remove your own admin role" : undefined}
      >
        {ROLES.map((r) => (
          <option key={r} value={r}>
            {r}
          </option>
        ))}
      </select>
      {error && <p className="text-[10px] text-[#A63D3D] font-mono">{error}</p>}
    </div>
  );
}
