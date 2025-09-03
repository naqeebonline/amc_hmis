<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetails extends Model
{
    use HasFactory;
    use Syncable;
    protected $table = "sale_details";
    protected $primaryKey = 'SDID';
    protected $guarded = ["SDID"];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class, 'ProductID');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'SaleID');
    }

    public function return_by()
    {
        return $this->belongsTo(User::class, 'return_by',"id");
    }
    public function return_by_user()
    {
        return $this->belongsTo(User::class, 'return_by',"id");
    }

  
    
}
