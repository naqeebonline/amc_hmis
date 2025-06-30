<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceTransaction extends Model
{
    use HasFactory;
    protected $table = "finance_transactions";
    protected $primaryKey = 'id';
    protected $guarded = ["id"];
    public $timestamps = false;
}
