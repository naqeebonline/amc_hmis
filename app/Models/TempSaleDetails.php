<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempSaleDetails extends Model
{
    use HasFactory;
    protected $table = "temp_sale_details";
    protected $primaryKey = 'SDID';
    protected $guarded = ["SDID"];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID');
    }



  
    
}
