<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnDetails extends Model
{
    use HasFactory;
    protected $table = "grn_details";
    protected $primaryKey = 'GDID';
    protected $guarded = ["GDID"];
    public $timestamps = false;


    public function products(){
        return $this->belongsTo(Product::class,"ProductID");
    }

    public function grn(){
        return $this->belongsTo(Grn::class, "GRNID", "GRNID");
    }

  
    
}
