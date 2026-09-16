import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { AuditLogList } from '@/components/AuditLogList';
import { LeadHunterSyncButton } from '@/components/LeadHunterSyncButton';
import { requireReviewer } from '@/lib/auth';
import { getAdminStats, getAuditLog } from '@/lib/admin-api';

export default async function AdminDashboardPage() {
  await requireReviewer();
  const [stats, recentActivity] = await Promise.all([getAdminStats(), getAuditLog()]);

  return (
    <>
      <SiteHeader />
      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-3">
          REVIEWER DASHBOARD
        </p>
        <h1 className="font-display font-bold text-3xl text-ink mb-8">Verification Queue</h1>

        {!stats ? (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        ) : (
          <>
            <div className="grid sm:grid-cols-3 gap-4 mb-6">
              <Link
                href="/admin/opportunities?status=discovered"
                className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
              >
                <div className="font-mono text-3xl text-ink">{stats.by_status.discovered}</div>
                <div className="text-sm text-manifest mt-1">Discovered — unreviewed</div>
              </Link>
              <Link
                href="/admin/opportunities?status=reviewing"
                className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
              >
                <div className="font-mono text-3xl text-ink">{stats.by_status.reviewing}</div>
                <div className="text-sm text-manifest mt-1">In review</div>
              </Link>
              <Link
                href="/admin/opportunities?status=verified"
                className="border border-line bg-paper rounded-md p-5 hover:border-ink transition-colors"
              >
                <div className="font-mono text-3xl text-seal">{stats.by_status.verified}</div>
                <div className="text-sm text-manifest mt-1">Verified — ready to publish</div>
              </Link>
            </div>

            <div className="grid sm:grid-cols-3 gap-4">
              <div className="border border-line rounded-md p-5">
                <div className="font-mono text-2xl text-ink">{stats.by_status.published}</div>
                <div className="text-xs text-manifest mt-1">Published (live)</div>
              </div>
              <div className="border border-line rounded-md p-5">
                <div className="font-mono text-2xl text-manifest">{stats.by_status.expired}</div>
                <div className="text-xs text-manifest mt-1">Expired</div>
              </div>
              <div className="border border-line rounded-md p-5">
                <div className="font-mono text-2xl text-[#A63D3D]">{stats.by_status.rejected}</div>
                <div className="text-xs text-manifest mt-1">Rejected</div>
              </div>
            </div>

            <div className="mt-10">
              <LeadHunterSyncButton />
            </div>

            <div className="mt-8 flex gap-6">
              <Link href="/admin/opportunities" className="text-sm text-seal underline">
                All opportunities →
              </Link>
              <Link href="/admin/companies" className="text-sm text-seal underline">
                Company verification →
              </Link>
              <Link href="/admin/users" className="text-sm text-seal underline">
                Manage users →
              </Link>
              <Link href="/admin/lead-hunter" className="text-sm text-seal underline">
                Lead Hunter →
              </Link>
            </div>

            <div className="mt-12">
              <p className="font-mono text-[10px] tracking-widest text-manifest mb-4">
                RECENT ACTIVITY
              </p>
              <AuditLogList entries={recentActivity} />
            </div>
          </>
        )}
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
