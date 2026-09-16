import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { ResetPasswordForm } from '@/components/ResetPasswordForm';

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>;
}) {
  const params = await searchParams;

  return (
    <>
      <SiteHeader />
      <main className="max-w-sm mx-auto px-6 py-16 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-4">RESET PASSWORD</p>
        <h1 className="font-display font-bold text-2xl text-ink mb-8">Choose a new password.</h1>

        {!params.token || !params.email ? (
          <div className="border border-signal bg-signal-dim rounded-sm p-4">
            <p className="text-sm text-ink">
              This reset link is missing required information. Request a new one.
            </p>
            <Link href="/forgot-password" className="inline-block mt-3 text-sm text-seal underline">
              Request a new link →
            </Link>
          </div>
        ) : (
          <ResetPasswordForm token={params.token} email={params.email} />
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
