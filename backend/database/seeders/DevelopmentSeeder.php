<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Dev/staging only. Every record here is explicitly status="test"
     * per section 11 of the architecture doc — the production system
     * must NEVER represent test data as genuine commercial intelligence.
     * Do not run this seeder against production.
     */
    public function run(): void
    {
        $company = Company::create([
            'name' => 'Example Trading Co (TEST)',
            'country' => 'Malaysia',
            'industry' => 'Food & Agriculture',
            'role' => 'Importer',
            'verification_status' => 'unknown',
        ]);

        Opportunity::create([
            'title' => '[TEST] Bulk Cocoa Buyer, 1000 MT',
            'category' => 'buying',
            'description' => 'Synthetic development record. Not a real opportunity.',
            'quantity' => '1,000 MT',
            'country' => 'Malaysia',
            'preferred_origin' => 'West Africa, Ghana',
            'payment_terms' => '60 days',
            'status' => 'test',
            'company_id' => $company->id,
        ]);
    }
}
