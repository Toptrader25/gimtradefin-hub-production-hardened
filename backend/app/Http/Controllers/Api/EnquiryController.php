<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    /**
     * POST /api/v1/opportunities/{id}/enquiries
     *
     * Public endpoint. Deliberately only allows enquiries against
     * PUBLISHED opportunities — same principle as OpportunityController:
     * you can't submit an introduction request against something that
     * was never approved for public display, even if you guess its ID.
     */
    public function store(Request $request, int $opportunityId)
    {
        $opportunity = Opportunity::published()->findOrFail($opportunityId);

        $validated = $request->validate([
            'enquirer_name'    => ['required', 'string', 'max:255'],
            'enquirer_email'   => ['required', 'email', 'max:255'],
            'enquirer_company' => ['nullable', 'string', 'max:255'],
            'type'             => ['required', Rule::in(['introduction_request', 'financing_assessment', 'general'])],
            'message'          => ['nullable', 'string', 'max:2000'],
        ]);

        $enquiry = Enquiry::create([
            'opportunity_id'    => $opportunity->id,
            // Same guard note as OpportunitySubmissionController: this
            // route is public, so the sanctum guard must be specified
            // explicitly or $request->user() always returns null here.
            'user_id'           => $request->user('sanctum')?->id,
            'enquirer_name'     => $validated['enquirer_name'],
            'enquirer_email'    => $validated['enquirer_email'],
            'enquirer_company'  => $validated['enquirer_company'] ?? null,
            'type'              => $validated['type'],
            'message'           => $validated['message'] ?? null,
            'status'            => 'new',
        ]);

        // Notify the person who listed the opportunity — closes what was
        // previously a TODO comment. Sent regardless of whether they
        // have an account, since submission has always been possible
        // without one.
        if ($opportunity->submitted_by_email) {
            $notifications = app(NotificationService::class);
            $subject = 'Someone wants an introduction on GiMtradefin';
            $body = "Someone has requested an introduction regarding your listing \"{$opportunity->title}\".\n\n"
                  . "From: {$validated['enquirer_name']} ({$validated['enquirer_email']})\n"
                  . (($validated['enquirer_company'] ?? null) ? "Company: {$validated['enquirer_company']}\n" : '')
                  . (($validated['message'] ?? null) ? "\nMessage:\n{$validated['message']}\n" : '')
                  . "\nGiMtradefin will facilitate this introduction — your contact details were not shared automatically.";

            if ($opportunity->submitted_by_user_id) {
                $user = User::find($opportunity->submitted_by_user_id);
                if ($user) {
                    $notifications->notifyUser($user, 'new_enquiry', [
                        'enquiry_id'     => $enquiry->id,
                        'opportunity_id' => $opportunity->id,
                    ], $subject, $body);
                }
            } else {
                $notifications->notifyEmailOnly($opportunity->submitted_by_email, $subject, $body);
            }
        }

        return response()->json([
            'status' => 'received',
            'id'     => $enquiry->id,
        ], 201);
    }
}
