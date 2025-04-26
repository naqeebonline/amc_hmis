<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnRequest extends Model
{
    use HasFactory;
    protected $table = "grn_request";
    protected $primaryKey = 'GRNID';
    protected $guarded = ["GRNID"];
    public $timestamps = false;


    public function products(){
        return $this->belongsTo(Product::class);
    }

    public function supplier(){
        return $this->belongsTo(Customer::class,"SCID","SCID");
    }

  
    
}
