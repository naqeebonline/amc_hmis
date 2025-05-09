<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    use HasFactory;
    protected $table = "payment_type";
    protected $primaryKey = 'payment_type_id';
    protected $guarded = ["payment_type_id"];
    public $timestamps = false;
}
