import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { RoleSelector } from '@/components/RoleSelector';
import { requireAdmin } from '@/lib/auth';
import { getAdminUsers } from '@/lib/admin-api';

export default async function AdminUsersPage() {
  const currentUser = await requireAdmin();
  const users = await getAdminUsers();

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin" className="font-mono text-xs text-manifest hover:text-ink">
          ← Dashboard
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-2">Users &amp; Roles</h1>
        <p className="text-sm text-manifest mb-8">
          Admin-only — separate from content review, since granting roles is more sensitive than
          approving trade leads.
        </p>

        {users === null && (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        )}

        <div className="space-y-2">
          {users?.map((u) => (
            <div
              key={u.id}
              className="flex items-center justify-between gap-4 border border-line bg-paper rounded-md p-4"
            >
              <div>
                <p className="font-display font-medium text-ink">
                  {u.name} {u.id === currentUser.id && <span className="text-xs text-manifest">(you)</span>}
                </p>
                <p className="font-mono text-xs text-manifest mt-0.5">{u.email}</p>
              </div>
              <RoleSelector userId={u.id} currentRole={u.role} isSelf={u.id === currentUser.id} />
            </div>
          ))}
        </div>
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
