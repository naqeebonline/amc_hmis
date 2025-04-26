<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    use HasFactory;
    protected $table = "payments_details";
    protected $primaryKey = 'PDID ';
    protected $guarded = ["PDID "];
    public $timestamps = false;


    public function paymentType(){
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }
    
}
