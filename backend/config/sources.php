<?php
return [
 'ec21-buying-leads'=>[
   'name'=>'EC21 New Buying Leads','class'=>'public_html','url'=>'https://www.ec21.com/','region'=>'Global','role'=>'commercial_buyer','priority'=>95,
   'enabled'=>(bool)env('SOURCE_EC21_ENABLED',false),'permission_confirmed'=>(bool)env('SOURCE_EC21_PERMISSION_CONFIRMED',false),'requires_auth'=>false,
   'terms_url'=>'https://www.ec21.com/html/ec/help/help_01.html','allowed_hosts'=>['www.ec21.com','ec21.com'],
   'options'=>['signal_type'=>'buyer_request','max_records'=>150]
 ],
 'tradeindia-buy-leads'=>[
   'name'=>'TradeIndia Buy Trade Leads','class'=>'public_html','url'=>'https://www.tradeindia.com/TradeLeads/buy/Business-Services/','region'=>'India','role'=>'commercial_buyer','priority'=>90,
   'enabled'=>(bool)env('SOURCE_TRADEINDIA_ENABLED',false),'permission_confirmed'=>(bool)env('SOURCE_TRADEINDIA_PERMISSION_CONFIRMED',false),'requires_auth'=>false,
   'terms_url'=>'https://www.tradeindia.com/','allowed_hosts'=>['www.tradeindia.com','tradeindia.com'],
   'options'=>['signal_type'=>'buyer_request','max_records'=>150]
 ],
 'matrade-public-trade-matching'=>[
   'name'=>'MATRADE Trade Matching / Public Trade Opportunities','class'=>'public_html','url'=>'https://www.matrade.gov.my/en/export-to-the-world/step-5-access-to-export-market/trade-matching','region'=>'Malaysia / Global','role'=>'trade_matching_intelligence','priority'=>75,
   'enabled'=>(bool)env('SOURCE_MATRADE_ENABLED',false),'permission_confirmed'=>(bool)env('SOURCE_MATRADE_PERMISSION_CONFIRMED',false),'requires_auth'=>false,
   'terms_url'=>'https://www.matrade.gov.my/en/','allowed_hosts'=>['www.matrade.gov.my','matrade.gov.my'],
   'options'=>['signal_type'=>'buyer_request','max_records'=>100]
 ],
 // Real commercial platforms below are registered but deliberately disabled until an approved API/feed/license/permission is configured.
 'go4worldbusiness'=>['name'=>'Go4WorldBusiness Buyer Leads','class'=>'public_html','url'=>'https://www.go4worldbusiness.com/','region'=>'Global','role'=>'commercial_buyer','priority'=>95,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://www.go4worldbusiness.com/','allowed_hosts'=>['www.go4worldbusiness.com','go4worldbusiness.com'],'options'=>['signal_type'=>'buyer_request']],
 'hktdc-sourcing'=>['name'=>'HKTDC Sourcing','class'=>'public_html','url'=>'https://sourcing.hktdc.com/','region'=>'Hong Kong / Global','role'=>'commercial_buyer_seller','priority'=>95,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://sourcing.hktdc.com/','allowed_hosts'=>['sourcing.hktdc.com'],'options'=>['signal_type'=>'commercial_signal']],
 'globalsources'=>['name'=>'Global Sources','class'=>'public_html','url'=>'https://www.globalsources.com/','region'=>'Asia / Global','role'=>'commercial_buyer_seller','priority'=>90,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://www.globalsources.com/','allowed_hosts'=>['www.globalsources.com','globalsources.com'],'options'=>['signal_type'=>'commercial_signal']],
 'made-in-china'=>['name'=>'Made-in-China.com','class'=>'public_html','url'=>'https://www.made-in-china.com/','region'=>'China / Global','role'=>'commercial_buyer_seller','priority'=>90,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://www.made-in-china.com/','allowed_hosts'=>['www.made-in-china.com','made-in-china.com'],'options'=>['signal_type'=>'commercial_signal']],
 'tradekey'=>['name'=>'TradeKey','class'=>'public_html','url'=>'https://www.tradekey.com/','region'=>'Global','role'=>'commercial_buyer_seller','priority'=>85,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://www.tradekey.com/','allowed_hosts'=>['www.tradekey.com'],'options'=>['signal_type'=>'commercial_signal']],
 'europages'=>['name'=>'Europages','class'=>'public_html','url'=>'https://www.europages.co.uk/','region'=>'Europe / Global','role'=>'commercial_supplier_buyer','priority'=>85,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://www.europages.co.uk/','allowed_hosts'=>['www.europages.co.uk','europages.co.uk'],'options'=>['signal_type'=>'commercial_signal']],
 'ecplaza'=>['name'=>'ECPlaza','class'=>'public_html','url'=>'https://www.ecplaza.net/','region'=>'Asia / Global','role'=>'commercial_buyer_seller','priority'=>80,'enabled'=>false,'permission_confirmed'=>false,'requires_auth'=>false,'terms_url'=>'https://www.ecplaza.net/','allowed_hosts'=>['www.ecplaza.net'],'options'=>['signal_type'=>'commercial_signal']],
];
