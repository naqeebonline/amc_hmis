<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grn extends Model
{
    use HasFactory;
    protected $table = "grn";
    protected $primaryKey = 'GRNID';
    protected $guarded = ["GRNID"];
    public $timestamps = false;


    public function products(){
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::created(function ($data) {
            // After creating, copy SaleID into id
            $data->id = $data->GRNID;
            $data->saveQuietly(); // avoid recursion
        });
    }

  
    
}
