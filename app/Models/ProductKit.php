<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductKit extends Model
{
    use HasFactory;
    protected $table = "product_kits";
    protected $primaryKey = 'id';
    protected $guarded = ["id"];
    public $timestamps = false;

    public function product(){
        return $this->belongsTo(Product::class, "product_id", "ProductID");
    }


    
    
}
