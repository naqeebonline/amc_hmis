<?php

namespace App\Models;

use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $table = "sale";
    protected $primaryKey = 'SaleID';
    protected $guarded = ["SaleID"];
    public $timestamps = false;

    public function patient(){
        return $this->belongsTo(Patient::class,"patient_id","id");
    }



  
    
}
