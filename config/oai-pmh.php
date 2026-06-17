<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OAI-PMH Repository Configuration
    |--------------------------------------------------------------------------
    |
    | These values are exposed by the /oai endpoint and must match the
    | institutional repository settings used by harvesters such as Google
    | Scholar, Latindex, Redalyc and SciELO.
    |
    */

    'repository_name' => env('OAI_REPOSITORY_NAME', 'Repositorio Institucional UNIMAR'),

    'base_url' => env('OAI_BASE_URL', config('app.url').'/oai'),

    'admin_email' => env('OAI_ADMIN_EMAIL', 'repositorio@unimar.edu.ve'),

    // ISO 8601 date of the earliest published record available.
    'earliest_datestamp' => env('OAI_EARLIEST_DATESTAMP', '2024-01-01T00:00:00Z'),

    'deleted_record' => env('OAI_DELETED_RECORD', 'no'),

    'granularity' => env('OAI_GRANULARITY', 'YYYY-MM-DDThh:mm:ssZ'),

    // Number of records returned per ListRecords response.
    'page_size' => (int) env('OAI_PAGE_SIZE', 50),
];
