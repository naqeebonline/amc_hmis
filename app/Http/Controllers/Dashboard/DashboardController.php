<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function retailPharmacyDashboard()
    {
        $from_date = $_GET['from_date'] ?? date("Y-m-d");
        $to_date = $_GET['to_date'] ?? date("Y-m-d");
        $query = Sale::where('store_id', session('store_id'))
            ->when($from_date, function ($query) use ($from_date) {
                return $query->where('Date', '>=', date("Y-m-d", strtotime($from_date)));
            })
            ->when($to_date, function ($query) use ($to_date) {
                return $query->where('Date', '<=', date("Y-m-d", strtotime($to_date)));
            });

        $totals = $query->selectRaw('SUM(TotalSale) as TotalSale, SUM(Discount) as Discount, SUM(received_amount) as received_amount')
            ->first();
        $data['data'] = $totals;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;
        return view("retail_dashboard",$data);

    }
}
