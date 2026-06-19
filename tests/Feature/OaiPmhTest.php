<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OaiPmhTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicProgram $program;

    protected ResearchLine $line;

    protected ProductionType $type;

    protected AcademicPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program = AcademicProgram::create([
            'name' => 'Ingeniería de Sistemas',
            'code' => 'ING-SIS',
            'is_active' => true,
        ]);

        $this->line = ResearchLine::create([
            'academic_program_id' => $this->program->id,
            'name' => 'Inteligencia Artificial',
            'is_active' => true,
        ]);

        $this->type = ProductionType::create([
            'name' => 'Tesis de Grado',
            'description' => 'Trabajo especial de grado',
        ]);

        $this->period = AcademicPeriod::create([
            'name' => '2026-I',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        config(['oai-pmh.repository_name' => 'Repositorio UNIMAR']);
        config(['oai-pmh.base_url' => 'http://localhost/oai']);
        config(['oai-pmh.admin_email' => 'admin@unimar.edu.ve']);
        config(['oai-pmh.page_size' => 2]);
    }

    public function test_identify_returns_repository_info(): void
    {
        $response = $this->get('/oai?verb=Identify');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals('Repositorio UNIMAR', (string) $xml->Identify->repositoryName);
        $this->assertEquals('2.0', (string) $xml->Identify->protocolVersion);
    }

    public function test_list_metadata_formats(): void
    {
        $response = $this->get('/oai?verb=ListMetadataFormats');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals('oai_dc', (string) $xml->ListMetadataFormats->metadataFormat->metadataPrefix);
    }

    public function test_list_sets_returns_dynamic_sets(): void
    {
        $response = $this->get('/oai?verb=ListSets');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $xml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
        $setNodes = $xml->xpath('//oai:ListSets/oai:set/oai:setSpec');
        $setSpecs = array_map(fn ($node) => (string) $node, $setNodes);

        $this->assertContains('program:'.$this->program->id, $setSpecs);
        $this->assertContains('line:'.$this->line->id, $setSpecs);
        $this->assertContains('type:'.$this->type->id, $setSpecs);
    }

    public function test_list_records_returns_only_published_productions(): void
    {
        $published = $this->createProduction('published');
        $this->createProduction('approved');

        $response = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertCount(1, $xml->ListRecords->record);
        $this->assertStringContainsString($published->uuid, (string) $xml->ListRecords->record[0]->header->identifier);
    }

    public function test_list_records_filters_by_set(): void
    {
        $this->createProduction('published', ['research_line_id' => $this->line->id]);
        $this->createProduction('published', ['research_line_id' => null]);

        $response = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc&set=line:'.$this->line->id);

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertCount(1, $xml->ListRecords->record);
    }

    public function test_list_records_paginates_with_resumption_token(): void
    {
        $this->createProduction('published');
        $this->createProduction('published');
        $this->createProduction('published');

        $response = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertCount(2, $xml->ListRecords->record);
        $this->assertNotNull($xml->ListRecords->resumptionToken);

        $token = (string) $xml->ListRecords->resumptionToken;
        $secondPage = $this->get('/oai?verb=ListRecords&resumptionToken='.$token);
        $secondXml = simplexml_load_string($secondPage->getContent());
        $this->assertCount(1, $secondXml->ListRecords->record);
    }

    public function test_list_records_requires_metadata_prefix(): void
    {
        $response = $this->get('/oai?verb=ListRecords');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals('badArgument', (string) $xml->error['code']);
    }

    public function test_list_records_rejects_unsupported_metadata_prefix(): void
    {
        $response = $this->get('/oai?verb=ListRecords&metadataPrefix=marc21');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals('cannotDisseminateFormat', (string) $xml->error['code']);
    }

    public function test_get_record_returns_published_production(): void
    {
        $production = $this->createProduction('published');
        $identifier = 'oai:unimar:'.$production->uuid;

        $response = $this->get('/oai?verb=GetRecord&identifier='.$identifier.'&metadataPrefix=oai_dc');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals($identifier, (string) $xml->GetRecord->record->header->identifier);
        $metadata = $xml->GetRecord->record->metadata->children('oai_dc', true)->dc->children('dc', true);
        $this->assertEquals($production->title, (string) $metadata->title);
    }

    public function test_get_record_returns_error_for_unpublished(): void
    {
        $production = $this->createProduction('draft');
        $identifier = 'oai:unimar:'.$production->uuid;

        $response = $this->get('/oai?verb=GetRecord&identifier='.$identifier.'&metadataPrefix=oai_dc');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals('idDoesNotExist', (string) $xml->error['code']);
    }

    public function test_bad_verb_returns_error(): void
    {
        $response = $this->get('/oai?verb=BadVerb');

        $response->assertStatus(200);
        $xml = simplexml_load_string($response->getContent());
        $this->assertEquals('badVerb', (string) $xml->error['code']);
    }

    protected function createProduction(string $state, array $overrides = []): Production
    {
        return Production::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba '.Str::random(5),
            'abstract' => 'Resumen de prueba',
            'authors' => 'Autor de Prueba',
            'tutor' => 'Tutor de Prueba',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => $state,
            'submission_date' => now(),
            'approval_date' => in_array($state, ['approved', 'published']) ? now() : null,
            'published_at' => $state === 'published' ? now() : null,
        ], $overrides));
    }
}
