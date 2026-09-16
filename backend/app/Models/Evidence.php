<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    use HasFactory;

    protected $table = 'evidence';

    protected $fillable = [
        'opportunity_id', 'source_id', 'original_url', 'source_record_id',
        'discovered_at', 'last_checked_at', 'excerpt', 'evidence_type',
        'evidence_timestamp', 'source_confidence',
    ];

    protected $casts = [
        'discovered_at'      => 'datetime',
        'last_checked_at'    => 'datetime',
        'evidence_timestamp' => 'datetime',
    ];

    public function opportunity() {
        return $this->belongsTo(Opportunity::class);
    }

    public function source() {
        return $this->belongsTo(Source::class);
    }
}
