<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Staging / demo seed only. Every company and opportunity title is
 * explicitly marked [DEMO] so nobody mistakes this for live commercial
 * intelligence. Idempotent: skips if demo companies already exist.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Company::where('name', 'like', '%[DEMO]%')->exists()) {
            $this->command?->info('DemoSeeder: demo data already present — skipping.');
            return;
        }

        User::firstOrCreate(
            ['email' => 'demo.reviewer@gimtradefin.test'],
            [
                'name'     => 'Demo Reviewer [DEMO]',
                'password' => Hash::make('DemoHub2026!'),
                'role'     => 'reviewer',
            ]
        );

        $companies = [
            [
                'name' => 'Aurelia Commodities Sdn Bhd [DEMO]',
                'country' => 'Malaysia',
                'industry' => 'Food & Agriculture',
                'role' => 'Importer / Distributor',
                'description' => 'Demo importer focused on cocoa, palm derivatives, and specialty fats for ASEAN food manufacturers. Not a real company.',
                'markets' => ['Malaysia', 'Singapore', 'Indonesia'],
                'products' => ['Cocoa liquor', 'Palm olein', 'Specialty fats'],
                'website' => 'https://example.com/demo-aurelia',
                'verification_status' => 'verified',
                'verified_at' => now()->subDays(12),
            ],
            [
                'name' => 'Mekong Grain Partners [DEMO]',
                'country' => 'Vietnam',
                'industry' => 'Agriculture',
                'role' => 'Exporter',
                'description' => 'Demo rice and cassava exporter serving Middle East and Africa buyers. Synthetic record for hub UI demos.',
                'markets' => ['UAE', 'Saudi Arabia', 'Nigeria'],
                'products' => ['Jasmine rice', 'Cassava chips'],
                'website' => 'https://example.com/demo-mekong',
                'verification_status' => 'verified',
                'verified_at' => now()->subDays(8),
            ],
            [
                'name' => 'Harborline Packaging Co. [DEMO]',
                'country' => 'Singapore',
                'industry' => 'Packaging & Logistics',
                'role' => 'Supplier',
                'description' => 'Demo flexible packaging supplier for F&B exporters. Demo data only.',
                'markets' => ['Singapore', 'Malaysia', 'Thailand'],
                'products' => ['Retort pouches', 'Barrier films'],
                'website' => 'https://example.com/demo-harborline',
                'verification_status' => 'pending',
            ],
            [
                'name' => 'Sahel Agro Trading Ltd [DEMO]',
                'country' => 'Ghana',
                'industry' => 'Food & Agriculture',
                'role' => 'Exporter',
                'description' => 'Demo West African cocoa and shea exporter. Not real commercial intelligence.',
                'markets' => ['EU', 'Malaysia', 'China'],
                'products' => ['Cocoa beans', 'Shea butter'],
                'website' => 'https://example.com/demo-sahel',
                'verification_status' => 'verified',
                'verified_at' => now()->subDays(20),
            ],
            [
                'name' => 'Pacific Cold Chain JV [DEMO]',
                'country' => 'Philippines',
                'industry' => 'Logistics',
                'role' => 'Partnership seeker',
                'description' => 'Demo cold-chain operator seeking regional distribution partners. Synthetic.',
                'markets' => ['Philippines', 'Japan', 'Korea'],
                'products' => ['Frozen seafood logistics'],
                'website' => null,
                'verification_status' => 'unknown',
            ],
        ];

        $created = [];
        foreach ($companies as $row) {
            $created[] = Company::create($row);
        }

        $opps = [
            [
                'title' => '[DEMO] Seeking 500 MT cocoa liquor — ASEAN delivery',
                'category' => 'buying',
                'description' => 'Demo buying lead. Looking for West African origin cocoa liquor, food-grade, with COA and phytosanitary docs. Payment LC at sight preferred. This is synthetic demo data for the GiMtradefin Hub MVP.',
                'quantity' => '500 MT',
                'country' => 'Malaysia',
                'preferred_origin' => 'Ghana / Ivory Coast',
                'payment_terms' => 'LC at sight',
                'industry' => 'Food & Agriculture',
                'company_id' => $created[0]->id,
                'overall_score' => 82,
                'commercial_intent_score' => 88,
                'source_reliability_score' => 75,
                'evidence_strength_score' => 80,
            ],
            [
                'title' => '[DEMO] Jasmine rice 5% broken — 2,000 MT available',
                'category' => 'selling',
                'description' => 'Demo selling lead. Export-ready Vietnamese jasmine rice, 5% broken, packed in 50kg PP bags. FOB Ho Chi Minh. Synthetic demo record — not a live offer.',
                'quantity' => '2,000 MT',
                'country' => 'Vietnam',
                'preferred_origin' => 'Mekong Delta',
                'payment_terms' => '30% TT / 70% against docs',
                'industry' => 'Agriculture',
                'company_id' => $created[1]->id,
                'overall_score' => 79,
                'commercial_intent_score' => 85,
                'source_reliability_score' => 78,
                'evidence_strength_score' => 72,
            ],
            [
                'title' => '[DEMO] Retort pouch supplier for ready-meal exporters',
                'category' => 'selling',
                'description' => 'Demo supply of high-barrier retort pouches (aluminium and transparent). MOQ 50,000 pcs. Demo data only.',
                'quantity' => '50,000 pcs MOQ',
                'country' => 'Singapore',
                'preferred_origin' => null,
                'payment_terms' => 'Net 30',
                'industry' => 'Packaging & Logistics',
                'company_id' => $created[2]->id,
                'overall_score' => 71,
                'commercial_intent_score' => 70,
                'source_reliability_score' => 68,
                'evidence_strength_score' => 74,
            ],
            [
                'title' => '[DEMO] Cocoa beans 1,000 MT — main crop',
                'category' => 'selling',
                'description' => 'Demo Ghana main-crop cocoa beans, Grade 1, moisture <7.5%. Ready for Q4 shipment. Synthetic demo opportunity.',
                'quantity' => '1,000 MT',
                'country' => 'Ghana',
                'preferred_origin' => 'Ghana',
                'payment_terms' => 'CAD / LC 60 days',
                'industry' => 'Food & Agriculture',
                'company_id' => $created[3]->id,
                'overall_score' => 86,
                'commercial_intent_score' => 90,
                'source_reliability_score' => 84,
                'evidence_strength_score' => 83,
            ],
            [
                'title' => '[DEMO] Seeking ASEAN cold-chain distribution partner',
                'category' => 'partnership',
                'description' => 'Demo partnership request. Cold-chain operator seeking JV or exclusive distribution partners for frozen seafood lanes into Japan and Korea. Not a real lead.',
                'quantity' => null,
                'country' => 'Philippines',
                'preferred_origin' => null,
                'payment_terms' => 'To be discussed',
                'industry' => 'Logistics',
                'company_id' => $created[4]->id,
                'overall_score' => 68,
                'commercial_intent_score' => 72,
                'source_reliability_score' => 60,
                'evidence_strength_score' => 65,
            ],
            [
                'title' => '[DEMO] Palm olein buyer — 800 MT monthly',
                'category' => 'buying',
                'description' => 'Demo recurring buying interest for RBD palm olein, CP10. Monthly offtake ~800 MT into Port Klang. Synthetic demo data.',
                'quantity' => '800 MT / month',
                'country' => 'Malaysia',
                'preferred_origin' => 'Malaysia / Indonesia',
                'payment_terms' => 'TT against BL',
                'industry' => 'Food & Agriculture',
                'company_id' => $created[0]->id,
                'overall_score' => 77,
                'commercial_intent_score' => 80,
                'source_reliability_score' => 76,
                'evidence_strength_score' => 70,
            ],
            [
                'title' => '[DEMO] Shea butter bulk — cosmetic grade 200 MT',
                'category' => 'selling',
                'description' => 'Demo cosmetic-grade unrefined shea butter, 200 MT available. EU-bound packaging options. Demo only.',
                'quantity' => '200 MT',
                'country' => 'Ghana',
                'preferred_origin' => 'Ghana',
                'payment_terms' => 'LC at sight',
                'industry' => 'Food & Agriculture',
                'company_id' => $created[3]->id,
                'overall_score' => 74,
                'commercial_intent_score' => 76,
                'source_reliability_score' => 80,
                'evidence_strength_score' => 69,
            ],
            [
                'title' => '[DEMO] Cassava chips for animal feed — 3,000 MT',
                'category' => 'selling',
                'description' => 'Demo feed-grade cassava chips, starch 70%+, moisture 14%. FOB Cat Lai. Synthetic demo record.',
                'quantity' => '3,000 MT',
                'country' => 'Vietnam',
                'preferred_origin' => 'Vietnam',
                'payment_terms' => 'CAD',
                'industry' => 'Agriculture',
                'company_id' => $created[1]->id,
                'overall_score' => 73,
                'commercial_intent_score' => 75,
                'source_reliability_score' => 72,
                'evidence_strength_score' => 71,
            ],
        ];

        foreach ($opps as $row) {
            Opportunity::create(array_merge($row, [
                'status' => 'published',
                'published_at' => now()->subDays(random_int(1, 21)),
                'verified_at' => now()->subDays(random_int(2, 25)),
            ]));
        }

        $this->command?->info('DemoSeeder: created '.count($created).' companies and '.count($opps).' published opportunities.');
    }
}
