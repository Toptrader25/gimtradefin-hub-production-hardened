import Link from 'next/link';
import { getCurrentUser } from '@/lib/auth';
import { logoutAction } from '@/lib/auth-actions';
import { getMyNotifications } from '@/lib/admin-api';
import { NotificationBell } from '@/components/NotificationBell';

export async function SiteHeader() {
  const user = await getCurrentUser();
  const notifications = user ? await getMyNotifications() : null;

  return (
    <header className="border-b border-line bg-paper/80 backdrop-blur sticky top-0 z-10">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
        <Link href="/" className="flex items-baseline gap-2 shrink-0">
          <span className="font-display font-bold text-lg text-ink tracking-tight">GiMtradefin</span>
          <span className="font-mono text-[10px] text-manifest tracking-widest">HUB</span>
        </Link>

        <nav className="hidden md:flex items-center gap-6 font-medium text-sm text-ink">
          <Link href="/opportunities" className="hover:text-seal transition-colors">
            Opportunities
          </Link>
          <Link href="/companies" className="hover:text-seal transition-colors">
            Companies
          </Link>
          {user && (
            <Link href="/dashboard" className="hover:text-seal transition-colors">
              Dashboard
            </Link>
          )}
          {user && (user.role === 'admin' || user.role === 'reviewer') && (
            <Link href="/admin" className="hover:text-seal transition-colors">
              Admin
            </Link>
          )}
        </nav>

        <div className="flex items-center gap-2 sm:gap-3 shrink-0">
          {user ? (
            <>
              <NotificationBell
                initialNotifications={notifications?.data ?? []}
                initialUnreadCount={notifications?.unread_count ?? 0}
              />
              <Link
                href="/dashboard"
                className="hidden sm:inline font-mono text-xs text-manifest hover:text-ink transition-colors"
                title={user.email}
              >
                {user.name}
              </Link>
              <form action={logoutAction}>
                <button className="text-sm text-manifest hover:text-ink transition-colors">
                  Sign out
                </button>
              </form>
            </>
          ) : (
            <Link href="/login" className="text-sm text-manifest hover:text-ink transition-colors">
              Sign in
            </Link>
          )}
          <Link
            href="/submit"
            className="text-sm font-medium bg-ink text-paper px-3 sm:px-4 py-2 rounded-sm hover:bg-ink/90 transition-colors"
          >
            <span className="sm:hidden">Submit</span>
            <span className="hidden sm:inline">Submit an Opportunity</span>
          </Link>
        </div>
      </div>

      {/* Narrow-viewport secondary nav */}
      <nav className="md:hidden border-t border-line px-4 py-2 flex gap-4 overflow-x-auto font-medium text-sm text-ink">
        <Link href="/opportunities" className="whitespace-nowrap hover:text-seal transition-colors">
          Opportunities
        </Link>
        <Link href="/companies" className="whitespace-nowrap hover:text-seal transition-colors">
          Companies
        </Link>
        {user && (
          <Link href="/dashboard" className="whitespace-nowrap hover:text-seal transition-colors">
            Dashboard
          </Link>
        )}
        {user && (user.role === 'admin' || user.role === 'reviewer') && (
          <Link href="/admin" className="whitespace-nowrap hover:text-seal transition-colors">
            Admin
          </Link>
        )}
        {!user && (
          <Link href="/register" className="whitespace-nowrap hover:text-seal transition-colors">
            Register
          </Link>
        )}
      </nav>
    </header>
  );
}
