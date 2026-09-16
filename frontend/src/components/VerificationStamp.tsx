export function VerificationStamp({ size = 'md' }: { size?: 'sm' | 'md' }) {
  const dims = size === 'sm' ? 'w-9 h-9' : 'w-14 h-14';
  const textSize = size === 'sm' ? 'text-[6px]' : 'text-[8px]';

  return (
    <div
      className={`${dims} relative rounded-full border-2 border-seal flex items-center justify-center shrink-0 select-none`}
      style={{ transform: 'rotate(-8deg)' }}
      aria-label="Verified by GiMtradefin"
    >
      <div className="absolute inset-[3px] rounded-full border border-seal/40" />
      <div className="flex flex-col items-center leading-none text-seal font-mono">
        <span className={`${textSize} tracking-wider font-medium`}>VERIFIED</span>
        <span className={size === 'sm' ? 'text-[9px]' : 'text-[13px]'}>&#10003;</span>
      </div>
    </div>
  );
}
