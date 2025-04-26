<?php

namespace App\Models\Patient;
use App\Models\Configuration\Investigation;
use App\Models\Configuration\InvestigationParameter;
use App\Models\Configuration\InvestigationSubCategory;
use App\Models\Configuration\Ward;
use App\Models\Configuration\WardBed;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientRefund extends Model
{
    use HasFactory;
    protected $table = "patient_refunds";
    protected $guarded = ["id"];
    protected $primaryKey = 'id';
    public $timestamps = false;

 
 
}
