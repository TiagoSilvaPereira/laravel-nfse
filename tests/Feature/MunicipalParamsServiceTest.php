<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Nfse\MunicipalParamsService;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class MunicipalParamsServiceTest extends TestCase
{
    public function test_formats_service_code_correctly()
    {
        $service = new MunicipalParamsService();
        
        $this->assertEquals('01.01.01.000', $service->formatServiceCode('010101'));
        
        $this->assertEquals('01.01.00.000', $service->formatServiceCode('0101'));
        
        $this->assertEquals('01.01.01.010', $service->formatServiceCode('01010101'));
    }

    public function test_gets_service_aliquota_with_cache()
    {
        Cache::flush();
        
        $company = Company::factory()->create([
            'municipality_code' => '3550308', // São Paulo
        ]);
        
        $service = new MunicipalParamsService();
        $service->setCompany($company);
        
        $aliquota = $service->getServiceAliquota('3550308', '010101');
        
        $cacheKey = 'nfse.aliquota.3550308.01.01.01.000.' . now()->format('Y-m-d');
        $this->assertTrue(Cache::has($cacheKey));
        
        $aliquotaCached = $service->getServiceAliquota('3550308', '010101');
        $this->assertEquals($aliquota, $aliquotaCached);
    }

    public function test_checks_city_aderence()
    {
        $company = Company::factory()->create([
            'municipality_code' => '3550308',
        ]);
        
        $service = new MunicipalParamsService();
        $service->setCompany($company);
        
        $isAderent = $service->isCityAderent('3550308');
        
        $this->assertIsBool($isAderent);
    }

    public function test_checks_article_6_retention()
    {
        $company = Company::factory()->create([
            'municipality_code' => '3550308',
        ]);
        
        $service = new MunicipalParamsService();
        $service->setCompany($company);
        
        $hasRetention = $service->hasArticle6Retention('3550308');

        $this->assertIsBool($hasRetention);
    }
}
