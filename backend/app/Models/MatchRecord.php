<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchRecord extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'opportunity_id', 'matched_company_id', 'matched_opportunity_id',
        'product_compatibility', 'market_compatibility', 'industry_compatibility',
        'geographic_fit', 'commercial_intent', 'overall_match', 'explanation', 'status',
    ];

    public function opportunity() {
        return $this->belongsTo(Opportunity::class);
    }

    public function matchedCompany() {
        return $this->belongsTo(Company::class, 'matched_company_id');
    }
}
