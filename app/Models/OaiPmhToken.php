<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OaiPmhToken extends Model
{
    protected $primaryKey = 'token';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
