<?php
return [
 'default_target' => env('LEADHUNTER_CANONICAL_LANGUAGE','en'),
 'minimum_detection_confidence' => (float) env('LEADHUNTER_LANGUAGE_MIN_CONFIDENCE','0.72'),
 'supported_languages' => [
  'en','zh','zh-TW','vi','id','ms','th','ja','ko','hi','bn','ar','ur','tr','fa','ru','es','pt','fr','de','it'
 ],
 'translation' => [
  'provider' => env('LEADHUNTER_TRANSLATION_PROVIDER','none'),
  'deepl_api_key' => env('DEEPL_API_KEY'),
  'deepl_endpoint' => env('DEEPL_ENDPOINT','https://api-free.deepl.com/v2/translate'),
 ],
 'protected_terms' => ['Incoterms','HS Code','MOQ','RFQ','FOB','CIF','EXW','DAP','DDP','LEI'],
];
