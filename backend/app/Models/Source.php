<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'country', 'region', 'source_type', 'connector_type',
        'access_method', 'reliability_score', 'commercial_relevance_score',
        'tier', 'url', 'access_notes', 'last_scan_at', 'scan_status', 'is_active',
    ];

    protected $casts = [
        'last_scan_at' => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function opportunities() {
        return $this->hasMany(Opportunity::class);
    }

    public function scanRuns() {
        return $this->hasMany(ScanRun::class);
    }
}
