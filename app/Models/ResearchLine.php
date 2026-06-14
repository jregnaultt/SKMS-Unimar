<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }
}
