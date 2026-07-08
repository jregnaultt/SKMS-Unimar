<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionType extends Model
{
    use HasAuditLog, HasFactory;

    protected $guarded = ['id'];
}
