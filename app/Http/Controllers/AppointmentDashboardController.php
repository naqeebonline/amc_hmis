<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $from_date = $request->input('from_date', Carbon::now()->toDateString());
        $to_date = $request->input('to_date', Carbon::now()->toDateString());
        $consultant_id = $request->input('consultant_id');
        $opd_type_id = $request->input('opd_type_id');

        $query = DB::table('appointments')
            ->whereDate('appointment_date', '>=', $from_date)
            ->whereDate('appointment_date', '<=', $to_date)
            ->where('is_active', 1);
        if ($consultant_id) {
            $query->where('consultant_id', $consultant_id);
        }
        if ($opd_type_id) {
            $query->where('opd_type_id', $opd_type_id);
        }

    $total_appointments = $query->count();
    $total_hospital_share = (clone $query)->sum('hospital_share');
    $total_consultant_share = (clone $query)->sum('consultant_share');
    $total_amount = $total_hospital_share + $total_consultant_share;

        $opd_type_counts_query = DB::table('appointments')
            ->select('opd_type_id', DB::raw('count(*) as total'))
            ->whereDate('appointment_date', '>=', $from_date)
            ->whereDate('appointment_date', '<=', $to_date)
            ->where('is_active', 1);
        if ($consultant_id) {
            $opd_type_counts_query->where('consultant_id', $consultant_id);
        }
        if ($opd_type_id) {
            $opd_type_counts_query->where('opd_type_id', $opd_type_id);
        }
        $opd_type_counts = $opd_type_id
            ? $opd_type_counts_query->groupBy('opd_type_id')->get()->filter(fn($row) => $row->opd_type_id == $opd_type_id)
            : $opd_type_counts_query->groupBy('opd_type_id')->get();

        $consultants = DB::table('consultants')->select('id', 'name')->get();
        $opd_types = DB::table('opd_type')->select('id', 'name')->get();

        return view('appointment_dashboard', [
            'total_appointments' => $total_appointments,
            'total_hospital_share' => $total_hospital_share,
            'total_consultant_share' => $total_consultant_share,
            'total_amount' => $total_amount,
            'opd_type_counts' => $opd_type_counts,
            'consultants' => $consultants,
            'opd_types' => $opd_types,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'selected_date' => $from_date,
            'selected_consultant' => $consultant_id,
            'selected_opd_type' => $opd_type_id,
        ]);
    }
}
