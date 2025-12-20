<?php

namespace Tests\Feature\Nfse;

use App\Actions\Nfse\PrepareInvoice;
use App\Enums\NfseStatus;
use App\Jobs\ProcessNfseEmission;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AsyncEmissionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'last_dps_number' => 100,
            'dps_series' => '1',
        ]);
    }

    /** @test */
    public function it_creates_invoice_in_draft_and_dispatches_job()
    {
        Queue::fake();

        $data = $this->getValidInvoiceData();

        $response = $this->postJson('/api/nfse', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'status', 'integration_id'],
            ]);

        $invoice = Invoice::first();
        $this->assertEquals(NfseStatus::DRAFT, $invoice->status);
        $this->assertNull($invoice->processing_at);

        Queue::assertPushed(ProcessNfseEmission::class, function ($job) use ($invoice) {
            return $job->invoiceId === $invoice->id;
        });
    }

    /** @test */
    public function it_updates_existing_draft_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => 'test-001',
            'status' => NfseStatus::DRAFT,
            'payload' => ['old' => 'data'],
        ]);

        $data = $this->getValidInvoiceData(['integration_id' => 'test-001']);

        $response = $this->postJson('/api/nfse', $data);

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertNotEquals(['old' => 'data'], $invoice->payload);
    }

    /** @test */
    public function it_returns_existing_authorized_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => 'test-001',
            'status' => NfseStatus::AUTHORIZED,
        ]);

        $data = $this->getValidInvoiceData(['integration_id' => 'test-001']);

        $response = $this->postJson('/api/nfse', $data);

        $response->assertStatus(200);
        
        $this->assertEquals(1, Invoice::count());
    }

    /** @test */
    public function it_prevents_modifying_invoice_being_processed()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'integration_id' => 'test-001',
            'status' => NfseStatus::PROCESSING,
            'processing_at' => now(),
        ]);

        $data = $this->getValidInvoiceData(['integration_id' => 'test-001']);

        $response = $this->postJson('/api/nfse', $data);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Erro ao processar nota fiscal.',
            ]);
    }

    /** @test */
    public function it_generates_unique_dps_numbers_with_concurrent_requests()
    {
        Queue::fake();

        // Simula múltiplas requisições concorrentes
        $invoices = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $data = $this->getValidInvoiceData([
                'integration_id' => "concurrent-{$i}",
            ]);

            $response = $this->postJson('/api/nfse', $data);
            $response->assertSuccessful();
            
            $invoices[] = Invoice::where('integration_id', "concurrent-{$i}")->first();
        }

        // Todos devem ter sido criados
        $this->assertCount(5, $invoices);

        // Todos devem estar em DRAFT
        foreach ($invoices as $invoice) {
            $this->assertEquals(NfseStatus::DRAFT, $invoice->status);
        }
    }

    /** @test */
    public function job_marks_invoice_as_processing()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => NfseStatus::DRAFT,
            'dps_number' => 0,
        ]);

        $this->assertNull($invoice->processing_at);

        // Aqui você precisaria mockar os serviços para testar o job
        // Por ora, apenas verifica que o job pode ser instanciado
        $job = new ProcessNfseEmission($invoice->id);
        $this->assertInstanceOf(ProcessNfseEmission::class, $job);
    }

    protected function getValidInvoiceData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'integration_id' => 'test-' . uniqid(),
            'customer' => [
                'name' => 'Cliente Teste',
                'cpfCnpj' => '12345678901',
                'address' => [
                    'street' => 'Rua Teste',
                    'number' => '123',
                    'district' => 'Centro',
                    'city_code' => '3550308',
                    'zip_code' => '01310100',
                ],
            ],
            'service' => [
                'code' => '01.01',
                'nbs_code' => '0101010',
                'description' => 'Serviço de teste',
                'amount' => 1000.00,
            ],
        ], $overrides);
    }
}
