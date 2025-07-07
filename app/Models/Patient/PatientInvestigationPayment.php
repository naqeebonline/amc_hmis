<?php

namespace App\Models\Patient;

use App\Models\Configuration\District;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientInvestigationPayment extends Model
{
    use HasFactory;
    protected $table = "patient_investigations_payments";
    protected $guarded = ["id"];
    protected $primaryKey = 'id';
    public $timestamps = false;



    
}
