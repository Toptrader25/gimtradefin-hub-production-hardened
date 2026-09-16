<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id', 'user_id', 'enquirer_name', 'enquirer_email',
        'enquirer_company', 'type', 'message', 'status',
    ];

    public function opportunity() {
        return $this->belongsTo(Opportunity::class);
    }
}
