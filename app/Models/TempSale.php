<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempSale extends Model
{
    use HasFactory;
    protected $table = "temp_sale";
    protected $primaryKey = 'SaleID';
    protected $guarded = ["SaleID"];
    public $timestamps = false;



  
    
}
