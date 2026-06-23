<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\OaiPmhToken;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Implements an OAI-PMH 2.0 data provider for the institutional repository.
 *
 * Exposes only productions whose workflow_state is 'published' in oai_dc.
 */
class OaiPmhService
{
    /**
     * Supported OAI-PMH verbs.
     *
     * @var array<int, string>
     */
    protected array $validVerbs = ['Identify', 'ListSets', 'ListMetadataFormats', 'ListRecords', 'GetRecord'];

    /**
     * Handle an OAI-PMH request and return the XML response body.
     *
     * @param  array<string, string|null>  $params
     */
    public function handle(array $params): string
    {
        $verb = $params['verb'] ?? null;

        if ($verb === null || ! in_array($verb, $this->validVerbs, true)) {
            return $this->errorResponse('badVerb', 'The verb argument is missing or is not legal.');
        }

        return match ($verb) {
            'Identify' => $this->identify($params),
            'ListSets' => $this->listSets($params),
            'ListMetadataFormats' => $this->listMetadataFormats($params),
            'ListRecords' => $this->listRecords($params),
            'GetRecord' => $this->getRecord($params),
        };
    }

    /**
     * Identify verb: repository description.
     *
     * @param  array<string, string|null>  $params
     */
    protected function identify(array $params): string
    {
        if ($this->hasIllegalArguments($params, ['verb'])) {
            return $this->errorResponse('badArgument', 'Illegal arguments for Identify verb.');
        }

        $content = sprintf(
            '<Identify>'
            .'<repositoryName>%s</repositoryName>'
            .'<baseURL>%s</baseURL>'
            .'<protocolVersion>2.0</protocolVersion>'
            .'<adminEmail>%s</adminEmail>'
            .'<earliestDatestamp>%s</earliestDatestamp>'
            .'<deletedRecord>%s</deletedRecord>'
            .'<granularity>%s</granularity>'
            .'</Identify>',
            $this->e(config('oai-pmh.repository_name')),
            $this->e(config('oai-pmh.base_url')),
            $this->e(config('oai-pmh.admin_email')),
            $this->e(config('oai-pmh.earliest_datestamp')),
            $this->e(config('oai-pmh.deleted_record')),
            $this->e(config('oai-pmh.granularity'))
        );

        return $this->buildResponse($content, ['verb' => 'Identify']);
    }

    /**
     * ListSets verb: dynamic sets based on catalogs and publication years.
     *
     * @param  array<string, string|null>  $params
     */
    protected function listSets(array $params): string
    {
        if ($this->hasIllegalArguments($params, ['verb', 'resumptionToken'])) {
            return $this->errorResponse('badArgument', 'Illegal arguments for ListSets verb.');
        }

        $sets = [];

        AcademicProgram::where('is_active', true)->orderBy('name')->each(function (AcademicProgram $program) use (&$sets): void {
            $sets[] = ['setSpec' => 'program:'.$program->id, 'setName' => $program->name];
        });

        ResearchLine::where('is_active', true)->orderBy('name')->each(function (ResearchLine $line) use (&$sets): void {
            $sets[] = ['setSpec' => 'line:'.$line->id, 'setName' => $line->name];
        });

        ProductionType::orderBy('name')->each(function (ProductionType $type) use (&$sets): void {
            $sets[] = ['setSpec' => 'type:'.$type->id, 'setName' => $type->name];
        });

        $driver = DB::connection()->getDriverName();
        $yearExpression = $driver === 'sqlite'
            ? "DISTINCT CAST(strftime('%Y', published_at) AS INTEGER) as year"
            : 'DISTINCT YEAR(published_at) as year';

        $years = Production::published()
            ->selectRaw($yearExpression)
            ->pluck('year')
            ->sortDesc();

        foreach ($years as $year) {
            $sets[] = ['setSpec' => 'year:'.$year, 'setName' => 'Año '.$year];
        }

        if ($sets === []) {
            return $this->errorResponse('noSetHierarchy', 'This repository does not support sets.');
        }

        $content = '<ListSets>';
        foreach ($sets as $set) {
            $content .= sprintf(
                '<set><setSpec>%s</setSpec><setName>%s</setName></set>',
                $this->e($set['setSpec']),
                $this->e($set['setName'])
            );
        }
        $content .= '</ListSets>';

        return $this->buildResponse($content, ['verb' => 'ListSets']);
    }

    /**
     * ListMetadataFormats verb: only oai_dc is supported.
     *
     * @param  array<string, string|null>  $params
     */
    protected function listMetadataFormats(array $params): string
    {
        $allowed = ['verb', 'identifier'];
        if ($this->hasIllegalArguments($params, $allowed)) {
            return $this->errorResponse('badArgument', 'Illegal arguments for ListMetadataFormats verb.');
        }

        if (isset($params['identifier'])) {
            $uuid = $this->parseIdentifier($params['identifier']);
            if ($uuid === null || ! Production::published()->where('uuid', $uuid)->exists()) {
                return $this->errorResponse('idDoesNotExist', 'The identifier does not exist.');
            }
        }

        $content = '<ListMetadataFormats>'
            .'<metadataFormat>'
            .'<metadataPrefix>oai_dc</metadataPrefix>'
            .'<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>'
            .'<metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>'
            .'</metadataFormat>'
            .'</ListMetadataFormats>';

        $requestParams = ['verb' => 'ListMetadataFormats'];
        if (isset($params['identifier'])) {
            $requestParams['identifier'] = $params['identifier'];
        }

        return $this->buildResponse($content, $requestParams);
    }

    /**
     * ListRecords verb: paginated list of published productions.
     *
     * @param  array<string, string|null>  $params
     */
    protected function listRecords(array $params): string
    {
        $allowed = ['verb', 'from', 'until', 'set', 'metadataPrefix', 'resumptionToken'];
        if ($this->hasIllegalArguments($params, $allowed)) {
            return $this->errorResponse('badArgument', 'Illegal arguments for ListRecords verb.');
        }

        if (isset($params['resumptionToken'])) {
            return $this->listRecordsWithToken($params);
        }

        $metadataPrefix = $params['metadataPrefix'] ?? null;
        if ($metadataPrefix === null) {
            return $this->errorResponse('badArgument', 'Missing metadataPrefix argument.');
        }
        if ($metadataPrefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', 'The metadata prefix is not supported.');
        }

        $filters = $this->buildFilters($params);
        if (isset($filters['error'])) {
            return $this->errorResponse($filters['error'], $filters['message']);
        }

        $query = $this->buildProductionQuery($filters);
        $pageSize = (int) config('oai-pmh.page_size', 50);

        $total = $query->count();
        $records = $query->orderBy('published_at')->orderBy('id')->limit($pageSize + 1)->get();

        if ($records->isEmpty()) {
            return $this->errorResponse('noRecordsMatch', 'No records match the given criteria.');
        }

        $hasMore = $records->count() > $pageSize;
        if ($hasMore) {
            $records->pop();
        }

        $content = '<ListRecords>';
        foreach ($records as $production) {
            $content .= $this->recordXml($production);
        }

        if ($hasMore) {
            $token = $this->createResumptionToken($filters, $pageSize);
            $content .= sprintf(
                '<resumptionToken expirationDate="%s" completeListSize="%d" cursor="%d">%s</resumptionToken>',
                $token->expiration_date->toIso8601ZuluString(),
                $total,
                $pageSize,
                $this->e($token->token)
            );
        }

        $content .= '</ListRecords>';

        $requestParams = ['verb' => 'ListRecords', 'metadataPrefix' => 'oai_dc'];
        foreach (['from', 'until', 'set'] as $key) {
            if (isset($params[$key])) {
                $requestParams[$key] = $params[$key];
            }
        }

        return $this->buildResponse($content, $requestParams);
    }

    /**
     * Continue a previously paginated ListRecords request.
     *
     * @param  array<string, string|null>  $params
     */
    protected function listRecordsWithToken(array $params): string
    {
        if (count(array_diff(array_keys($params), ['verb', 'resumptionToken'])) > 0) {
            return $this->errorResponse('badArgument', 'resumptionToken cannot be combined with other arguments.');
        }

        $tokenString = $params['resumptionToken'];
        $token = OaiPmhToken::where('token', $tokenString)
            ->where('expiration_date', '>', now())
            ->first();

        if ($token === null) {
            return $this->errorResponse('badResumptionToken', 'The resumption token is invalid or expired.');
        }

        $filters = $token->metadata ?? [];
        $cursor = (int) $token->cursor;
        $pageSize = (int) config('oai-pmh.page_size', 50);

        $query = $this->buildProductionQuery($filters);
        $total = (int) ($filters['total'] ?? $query->count());

        $records = $query->orderBy('published_at')->orderBy('id')->skip($cursor)->limit($pageSize + 1)->get();

        if ($records->isEmpty()) {
            return $this->errorResponse('noRecordsMatch', 'No records match the given criteria.');
        }

        $hasMore = $records->count() > $pageSize;
        if ($hasMore) {
            $records->pop();
        }

        $content = '<ListRecords>';
        foreach ($records as $production) {
            $content .= $this->recordXml($production);
        }

        if ($hasMore) {
            $token->cursor = $cursor + $pageSize;
            $token->save();
            $content .= sprintf(
                '<resumptionToken expirationDate="%s" completeListSize="%d" cursor="%d">%s</resumptionToken>',
                $token->expiration_date->toIso8601ZuluString(),
                $total,
                $cursor + $pageSize,
                $this->e($token->token)
            );
        } else {
            $token->delete();
        }

        $content .= '</ListRecords>';

        return $this->buildResponse($content, ['verb' => 'ListRecords', 'resumptionToken' => $tokenString]);
    }

    /**
     * GetRecord verb: return a single published production.
     *
     * @param  array<string, string|null>  $params
     */
    protected function getRecord(array $params): string
    {
        $allowed = ['verb', 'identifier', 'metadataPrefix'];
        if ($this->hasIllegalArguments($params, $allowed)) {
            return $this->errorResponse('badArgument', 'Illegal arguments for GetRecord verb.');
        }

        $metadataPrefix = $params['metadataPrefix'] ?? null;
        if ($metadataPrefix === null) {
            return $this->errorResponse('badArgument', 'Missing metadataPrefix argument.');
        }
        if ($metadataPrefix !== 'oai_dc') {
            return $this->errorResponse('cannotDisseminateFormat', 'The metadata prefix is not supported.');
        }

        $identifier = $params['identifier'] ?? null;
        if ($identifier === null) {
            return $this->errorResponse('badArgument', 'Missing identifier argument.');
        }

        $uuid = $this->parseIdentifier($identifier);
        if ($uuid === null) {
            return $this->errorResponse('idDoesNotExist', 'The identifier does not exist.');
        }

        $production = Production::published()->where('uuid', $uuid)->first();
        if ($production === null) {
            return $this->errorResponse('idDoesNotExist', 'The identifier does not exist.');
        }

        $content = '<GetRecord>'.$this->recordXml($production).'</GetRecord>';

        return $this->buildResponse($content, ['verb' => 'GetRecord', 'identifier' => $identifier, 'metadataPrefix' => 'oai_dc']);
    }

    /**
     * Build the OAI-PMH XML envelope.
     *
     * @param  array<string, string>  $requestParams
     */
    protected function buildResponse(string $content, array $requestParams): string
    {
        $requestXml = sprintf(
            '<request%s>%s</request>',
            $this->requestAttributes($requestParams),
            $this->e(config('oai-pmh.base_url'))
        );

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            .'<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            .'xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">'
            .'<responseDate>'.Carbon::now('UTC')->toIso8601ZuluString().'</responseDate>'
            .$requestXml
            .$content
            .'</OAI-PMH>';
    }

    /**
     * Build a standard OAI-PMH error response.
     */
    protected function errorResponse(string $code, string $message): string
    {
        $content = sprintf('<error code="%s">%s</error>', $this->e($code), $this->e($message));

        return $this->buildResponse($content, []);
    }

    /**
     * Generate the XML for a single record.
     */
    protected function recordXml(Production $production): string
    {
        $identifier = $this->oaiIdentifier($production->uuid);
        $datestamp = $production->published_at?->toIso8601ZuluString() ?? $production->updated_at->toIso8601ZuluString();

        $sets = [];
        if ($production->academic_program_id) {
            $sets[] = 'program:'.$production->academic_program_id;
        }
        if ($production->research_line_id) {
            $sets[] = 'line:'.$production->research_line_id;
        }
        if ($production->production_type_id) {
            $sets[] = 'type:'.$production->production_type_id;
        }
        if ($production->published_at) {
            $sets[] = 'year:'.$production->published_at->year;
        }

        $setSpecs = '';
        foreach ($sets as $set) {
            $setSpecs .= '<setSpec>'.$this->e($set).'</setSpec>';
        }

        $metadata = $this->metadataXml($production);

        return sprintf(
            '<record><header>%s<datestamp>%s</datestamp>%s</header><metadata>%s</metadata></record>',
            '<identifier>'.$this->e($identifier).'</identifier>',
            $this->e($datestamp),
            $setSpecs,
            $metadata
        );
    }

    /**
     * Build the oai_dc metadata block for a production.
     */
    protected function metadataXml(Production $production): string
    {
        $dc = [
            'title' => [$production->title],
            'creator' => $this->splitValues($production->authors),
            'subject' => $this->splitValues($production->keywords),
            'description' => [$production->abstract],
            'publisher' => [$production->academicProgram?->name],
            'contributor' => $this->splitValues($production->tutor),
            'date' => [$production->published_at?->toDateString()],
            'type' => [$production->productionType?->name],
            'format' => ['application/pdf'],
            'identifier' => array_filter([$this->productionUrl($production), $production->doi]),
            'source' => [$production->academicProgram?->name],
            'language' => ['es'],
            'relation' => [$production->researchLine?->name],
            'coverage' => [],
            'rights' => ['Acceso abierto'],
        ];

        $xml = '<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            .'xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">';

        foreach ($dc as $element => $values) {
            foreach ($values as $value) {
                if ($value !== null && $value !== '') {
                    $xml .= sprintf('<dc:%s>%s</dc:%s>', $element, $this->e($value), $element);
                }
            }
        }

        $xml .= '</oai_dc:dc>';

        return $xml;
    }

    /**
     * Parse and validate OAI-PMH request filters.
     *
     * @param  array<string, string|null>  $params
     * @return array<string, mixed>
     */
    protected function buildFilters(array $params): array
    {
        $filters = [];

        if (isset($params['from'])) {
            $from = $this->parseOaiDate($params['from']);
            if ($from === null) {
                return ['error' => 'badArgument', 'message' => 'Invalid from date format.'];
            }
            $filters['from'] = $from;
        }

        if (isset($params['until'])) {
            $until = $this->parseOaiDate($params['until']);
            if ($until === null) {
                return ['error' => 'badArgument', 'message' => 'Invalid until date format.'];
            }
            $filters['until'] = $until;
        }

        if (isset($params['set'])) {
            $set = $this->parseSetSpec($params['set']);
            if ($set === null) {
                return ['error' => 'badArgument', 'message' => 'Invalid set spec.'];
            }
            $filters['set'] = $set;
        }

        return $filters;
    }

    /**
     * Build the base Eloquent query for published productions.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function buildProductionQuery(array $filters): Builder
    {
        $query = Production::published()->with(['academicProgram', 'researchLine', 'productionType', 'keywords']);

        if (isset($filters['from'])) {
            $query->whereDate('published_at', '>=', $filters['from']);
        }
        if (isset($filters['until'])) {
            $query->whereDate('published_at', '<=', $filters['until']);
        }

        if (isset($filters['set'])) {
            $set = $filters['set'];
            match ($set['type']) {
                'program' => $query->where('academic_program_id', $set['id']),
                'line' => $query->where('research_line_id', $set['id']),
                'type' => $query->where('production_type_id', $set['id']),
                'year' => $query->whereYear('published_at', $set['id']),
            };
        }

        return $query;
    }

    /**
     * Create a resumption token and persist it.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function createResumptionToken(array $filters, int $pageSize): OaiPmhToken
    {
        $filters['total'] = $this->buildProductionQuery($filters)->count();
        $filters['page_size'] = $pageSize;

        return OaiPmhToken::create([
            'token' => Str::random(64),
            'expiration_date' => now()->addDay(),
            'cursor' => $pageSize,
            'metadata' => $filters,
        ]);
    }

    /**
     * Parse a setSpec into type and id.
     *
     * @return array{type: string, id: int}|null
     */
    protected function parseSetSpec(string $spec): ?array
    {
        if (! preg_match('/^(program|line|type|year):(\d+)$/u', $spec, $matches)) {
            return null;
        }

        return ['type' => $matches[1], 'id' => (int) $matches[2]];
    }

    /**
     * Parse an OAI-PMH identifier and return the internal UUID.
     */
    protected function parseIdentifier(string $identifier): ?string
    {
        $prefix = 'oai:unimar:';
        if (! str_starts_with($identifier, $prefix)) {
            return null;
        }

        $uuid = substr($identifier, strlen($prefix));

        return preg_match('/^[a-f0-9\-]{36}$/i', $uuid) ? $uuid : null;
    }

    /**
     * Build the OAI-PMH identifier for a production UUID.
     */
    protected function oaiIdentifier(string $uuid): string
    {
        return 'oai:unimar:'.$uuid;
    }

    /**
     * Parse an OAI-PMH date (full or short granularity).
     */
    protected function parseOaiDate(string $date): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $date)) {
            return Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $date, 'UTC') ?: null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::createFromFormat('Y-m-d', $date, 'UTC') ?: null;
        }

        return null;
    }

    /**
     * Check for unexpected request arguments.
     *
     * @param  array<string, string|null>  $params
     * @param  array<int, string>  $allowed
     */
    protected function hasIllegalArguments(array $params, array $allowed): bool
    {
        foreach (array_keys($params) as $key) {
            if ($key !== '' && ! in_array($key, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build request element attributes from parameters.
     *
     * @param  array<string, string>  $params
     */
    protected function requestAttributes(array $params): string
    {
        $attrs = '';
        foreach ($params as $key => $value) {
            $attrs .= sprintf(' %s="%s"', $key, $this->e($value));
        }

        return $attrs;
    }

    /**
     * Split a comma-separated metadata value.
     */
    protected function splitValues(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $value)));
    }

    /**
     * Generate the public URL for a production.
     */
    protected function productionUrl(Production $production): string
    {
        return URL::route('productions.show', $production, false);
    }

    /**
     * Escape a string for XML.
     */
    protected function e(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
