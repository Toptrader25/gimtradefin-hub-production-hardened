<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime'];

    public function companies() {
        return $this->belongsToMany(Company::class, 'company_users')
                     ->withPivot('role_in_company')
                     ->withTimestamps();
    }

    /** Opportunities this user submitted (any status, not just published). */
    public function opportunities() {
        return $this->hasMany(Opportunity::class, 'submitted_by_user_id');
    }

    /** Introduction/financing enquiries this user has sent. */
    public function enquiries() {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Deliberately overrides Illuminate\Notifications\Notifiable's own
     * notifications() relationship (which expects a different schema —
     * morphMany with notifiable_type/notifiable_id). Safe: the
     * Notifiable trait is only used here for notify()'s mail channel
     * (see sendPasswordResetNotification below), which never touches
     * this relationship. This app's own Notification model/table is
     * what NotificationService and NotificationController actually use.
     */
    public function notifications() {
        return $this->hasMany(Notification::class);
    }

    /**
     * Overrides Laravel's default (which points at a backend web route
     * that doesn't exist in this API-only app) with a link to the
     * Next.js frontend's reset-password page instead.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
