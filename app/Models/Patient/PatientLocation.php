<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientLocation extends Model
{
    use HasFactory;
    protected $table = "patient_locations";
    protected $guarded = ["id"];
    protected $primaryKey = 'id';
    public $timestamps = false;
}
