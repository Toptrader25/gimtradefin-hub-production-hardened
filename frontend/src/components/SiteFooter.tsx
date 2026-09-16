import Link from 'next/link';

export function SiteFooter() {
  return (
    <footer className="bg-ink text-paper/70 mt-auto">
      <div className="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row justify-between gap-8">
        <div>
          <span className="font-display font-bold text-paper">GiMtradefin</span>
          <p className="text-xs mt-2 max-w-xs text-paper/50 leading-relaxed">
            Evidence-grounded trade intelligence. Every opportunity traced to its source.
            AI analyzes; a person verifies.
          </p>
        </div>
        <div className="flex flex-wrap gap-x-8 gap-y-3 font-mono text-xs text-paper/60">
          <Link href="/opportunities" className="hover:text-paper transition-colors">Opportunities</Link>
          <Link href="/companies" className="hover:text-paper transition-colors">Companies</Link>
          <Link href="/submit" className="hover:text-paper transition-colors">Submit</Link>
          <Link href="/login" className="hover:text-paper transition-colors">Sign in</Link>
        </div>
        <div className="font-mono text-xs text-paper/40 md:text-right">
          &copy; {new Date().getFullYear()} GiMtradefin
        </div>
      </div>
    </footer>
  );
}
