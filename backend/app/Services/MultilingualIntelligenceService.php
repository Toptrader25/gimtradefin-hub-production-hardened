<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class MultilingualIntelligenceService
{
    private array $scripts = [
        'Hiragana'=>'ja','Katakana'=>'ja','Han'=>'zh','Hangul'=>'ko','Thai'=>'th','Arabic'=>'ar','Cyrillic'=>'ru','Devanagari'=>'hi','Bengali'=>'bn',
    ];

    private array $terms = [
      'en'=>['buying'=>['buyer','buying','purchase','procurement','rfq','seeking supplier','looking for supplier','import requirement'], 'selling'=>['seller','selling','supplier','manufacturer','exporter','offer'], 'partnership'=>['distributor wanted','agent wanted','joint venture','partnership','strategic partner'], 'investment'=>['investor','investment','funding','capital','raising'], 'capital_seeking'=>['seeking funding','seeking investment','raising capital','funding required']],
      'zh'=>['buying'=>['采购','采购需求','求购','买家','采购商','询价'], 'selling'=>['供应商','卖家','销售','制造商','出口商'], 'partnership'=>['寻找经销商','代理商','合作伙伴','合资'], 'investment'=>['投资','投资者','融资','资本'], 'capital_seeking'=>['寻求融资','寻求投资','融资需求']],
      'vi'=>['buying'=>['mua','người mua','nhu cầu mua','tìm nhà cung cấp','yêu cầu mua'], 'selling'=>['bán','người bán','nhà cung cấp','nhà sản xuất','xuất khẩu'], 'partnership'=>['đối tác','nhà phân phối','đại lý','liên doanh'], 'investment'=>['đầu tư','nhà đầu tư','gọi vốn','vốn'], 'capital_seeking'=>['tìm vốn','cần vốn','gọi vốn']],
      'id'=>['buying'=>['membeli','pembeli','permintaan pembelian','mencari pemasok'], 'selling'=>['menjual','penjual','pemasok','produsen','eksportir'], 'partnership'=>['mitra','distributor','agen','kemitraan'], 'investment'=>['investasi','investor','pendanaan','modal'], 'capital_seeking'=>['mencari pendanaan','mencari modal']],
      'ms'=>['buying'=>['membeli','pembeli','permintaan pembelian','mencari pembekal'], 'selling'=>['menjual','penjual','pembekal','pengilang','pengeksport'], 'partnership'=>['rakan kongsi','pengedar','ejen','perkongsian'], 'investment'=>['pelaburan','pelabur','pembiayaan','modal'], 'capital_seeking'=>['mencari pembiayaan','mencari modal']],
      'th'=>['buying'=>['ซื้อ','ผู้ซื้อ','ความต้องการซื้อ','กำลังหาซัพพลายเออร์'], 'selling'=>['ขาย','ผู้ขาย','ซัพพลายเออร์','ผู้ผลิต','ผู้ส่งออก'], 'partnership'=>['พันธมิตร','ตัวแทนจำหน่าย','ตัวแทน','ร่วมทุน'], 'investment'=>['ลงทุน','นักลงทุน','เงินทุน','การระดมทุน'], 'capital_seeking'=>['ต้องการเงินทุน','ระดมทุน']],
      'ja'=>['buying'=>['購入','買い手','調達','仕入れ','サプライヤーを探しています'], 'selling'=>['販売','売り手','サプライヤー','メーカー','輸出業者'], 'partnership'=>['代理店','販売代理店','パートナー','合弁'], 'investment'=>['投資','投資家','資金調達','資本'], 'capital_seeking'=>['資金調達を求める','投資を募集']],
      'ko'=>['buying'=>['구매','구매자','조달','공급업체 찾기'], 'selling'=>['판매','판매자','공급업체','제조업체','수출업체'], 'partnership'=>['파트너','유통업체','대리점','합작'], 'investment'=>['투자','투자자','자금조달','자본'], 'capital_seeking'=>['자금조달을 찾고','투자 유치']],
      'ar'=>['buying'=>['شراء','مشتري','مشتريات','طلب شراء','البحث عن مورد'], 'selling'=>['بيع','بائع','مورد','مصنع','مصدر'], 'partnership'=>['شريك','موزع','وكيل','مشروع مشترك'], 'investment'=>['استثمار','مستثمر','تمويل','رأس المال'], 'capital_seeking'=>['يبحث عن تمويل','يبحث عن استثمار']],
    ];

    public function analyze(string $assetType, string $assetId, string $text, ?string $hint=null): array
    {
        $text = trim($text); if ($text==='') throw new \InvalidArgumentException('Text is required.');
        [$lang,$confidence,$script,$mixed] = $this->detect($text,$hint);
        $assetId = (string)$assetId;
        $id = (string) Str::uuid();
        DB::table('multilingual_text_assets')->insert(['id'=>$id,'asset_type'=>$assetType,'asset_id'=>$assetId,'original_text'=>$text,'source_language'=>$lang,'language_confidence'=>$confidence,'script'=>$script,'mixed_language'=>$mixed,'normalization_version'=>'1.0','metadata'=>json_encode(['detector'=>'script+lexicon']), 'created_at'=>now(),'updated_at'=>now()]);
        $concepts=$this->extractConcepts($id,$text,$lang);
        return ['text_asset_id'=>$id,'language'=>$lang,'confidence'=>$confidence,'script'=>$script,'mixed_language'=>$mixed,'concepts'=>$concepts];
    }

    public function translate(string $assetId, string $target='en'): ?array
    {
        $asset=DB::table('multilingual_text_assets')->where('id',$assetId)->first(); if(!$asset) return null;
        if(($asset->source_language ?? '')===$target) return ['translated_text'=>$asset->original_text,'provider'=>'identity','quality_confidence'=>1.0];
        $provider=config('multilingual.translation.provider','none');
        if($provider==='deepl' && config('multilingual.translation.deepl_api_key')) {
            $params=['text'=>$asset->original_text,'target_lang'=>strtoupper($target==='zh-TW'?'ZH-HANT':($target==='zh'?'ZH':$target)),'preserve_formatting'=>'1'];
            $r=Http::withHeaders(['Authorization'=>'DeepL-Auth-Key '.config('multilingual.translation.deepl_api_key')])->asForm()->post(config('multilingual.translation.deepl_endpoint'),$params);
            if($r->successful()) {
                $translated=$r->json('translations.0.text');
                if($translated) return $this->storeTranslation($assetId,$target,$translated,'deepl',$r->json('translations.0.detected_source_language'),0.90);
            }
        }
        return null;
    }

    private function storeTranslation(string $assetId,string $target,string $text,string $provider,?string $model,float $confidence): array {
        $id=(string)Str::uuid(); DB::table('multilingual_translations')->insert(['id'=>$id,'text_asset_id'=>$assetId,'target_language'=>$target,'translated_text'=>$text,'provider'=>$provider,'model'=>$model,'translation_version'=>'1.0','quality_confidence'=>$confidence,'quality_checks'=>json_encode($this->qualityChecks($assetId,$text)),'created_at'=>now()]);
        return ['translation_id'=>$id,'translated_text'=>$text,'provider'=>$provider,'quality_confidence'=>$confidence];
    }

    private function qualityChecks(string $assetId,string $translation): array {
        $asset=DB::table('multilingual_text_assets')->where('id',$assetId)->first(); $original=$asset?->original_text ?? '';
        preg_match_all('/\d+(?:[,.]\d+)?/u',$original,$a); preg_match_all('/\d+(?:[,.]\d+)?/u',$translation,$b);
        $numbersOk=sort($a[0])===sort($b[0]);
        return ['numeric_consistency'=>$numbersOk,'protected_terms'=>config('multilingual.protected_terms',[])];
    }

    private function detect(string $text, ?string $hint): array {
        if($hint && in_array($hint,config('multilingual.supported_languages',[]),true)) return [$hint,0.99,$this->scriptOf($text),false];
        $scores=[]; foreach($this->scripts as $script=>$lang) { $count=0; preg_match_all('/\p{'.$script.'}+/u',$text,$m); foreach($m[0] as $x) $count+=mb_strlen($x); if($count) $scores[$lang]=($scores[$lang]??0)+$count; }
        foreach($this->terms as $lang=>$groups) foreach($groups as $terms) foreach($terms as $term) if(mb_stripos($text,$term)!==false) $scores[$lang]=($scores[$lang]??0)+3;
        if(!$scores){$lang='en';$conf=0.45;} else {$total=array_sum($scores); arsort($scores); $lang=array_key_first($scores); $conf=min(0.99,max(0.50,$scores[$lang]/max(1,$total)));}
        $mixed=count(array_filter($scores,fn($v)=>$v>=max($scores)*0.35))>1;
        return [$lang,$conf,$this->scriptOf($text),$mixed];
    }
    private function scriptOf(string $text): string { foreach(array_keys($this->scripts) as $s){if(preg_match('/\p{'.$s.'}/u',$text)) return $s;} return 'Latin'; }
    private function extractConcepts(string $assetId,string $text,string $lang): array {
        $out=[]; foreach(($this->terms[$lang]??[]) as $type=>$terms) foreach($terms as $term) if(mb_stripos($text,$term)!==false){$key=$type; $id=(string)Str::uuid(); DB::table('multilingual_concepts')->insert(['id'=>$id,'text_asset_id'=>$assetId,'concept_type'=>$type,'canonical_key'=>$key,'matched_term'=>$term,'language'=>$lang,'confidence'=>0.88,'span'=>json_encode([]),'created_at'=>now(),'updated_at'=>now()]); $out[]=['type'=>$type,'term'=>$term,'confidence'=>0.88];}
        return $out;
    }
}
