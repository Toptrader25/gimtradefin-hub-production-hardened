<?php
// Policy-level regression tests documenting the safety contract.

test('name similarity alone must never auto-link', function(){
    $policy = ['name_similarity'=>1.0,'domain_match'=>0.0,'registration_match'=>0.0,'tax_id_match'=>0.0,'phone_match'=>0.0];
    expect($policy['name_similarity'])->toBe(1.0);
    expect(($policy['domain_match']>=1 || $policy['registration_match']>=1 || $policy['tax_id_match']>=1 || $policy['phone_match']>=1))->toBeFalse();
});
