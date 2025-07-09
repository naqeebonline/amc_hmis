<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceHead;
use App\Models\Finance\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function balanceReport()
    {
        $debits = DB::table('finance_transactions')
            ->select('debit_head_id as head_id', DB::raw('SUM(amount) as total_debit'))
            ->where(["is_approved"=>1,"is_active"=>1])
            ->groupBy('debit_head_id');

        // Subquery: Credit totals
        $credits = DB::table('finance_transactions')
            ->select('credit_head_id as head_id', DB::raw('SUM(amount) as total_credit'))
            ->where(["is_approved"=>1,"is_active"=>1])
            ->groupBy('credit_head_id');

        // Merge into finance_heads
        $report = FinanceHead::leftJoinSub($debits, 'debits', 'finance_heads.id', '=', 'debits.head_id')
            ->leftJoinSub($credits, 'credits', 'finance_heads.id', '=', 'credits.head_id')
            ->select(
                'finance_heads.id',
                'finance_heads.name',
                'finance_heads.type',
                DB::raw('COALESCE(total_debit, 0) as total_debit'),
                DB::raw('COALESCE(total_credit, 0) as total_credit')
            )
            ->get()
            ->map(function ($head) {
                if (in_array($head->type, ['asset', 'expense'])) {
                    $head->balance = $head->total_debit - $head->total_credit;
                } else {
                    $head->balance = $head->total_credit - $head->total_debit;
                }
                return $head;
            });

        return view('Finance.Reports.finance_balance_report', compact('report'));
    }

    public function profitAndLossReport(Request $request)
    {
        //$investigation_income = FinanceTransaction::where()



        // Get total income from credit side (income is credited)
        $totalIncome = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'ft.credit_head_id', '=', 'fh.id')
            ->where(["ft.is_approved"=>1,"ft.is_active"=>1])
            ->where('fh.type', 'income')
           // ->where("fh.id",5)
            ->sum('ft.amount');

        // Get total expenses from debit side (expenses are debited)
        $totalExpense = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'ft.debit_head_id', '=', 'fh.id')
            ->where('fh.type', 'expense')
            ->where(["ft.is_approved"=>1,"ft.is_active"=>1])
           // ->where("fh.id",11)
            ->sum('ft.amount');

        // Calculate net profit/loss
        $netProfit = $totalIncome - $totalExpense;

        return view('Finance.Reports.profit_and_lost_report', compact('totalIncome', 'totalExpense', 'netProfit'));


    }


 

    public function postDailyIncome(Request $request)
    {
        $from_date = $request->from_date ?? date("Y-m-d");
        $to_date = $request->to_date ?? date("Y-m-d");
        $userId = request()->user_id ?? auth()->user()->id; // or from $request if posting for another user
        $date = date("Y-m-d");
        // 1. Get income head IDs
        $cashHeadId = DB::table('finance_heads')->where('description', 'cash_at_office')->value('id');
        $appointmentHeadId = DB::table('finance_heads')->where('description', 'appointments')->value('id');
        $investigationHeadId = DB::table('finance_heads')->where('description', 'patient_investigations')->value('id');
        $pharmacyHeadId = DB::table('finance_heads')->where('description', 'sale')->value('id');

        // 2. Calculate daily totals
        $appointmentTotal = DB::table('appointments')
            ->when($from_date, function ($query) use ($from_date) {
                $query->whereDate('appointment_date',">=", $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->whereDate('appointment_date',"<=", $to_date);
            })
            ->where('user_id', $userId)
            ->sum('fee');

        $investigationTotal = DB::table('patient_investigations')
            ->when($from_date, function ($query) use ($from_date) {
                $query->whereDate('inv_date',">=", $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->whereDate('inv_date',"<=", $to_date);
            })
            ->where('user_id', $userId)
            ->sum('sale_price');

        $pharmacyTotal = DB::table('sale')
            ->when($from_date, function ($query) use ($from_date) {
                $query->whereDate('Date',">=", $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->whereDate('Date',"<=", $to_date);
            })
            ->where('user_id', $userId)
            ->sum('total_price');

        // 3. Insert finance transactions
        $now = Carbon::now();

        $entries = [];

        if ($appointmentTotal > 0) {
            $entries[] = [
                'transaction_date' => $date,
                'amount' => $appointmentTotal,
                'debit_head_id' => $cashHeadId,
                'credit_head_id' => $appointmentHeadId,
                'reference_type' => 'appointment',
                'user_id' => $userId,
                'remarks' => "Posted appointment income",
                'created_at' => $now
            ];
        }

        if ($investigationTotal > 0) {
            $entries[] = [
                'transaction_date' => $date,
                'amount' => $investigationTotal,
                'debit_head_id' => $cashHeadId,
                'credit_head_id' => $investigationHeadId,
                'reference_type' => 'investigation',
                'user_id' => $userId,
                'remarks' => "Posted investigation income",
                'created_at' => $now
            ];
        }

        if ($pharmacyTotal > 0) {
            $entries[] = [
                'transaction_date' => $date,
                'amount' => $pharmacyTotal,
                'debit_head_id' => $cashHeadId,
                'credit_head_id' => $pharmacyHeadId,
                'reference_type' => 'pharmacy_sale',
                'user_id' => $userId,
                'remarks' => "Posted pharmacy income",
                'created_at' => $now
            ];
        }

      //  DB::table('finance_transactions')->insert($entries);

        return back()->with('success', 'Daily income posted to finance transactions.');
    }

}
