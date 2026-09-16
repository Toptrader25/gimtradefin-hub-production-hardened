import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { CompanyVerifyActions } from '@/components/CompanyVerifyActions';
import { requireReviewer } from '@/lib/auth';
import { getAdminCompanies } from '@/lib/admin-api';

const STATUSES = ['unknown', 'pending', 'verified'];

export default async function AdminCompaniesPage({
  searchParams,
}: {
  searchParams: Promise<{ verification_status?: string; page?: string }>;
}) {
  await requireReviewer();
  const params = await searchParams;
  const page = params.page ? parseInt(params.page, 10) : 1;

  const result = await getAdminCompanies({ verification_status: params.verification_status, page });

  return (
    <>
      <SiteHeader />
      <main className="max-w-5xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin" className="font-mono text-xs text-manifest hover:text-ink">
          ← Dashboard
        </Link>
        <h1 className="font-display font-bold text-3xl text-ink mt-3 mb-6">Company Verification</h1>

        <div className="flex gap-2 mb-8 flex-wrap">
          <Link
            href="/admin/companies"
            className={`px-3 py-1.5 rounded-sm border text-xs font-mono ${
              !params.verification_status ? 'bg-ink text-paper border-ink' : 'border-line text-manifest hover:border-ink'
            }`}
          >
            ALL
          </Link>
          {STATUSES.map((s) => (
            <Link
              key={s}
              href={`/admin/companies?verification_status=${s}`}
              className={`px-3 py-1.5 rounded-sm border text-xs font-mono uppercase ${
                params.verification_status === s
                  ? 'bg-ink text-paper border-ink'
                  : 'border-line text-manifest hover:border-ink'
              }`}
            >
              {s}
            </Link>
          ))}
        </div>

        {!result && (
          <div className="border border-signal bg-signal-dim rounded-sm p-4 text-sm text-ink font-mono">
            Could not reach the admin API.
          </div>
        )}

        {result && result.data.length === 0 && (
          <div className="border border-line bg-paper rounded-sm p-10 text-center text-manifest">
            Nothing here.
          </div>
        )}

        <div className="space-y-3">
          {result?.data.map((c) => (
            <div
              key={c.id}
              className="flex items-center justify-between gap-4 border border-line bg-paper rounded-md p-4"
            >
              <Link href={`/admin/companies/${c.id}`} className="min-w-0 hover:text-seal transition-colors">
                <span className="font-mono text-[10px] uppercase tracking-widest text-manifest border border-line px-2 py-0.5 rounded-sm mr-2">
                  {c.verification_status}
                </span>
                <span className="font-display font-medium text-ink">{c.name}</span>
                <p className="font-mono text-xs text-manifest mt-1">
                  {c.country}
                  {c.role ? ` · ${c.role}` : ''} · {c.opportunities_count} opportunit{c.opportunities_count === 1 ? 'y' : 'ies'}
                </p>
              </Link>
              <CompanyVerifyActions companyId={c.id} currentStatus={c.verification_status} />
            </div>
          ))}
        </div>

        {result && result.meta.last_page > 1 && (
          <div className="flex justify-center gap-2 mt-10 font-mono text-sm">
            {Array.from({ length: result.meta.last_page }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/admin/companies?page=${p}${params.verification_status ? `&verification_status=${params.verification_status}` : ''}`}
                className={`w-8 h-8 flex items-center justify-center rounded-sm ${
                  p === result.meta.current_page ? 'bg-ink text-paper' : 'bg-chart-dim text-manifest'
                }`}
              >
                {p}
              </Link>
            ))}
          </div>
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
