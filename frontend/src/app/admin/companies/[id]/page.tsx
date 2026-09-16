import Link from 'next/link';
import { notFound } from 'next/navigation';
import { SiteHeader } from '@/components/SiteHeader';
import { CompanyVerifyActions } from '@/components/CompanyVerifyActions';
import { AuditLogList } from '@/components/AuditLogList';
import { requireReviewer } from '@/lib/auth';
import { getAdminCompany, getCompanyAuditLog } from '@/lib/admin-api';

const STATUS_COLOR: Record<string, string> = {
  published: 'text-seal',
  verified: 'text-seal',
  rejected: 'text-[#A63D3D]',
  expired: 'text-manifest',
};

export default async function AdminCompanyDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  await requireReviewer();
  const { id } = await params;
  const companyId = parseInt(id, 10);
  if (Number.isNaN(companyId)) notFound();

  const [company, auditLog] = await Promise.all([
    getAdminCompany(companyId),
    getCompanyAuditLog(companyId),
  ]);

  if (!company) {
    return (
      <>
        <SiteHeader />
        <main className="max-w-2xl mx-auto px-6 py-20 text-center flex-1 w-full">
          <p className="text-manifest">Could not load this company, or it doesn&apos;t exist.</p>
          <Link href="/admin/companies" className="inline-block mt-4 text-sm text-seal underline">
            Back to queue
          </Link>
        </main>
      </>
    );
  }

  return (
    <>
      <SiteHeader />
      <main className="max-w-3xl mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/admin/companies" className="font-mono text-xs text-manifest hover:text-ink">
          ← Companies
        </Link>

        <div className="flex items-start justify-between gap-4 mt-4 mb-8">
          <div>
            <span className="font-mono text-[10px] uppercase tracking-widest border border-line px-2 py-1 rounded-sm">
              {company.verification_status}
            </span>
            <h1 className="font-display font-bold text-2xl text-ink mt-3">{company.name}</h1>
            <p className="font-mono text-sm text-manifest mt-1">
              {company.country}
              {company.industry ? ` · ${company.industry}` : ''}
              {company.role ? ` · ${company.role}` : ''}
            </p>
          </div>
          <CompanyVerifyActions companyId={company.id} currentStatus={company.verification_status} />
        </div>

        {company.description && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">ABOUT</p>
            <p className="text-ink leading-relaxed">{company.description}</p>
          </section>
        )}

        {company.website && (
          <section className="mb-8">
            <p className="font-mono text-[10px] tracking-widest text-manifest mb-2">WEBSITE</p>
            <a href={company.website} target="_blank" rel="noopener noreferrer nofollow" className="text-sm text-seal underline">
              {company.website}
            </a>
          </section>
        )}

        <section className="mb-8">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">
            ALL OPPORTUNITIES ({company.opportunities_count}) — INCLUDING UNPUBLISHED
          </p>
          {company.opportunities.length === 0 ? (
            <p className="text-sm text-manifest italic">None yet.</p>
          ) : (
            <div className="space-y-2">
              {company.opportunities.map((o) => (
                <Link
                  key={o.id}
                  href={`/admin/opportunities/${o.id}`}
                  className="flex items-center justify-between gap-3 border border-line rounded-sm p-3 hover:border-ink transition-colors"
                >
                  <span className="text-sm text-ink">{o.title}</span>
                  <span className={`font-mono text-[10px] uppercase ${STATUS_COLOR[o.status] || 'text-manifest'}`}>
                    {o.status}
                  </span>
                </Link>
              ))}
            </div>
          )}
        </section>

        <section className="border-t border-line pt-6">
          <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">HISTORY</p>
          <AuditLogList entries={auditLog} />
        </section>
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
