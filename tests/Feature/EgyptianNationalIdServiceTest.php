<?php

use App\Services\EgyptianNationalIdService;

it('parses valid 1900s and 2000s IDs with gender and governorate', function () {
    $service = app(EgyptianNationalIdService::class);

    expect($service->parse('20001010100011'))->toMatchArray([
        'birth_date' => '1900-01-01', 'gender' => 'Male', 'governorate' => 'القاهرة',
    ]);
    expect($service->parse('30001020202002'))->toMatchArray([
        'birth_date' => '2000-01-02', 'gender' => 'Female', 'governorate' => 'الإسكندرية',
    ]);
});

it('normalizes Arabic digits and rejects malformed or unknown IDs', function () {
    $service = app(EgyptianNationalIdService::class);

    expect($service->parse('٢٠٠٠١٠١٠١٠٠٠٠١'))->not->toBeNull();
    foreach (['1234567890123', '123456789012345', '2000101010000A', '40001010100001', '29991320100001', '20001019900001'] as $id) {
        expect($service->parse($id))->toBeNull();
    }
});

it('supports outside-republic governorate code 88', function () {
    expect(app(EgyptianNationalIdService::class)->parse('30001018802003')['governorate'])->toBe('خارج الجمهورية');
});
