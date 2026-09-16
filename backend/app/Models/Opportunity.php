<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'category', 'description', 'quantity', 'country',
        'preferred_origin', 'payment_terms', 'industry', 'status',
        'company_id', 'source_id', 'submitted_by_user_id',
        'submitted_by_name', 'submitted_by_email', 'submitted_by_company', 'submitted_by_phone',
        'commercial_intent_score', 'source_reliability_score',
        'evidence_strength_score', 'recency_score',
        'company_confidence_score', 'match_potential_score', 'overall_score',
        'verified_by_user_id', 'verified_at', 'published_at', 'expires_at',
    ];

    protected $casts = [
        'verified_at'  => 'datetime',
        'published_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function source() {
        return $this->belongsTo(Source::class);
    }

    public function evidence() {
        return $this->hasMany(Evidence::class);
    }

    public function matches() {
        return $this->hasMany(MatchRecord::class);
    }

    public function enquiries() {
        return $this->hasMany(Enquiry::class);
    }

    public function verifiedBy() {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * The ONLY thing public-facing controllers should ever query with.
     * Never expose test/discovered/reviewing/verified-but-unpublished
     * records through the public API — see section 11 of the
     * architecture doc: nothing is public until it's explicitly published.
     */
    public function scopePublished(Builder $query): Builder {
        return $query->where('status', 'published');
    }
}
