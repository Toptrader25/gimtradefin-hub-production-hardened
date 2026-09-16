<?php
namespace Tests\Unit;
use Tests\TestCase;
use App\Services\LearningGovernanceScaleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
class LearningGovernanceScaleTest extends TestCase {use RefreshDatabase; public function test_feature_hash_is_stable():void{$s=app(LearningGovernanceScaleService::class);$id=$s->snapshotFeatures(['feature_set'=>'x','features'=>['b'=>2,'a'=>1]]);$row=DB::table('lh_feature_snapshots')->where('id',$id)->first();$this->assertSame(hash('sha256',$row->features),$row->feature_hash);} public function test_quality_check_returns_score():void{$r=app(LearningGovernanceScaleService::class)->qualityCheck();$this->assertArrayHasKey('quality_score',$r);}}
