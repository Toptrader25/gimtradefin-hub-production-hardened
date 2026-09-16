'use client';

import { useState, useTransition } from 'react';
import { useRouter } from 'next/navigation';
import {
  changeEngagementStageAction,
  addEngagementContactAction,
  logEngagementActivityAction,
  recordEngagementOutcomeAction,
} from '@/lib/engagement-actions';

const STAGES = ['approved', 'nurture', 'contacted', 'engaged', 'qualified', 'meeting', 'rfq', 'won', 'lost'];
const ACTIVITY_TYPES = ['call', 'email', 'note', 'meeting'];
const OUTCOMES = ['positive', 'neutral', 'negative', 'won', 'lost'];

export function EngagementActionPanel({ opportunityId }: { opportunityId: string }) {
  const [tab, setTab] = useState<'stage' | 'contact' | 'activity' | 'outcome'>('stage');
  const [pending, startTransition] = useTransition();
  const [message, setMessage] = useState<string | null>(null);
  const router = useRouter();

  // shared "actor" field across all four forms
  const [actor, setActor] = useState('');

  // stage
  const [stage, setStage] = useState(STAGES[0]);
  const [reason, setReason] = useState('');

  // contact
  const [contactName, setContactName] = useState('');
  const [contactRole, setContactRole] = useState('');

  // activity
  const [activityType, setActivityType] = useState(ACTIVITY_TYPES[0]);
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');

  // outcome
  const [outcome, setOutcome] = useState(OUTCOMES[0]);
  const [score, setScore] = useState('');
  const [notes, setNotes] = useState('');

  const run = (fn: () => Promise<{ ok: boolean; message?: string }>) => {
    if (!actor.trim()) {
      setMessage('Your name is required.');
      return;
    }
    setMessage(null);
    startTransition(async () => {
      const result = await fn();
      setMessage(result.ok ? 'Saved.' : result.message || 'Failed.');
      router.refresh();
    });
  };

  const tabClass = (t: string) =>
    `px-3 py-1.5 rounded-sm text-xs font-mono uppercase border ${
      tab === t ? 'bg-ink text-paper border-ink' : 'border-line text-manifest hover:border-ink'
    }`;

  return (
    <section className="border-t border-line pt-6">
      <p className="font-mono text-[10px] tracking-widest text-manifest mb-3">RECORD ACTIVITY</p>

      {message && <p className="text-xs font-mono text-manifest mb-3">{message}</p>}

      <div className="flex gap-2 mb-4 flex-wrap">
        <button onClick={() => setTab('stage')} className={tabClass('stage')}>
          Change Stage
        </button>
        <button onClick={() => setTab('contact')} className={tabClass('contact')}>
          Add Contact
        </button>
        <button onClick={() => setTab('activity')} className={tabClass('activity')}>
          Log Activity
        </button>
        <button onClick={() => setTab('outcome')} className={tabClass('outcome')}>
          Record Outcome
        </button>
      </div>

      <input
        value={actor}
        onChange={(e) => setActor(e.target.value)}
        placeholder="Your name (required)"
        className="w-48 border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper mb-3"
      />

      {tab === 'stage' && (
        <div className="space-y-3">
          <div className="flex gap-2 flex-wrap">
            {STAGES.map((s) => (
              <button
                key={s}
                onClick={() => setStage(s)}
                className={`px-3 py-1.5 rounded-sm text-xs font-mono uppercase border ${
                  stage === s ? 'bg-ink text-paper border-ink' : 'border-line text-manifest'
                }`}
              >
                {s}
              </button>
            ))}
          </div>
          <input
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="Reason"
            className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
          />
          <button
            disabled={pending}
            onClick={() => run(() => changeEngagementStageAction(opportunityId, stage, actor, reason))}
            className="px-4 py-2 rounded-sm text-sm font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
          >
            {pending ? '…' : 'Update Stage'}
          </button>
        </div>
      )}

      {tab === 'contact' && (
        <div className="space-y-3">
          <div className="grid sm:grid-cols-2 gap-3">
            <input
              value={contactName}
              onChange={(e) => setContactName(e.target.value)}
              placeholder="Contact name"
              className="border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
            />
            <input
              value={contactRole}
              onChange={(e) => setContactRole(e.target.value)}
              placeholder="Role (optional)"
              className="border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
            />
          </div>
          <button
            disabled={pending || !contactName.trim()}
            onClick={() => run(() => addEngagementContactAction(opportunityId, contactName, contactRole))}
            className="px-4 py-2 rounded-sm text-sm font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
          >
            {pending ? '…' : 'Add Contact'}
          </button>
        </div>
      )}

      {tab === 'activity' && (
        <div className="space-y-3">
          <div className="flex gap-2 flex-wrap">
            {ACTIVITY_TYPES.map((t) => (
              <button
                key={t}
                onClick={() => setActivityType(t)}
                className={`px-3 py-1.5 rounded-sm text-xs font-mono uppercase border ${
                  activityType === t ? 'bg-ink text-paper border-ink' : 'border-line text-manifest'
                }`}
              >
                {t}
              </button>
            ))}
          </div>
          <input
            value={subject}
            onChange={(e) => setSubject(e.target.value)}
            placeholder="Subject"
            className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
          />
          <textarea
            value={body}
            onChange={(e) => setBody(e.target.value)}
            placeholder="Notes"
            rows={2}
            className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper resize-none"
          />
          <button
            disabled={pending}
            onClick={() => run(() => logEngagementActivityAction(opportunityId, activityType, actor, subject, body))}
            className="px-4 py-2 rounded-sm text-sm font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
          >
            {pending ? '…' : 'Log Activity'}
          </button>
        </div>
      )}

      {tab === 'outcome' && (
        <div className="space-y-3">
          <div className="flex gap-2 flex-wrap">
            {OUTCOMES.map((o) => (
              <button
                key={o}
                onClick={() => setOutcome(o)}
                className={`px-3 py-1.5 rounded-sm text-xs font-mono uppercase border ${
                  outcome === o ? 'bg-ink text-paper border-ink' : 'border-line text-manifest'
                }`}
              >
                {o}
              </button>
            ))}
          </div>
          <div className="grid sm:grid-cols-2 gap-3">
            <input
              value={score}
              onChange={(e) => setScore(e.target.value)}
              type="number"
              min={0}
              max={100}
              placeholder="Score 0-100 (optional)"
              className="border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper"
            />
          </div>
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            placeholder="Notes"
            rows={2}
            className="w-full border border-line rounded-sm px-3 py-2 text-sm outline-none focus:border-ink bg-paper resize-none"
          />
          <button
            disabled={pending}
            onClick={() => run(() => recordEngagementOutcomeAction(opportunityId, outcome, score, notes, actor))}
            className="px-4 py-2 rounded-sm text-sm font-medium bg-ink text-paper hover:bg-ink/90 disabled:opacity-50 transition-colors"
          >
            {pending ? '…' : 'Record Outcome'}
          </button>
        </div>
      )}
    </section>
  );
}
