import { EvidencePipeline } from '@/components/EvidencePipeline';
import { SubmitOpportunityForm } from '@/components/SubmitOpportunityForm';
import { SiteHeader } from '@/components/SiteHeader';

export default function SubmitPage() {
  return (
    <>
      <SiteHeader />

      <main className="max-w-2xl mx-auto px-6 py-14 flex-1 w-full">
        <p className="font-mono text-xs tracking-[0.2em] text-seal font-medium mb-4">
          SUBMIT AN OPPORTUNITY
        </p>
        <h1 className="font-display font-bold text-3xl text-ink mb-4">
          Have a real trade opportunity?
        </h1>
        <p className="text-manifest leading-relaxed mb-8">
          Every submission goes through the same evidence-and-verification pipeline as anything
          GiMtradefin discovers itself — it won&apos;t appear publicly until a reviewer checks it.
        </p>

        <div className="border border-line bg-chart-dim/40 rounded-md p-5 mb-10 overflow-x-auto">
          <EvidencePipeline />
        </div>

        <div className="relative">
          <SubmitOpportunityForm />
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
