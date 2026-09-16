<?php
use App\Services\EntityNormalizer;

test('normalizes business names conservatively', function(){
    $n=new EntityNormalizer();
    expect($n->name('ABC Trading Co., Ltd.'))->toBe('abc trading');
    expect($n->domain('https://www.Example.com/path'))->toBe('example.com');
    expect($n->emailDomain('sales@Example.com'))->toBe('example.com');
    expect($n->phone('+60 (12) 345-6789'))->toBe('60123456789');
});
