<?php

namespace App\Http\Controllers\ProductConfiguration;

use App\Http\Controllers\Controller;
use App\Models\Configuration\InvestigationSubCategory;
use App\Models\Configuration\ServiceType;
use App\Models\Patient\PatientAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReturnProductController extends Controller
{
    public function return_product()
    {
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")->with("patient", "ward", "bed")
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->get();
        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();


        return view("sale.return_product", $data);
    }
}
