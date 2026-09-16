import Link from 'next/link';

export function EmptyState({
  title,
  body,
  primaryHref,
  primaryLabel,
  secondaryHref,
  secondaryLabel,
}: {
  title: string;
  body: string;
  primaryHref?: string;
  primaryLabel?: string;
  secondaryHref?: string;
  secondaryLabel?: string;
}) {
  return (
    <div className="rounded-md border border-line bg-paper px-6 py-12 text-center">
      <h2 className="font-display font-semibold text-lg text-ink">{title}</h2>
      <p className="text-sm text-manifest mt-3 max-w-md mx-auto leading-relaxed">{body}</p>
      {(primaryHref || secondaryHref) && (
        <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
          {primaryHref && primaryLabel && (
            <Link
              href={primaryHref}
              className="bg-ink text-paper px-5 py-2.5 rounded-sm text-sm font-medium hover:bg-ink/90 transition-colors"
            >
              {primaryLabel}
            </Link>
          )}
          {secondaryHref && secondaryLabel && (
            <Link
              href={secondaryHref}
              className="border border-line text-ink px-5 py-2.5 rounded-sm text-sm font-medium hover:border-ink transition-colors"
            >
              {secondaryLabel}
            </Link>
          )}
        </div>
      )}
    </div>
  );
}
