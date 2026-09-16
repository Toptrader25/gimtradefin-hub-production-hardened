import { SiteHeader } from '@/components/SiteHeader';
import { ForgotPasswordForm } from '@/components/ForgotPasswordForm';

export default function ForgotPasswordPage() {
  return (
    <>
      <SiteHeader />
      <main className="max-w-sm mx-auto px-6 py-16 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-4">RESET PASSWORD</p>
        <h1 className="font-display font-bold text-2xl text-ink mb-3">Forgot your password?</h1>
        <p className="text-sm text-manifest mb-8">
          Enter your email and we&apos;ll send you a link to reset it.
        </p>
        <ForgotPasswordForm />
      </main>
      <footer className="bg-ink text-paper/50 mt-auto">
        <div className="max-w-6xl mx-auto px-6 py-8 font-mono text-xs">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </footer>
    </>
  );
}
