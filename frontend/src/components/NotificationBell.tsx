'use client';

import { useState, useTransition } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { AppNotification } from '@/types/notification';
import { markNotificationReadAction, markAllNotificationsReadAction } from '@/lib/admin-actions';

const TYPE_LABEL: Record<string, string> = {
  opportunity_status_changed: 'Submission update',
  new_enquiry: 'New introduction request',
  new_submission: 'New submission to review',
};

function notificationLink(n: AppNotification): string | null {
  const p = n.payload;
  if (!p) return null;
  if (n.type === 'new_submission' && p.opportunity_id) return `/admin/opportunities/${p.opportunity_id}`;
  if (p.opportunity_id) return `/opportunities/${p.opportunity_id}`;
  return null;
}

export function NotificationBell({
  initialNotifications,
  initialUnreadCount,
}: {
  initialNotifications: AppNotification[];
  initialUnreadCount: number;
}) {
  const [open, setOpen] = useState(false);
  const [notifications, setNotifications] = useState(initialNotifications);
  const [unreadCount, setUnreadCount] = useState(initialUnreadCount);
  const [, startTransition] = useTransition();
  const router = useRouter();

  const handleOpen = () => setOpen((prev) => !prev);

  const handleReadOne = (id: number) => {
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n)));
    setUnreadCount((prev) => Math.max(0, prev - 1));
    startTransition(async () => {
      await markNotificationReadAction(id);
      router.refresh();
    });
  };

  const handleReadAll = () => {
    setNotifications((prev) => prev.map((n) => ({ ...n, read_at: n.read_at || new Date().toISOString() })));
    setUnreadCount(0);
    startTransition(async () => {
      await markAllNotificationsReadAction();
      router.refresh();
    });
  };

  return (
    <div className="relative">
      <button
        onClick={handleOpen}
        className="relative text-sm text-manifest hover:text-ink transition-colors"
        aria-label="Notifications"
      >
        <span className="font-mono">🔔</span>
        {unreadCount > 0 && (
          <span className="absolute -top-1.5 -right-1.5 bg-[#A63D3D] text-white text-[9px] font-mono rounded-full w-4 h-4 flex items-center justify-center">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
          <div className="absolute right-0 mt-2 w-80 border border-line bg-paper rounded-md shadow-lg z-20 max-h-96 overflow-y-auto">
            <div className="flex items-center justify-between px-4 py-3 border-b border-line">
              <span className="font-mono text-[10px] tracking-widest text-manifest">NOTIFICATIONS</span>
              {unreadCount > 0 && (
                <button onClick={handleReadAll} className="text-xs text-seal underline">
                  Mark all read
                </button>
              )}
            </div>
            {notifications.length === 0 ? (
              <p className="text-sm text-manifest text-center py-8">Nothing yet.</p>
            ) : (
              <ul>
                {notifications.map((n) => {
                  const link = notificationLink(n);
                  const content = (
                    <div className="px-4 py-3 border-b border-line last:border-b-0 hover:bg-chart transition-colors">
                      <div className="flex items-start justify-between gap-2">
                        <span className="text-sm text-ink">{TYPE_LABEL[n.type] || n.type}</span>
                        {!n.read_at && <span className="w-1.5 h-1.5 rounded-full bg-seal mt-1.5 shrink-0" />}
                      </div>
                      <p className="font-mono text-[10px] text-manifest mt-1">
                        {new Date(n.created_at).toLocaleString()}
                      </p>
                    </div>
                  );
                  return (
                    <li key={n.id} onClick={() => !n.read_at && handleReadOne(n.id)}>
                      {link ? (
                        <Link href={link} onClick={() => setOpen(false)}>
                          {content}
                        </Link>
                      ) : (
                        content
                      )}
                    </li>
                  );
                })}
              </ul>
            )}
          </div>
        </>
      )}
    </div>
  );
}
