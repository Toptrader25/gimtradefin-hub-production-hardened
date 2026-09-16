<?php
return [
 'version'=>'1.0.0',
 'protected_terms'=>['Incoterms','HS Code','MOQ','RFQ','FOB','CIF','EXW','DAP','DDP','LEI'],
 'provider'=>env('LEADHUNTER_SEMANTIC_PROVIDER','deterministic'),
 'embedding_provider'=>env('LEADHUNTER_EMBEDDING_PROVIDER','none'),
 'taxonomy'=>[
  'product'=>[
   'edible_oils'=>['edible oil','vegetable oil','palm oil','palm olein','rbd palm olein','sunflower oil','soybean oil'],
   'rice'=>['rice','white rice','parboiled rice','jasmine rice','basmati rice'],
   'steel'=>['steel','steel coil','hot rolled coil','cold rolled coil','stainless steel'],
   'textiles'=>['textile','fabric','garment','apparel','clothing'],
   'electronics'=>['electronics','electronic components','semiconductor','consumer electronics'],
   'chemicals'=>['chemical','industrial chemical','specialty chemical'],
  ],
  'industry'=>[
   'food_agriculture'=>['food','agriculture','agri','food processing'],
   'manufacturing'=>['manufacturing','factory','industrial manufacturing'],
   'construction'=>['construction','building materials','infrastructure'],
   'logistics'=>['logistics','freight','shipping','supply chain'],
   'energy'=>['energy','renewable energy','solar','oil and gas'],
  ],
  'capability'=>[
   'manufacturer'=>['manufacturer','manufacturing','factory','producer'],
   'exporter'=>['exporter','export company','export trading'],
   'importer'=>['importer','import company','import trading'],
   'supplier'=>['supplier','vendor','supplier company'],
   'distributor'=>['distributor','distribution','wholesaler'],
   'contract_manufacturer'=>['contract manufacturer','private label','oem','odm'],
  ],
 ],
 'synonyms'=>[
  'rbd palm olein'=>'edible_oils','palm olein'=>'edible_oils','vegetable oil'=>'edible_oils',
  '采购商'=>'buyer','进口商'=>'importer','出口商'=>'exporter','供应商'=>'supplier',
  'người mua'=>'buyer','nhà nhập khẩu'=>'importer','nhà xuất khẩu'=>'exporter','nhà cung cấp'=>'supplier',
  'pembeli'=>'buyer','pengimport'=>'importer','pengeksport'=>'exporter','pembekal'=>'supplier',
  'ผู้ซื้อ'=>'buyer','ผู้นำเข้า'=>'importer','ผู้ส่งออก'=>'exporter','ผู้จัดจำหน่าย'=>'distributor',
 ],
 'units'=>['ton'=>'mt','tonnes'=>'mt','metric ton'=>'mt','mt'=>'mt','kg'=>'kg','kilogram'=>'kg','kgs'=>'kg','litre'=>'l','liter'=>'l','liters'=>'l','l'=>'l','piece'=>'pcs','pieces'=>'pcs','pcs'=>'pcs'],
 'currencies'=>['usd'=>'USD','$'=>'USD','eur'=>'EUR','€'=>'EUR','gbp'=>'GBP','£'=>'GBP','cny'=>'CNY','rmb'=>'CNY','jpy'=>'JPY','inr'=>'INR','vnd'=>'VND','myr'=>'MYR','rm'=>'MYR','thb'=>'THB','idr'=>'IDR'],
];
