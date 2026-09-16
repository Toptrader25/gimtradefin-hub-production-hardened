<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Closes a gap that existed since Phase 2: the `notifications` table
 * existed with zero implementation. A submission could be approved and
 * the person would never know unless they happened to check their
 * dashboard. This is the one place both the in-app record and the
 * email actually get created — every trigger point in the app calls
 * through here rather than duplicating the logic.
 *
 * Uses Mail::raw() rather than Mailable classes with Blade views —
 * deliberate choice: I can't render/verify Blade templates in this
 * sandbox, and a plain-text email that's guaranteed correct beats a
 * nicer-looking one I can't actually test.
 */
class NotificationService
{
    /**
     * Notify a registered user (in-app + email, if they have an email
     * on file — they always do, but keeping the check explicit).
     */
    public function notifyUser(User $user, string $type, array $payload, string $emailSubject, string $emailBody): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'payload' => $payload,
        ]);

        $this->sendEmail($user->email, $emailSubject, $emailBody);
    }

    /**
     * Notify someone by email only — used when the recipient (e.g. an
     * opportunity's submitter) isn't necessarily a registered User,
     * since submission has always been possible without an account.
     */
    public function notifyEmailOnly(string $email, string $subject, string $body): void
    {
        $this->sendEmail($email, $subject, $body);
    }

    /**
     * Notify every admin/reviewer — used for "a new submission needs
     * review" alerts. In-app + email for each.
     */
    public function notifyReviewers(string $type, array $payload, string $emailSubject, string $emailBody): void
    {
        $reviewers = User::whereIn('role', ['admin', 'reviewer'])->get();

        foreach ($reviewers as $reviewer) {
            $this->notifyUser($reviewer, $type, $payload, $emailSubject, $emailBody);
        }
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            // Never let a failed email break the actual operation (a
            // status change, an enquiry) that triggered it. Same
            // principle as wp_mail() failures not blocking form
            // submission in the earlier WordPress build.
            report($e);
        }
    }
}
