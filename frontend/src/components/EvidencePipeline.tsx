const STAGES = [
  { label: 'SOURCE', detail: 'Discovered' },
  { label: 'SIGNAL', detail: 'Detected' },
  { label: 'EVIDENCE', detail: 'Collected' },
  { label: 'SCORE', detail: 'Assessed' },
  { label: 'VERIFIED', detail: 'Human-reviewed' },
];

export function EvidencePipeline() {
  return (
    <div className="flex items-center w-full overflow-x-auto py-1" aria-hidden="true">
      {STAGES.map((stage, i) => (
        <div key={stage.label} className="flex items-center shrink-0">
          <div className="flex flex-col items-center gap-1.5 min-w-[92px]">
            <div
              className={`w-2 h-2 rounded-full ${
                i === STAGES.length - 1 ? 'bg-seal' : 'bg-ink'
              }`}
            />
            <span className="font-mono text-[10px] tracking-wider text-ink font-medium">
              {stage.label}
            </span>
            <span className="font-mono text-[9px] text-manifest">{stage.detail}</span>
          </div>
          {i < STAGES.length - 1 && <div className="pipeline-connector w-10 md:w-16 -mt-6" />}
        </div>
      ))}
    </div>
  );
}
