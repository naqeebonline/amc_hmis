<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantProcedurePricing extends Model
{
    use HasFactory;
    protected $table = "consultant_procedure_pricing";
    protected $guarded = ["id"];
    public $timestamps = false;
}
