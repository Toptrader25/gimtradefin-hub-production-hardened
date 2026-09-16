import { redirect } from 'next/navigation';
import { SiteHeader } from '@/components/SiteHeader';
import { RegisterForm } from '@/components/RegisterForm';
import { getCurrentUser } from '@/lib/auth';

export default async function RegisterPage() {
  const user = await getCurrentUser();
  if (user) {
    redirect('/');
  }

  return (
    <>
      <SiteHeader />
      <main className="max-w-sm mx-auto px-6 py-16 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-4">CREATE ACCOUNT</p>
        <h1 className="font-display font-bold text-2xl text-ink mb-8">Join GiMtradefin.</h1>
        <RegisterForm />
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
