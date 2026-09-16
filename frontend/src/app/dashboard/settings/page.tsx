import Link from 'next/link';
import { SiteHeader } from '@/components/SiteHeader';
import { ChangePasswordForm } from '@/components/ChangePasswordForm';
import { requireUser } from '@/lib/auth';

export default async function SettingsPage() {
  const user = await requireUser();

  return (
    <>
      <SiteHeader />
      <main className="max-w-sm mx-auto px-6 py-12 flex-1 w-full">
        <Link href="/dashboard" className="font-mono text-xs text-manifest hover:text-ink">
          ← Dashboard
        </Link>
        <h1 className="font-display font-bold text-2xl text-ink mt-3 mb-1">Account Settings</h1>
        <p className="text-sm text-manifest mb-8">{user.email}</p>
        <ChangePasswordForm />
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
