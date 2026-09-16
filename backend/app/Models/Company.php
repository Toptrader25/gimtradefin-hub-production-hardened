<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'country', 'industry', 'role', 'description',
        'markets', 'products', 'website', 'verification_status',
        'verified_at', 'verified_by',
    ];

    protected $casts = [
        'markets'     => 'array',
        'products'    => 'array',
        'verified_at' => 'datetime',
    ];

    public function opportunities() {
        return $this->hasMany(Opportunity::class);
    }

    public function users() {
        return $this->belongsToMany(User::class, 'company_users')
                     ->withPivot('role_in_company')
                     ->withTimestamps();
    }
}
