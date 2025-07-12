<?php

namespace App\Http\Controllers\Finance\Reports;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceHead;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function balanceReport()
    {
        $debits = DB::table('finance_transactions')
            ->join('finance_vouchers', 'finance_transactions.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->select('debit_head_id as head_id', DB::raw('SUM(amount) as total_debit'))
            ->groupBy('debit_head_id');

        $credits = DB::table('finance_transactions')
            ->join('finance_vouchers', 'finance_transactions.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->select('credit_head_id as head_id', DB::raw('SUM(amount) as total_credit'))
            ->groupBy('credit_head_id');

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
            ->where('fh.type', 'income')
           // ->where("fh.id",5)
            ->sum('ft.amount');

        // Get total expenses from debit side (expenses are debited)
        $totalExpense = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'ft.debit_head_id', '=', 'fh.id')
            ->where('fh.type', 'expense')
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



    public function printDailyClosingVoucher($voucher_id)
    {
        $voucher = FinanceVoucher::with([
            'transactions' => function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('reference_type')
                        ->orWhere('reference_type', '!=', 'commission');
                })->with(['debitHead', 'creditHead']);
            },
            'createdBy',
            'approvedBy',
            'transactions.debitHead',
            'transactions.creditHead'
        ])->findOrFail($voucher_id);

        $rows = collect();

        foreach ($voucher->transactions as $transaction) {
            // Debit entries
            if ($transaction->debit_head_id && $transaction->debitHead) {
                $rows->push([
                    'type' => 'debit',
                    'head_code' => $transaction->debitHead->head_code,
                    'head_title' => $transaction->debitHead->name,
                    'debit' => $transaction->amount,
                    'credit' => 0,
                    'balance' => $transaction->amount
                ]);
            }

            // Credit entries
            if ($transaction->credit_head_id && $transaction->creditHead) {
                $rows->push([
                    'type' => 'credit',
                    'head_code' => $transaction->creditHead->head_code,
                    'head_title' => $transaction->creditHead->name,
                    'debit' => 0,
                    'credit' => $transaction->amount,
                    'balance' =>  $transaction->amount
                ]);
            }
        }

        // Sort so that debit entries come first
        $sortedRows = $rows->sortBy(function ($item) {
            return $item['type'] === 'debit' ? 0 : 1;
        })->values();

        $totalDebit = $sortedRows->sum('debit');
        $totalCredit = $sortedRows->sum('credit');
        return view('Finance.Reports.print_daily_closing_voucher', compact('voucher', 'sortedRows', 'totalDebit', 'totalCredit'));
       // return view('Finance.Reports.print_daily_closing_voucher', compact('voucher', 'rows', 'totalDebit', 'totalCredit'));
    }


    function get_user_base_daily_closing_report(){
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d");
        $report = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'fh.id', '=', 'ft.credit_head_id')
            ->join('users as user', 'ft.user_id', '=', 'user.id')
            ->select('ft.user_id', 'fh.name as head_name', 'ft.reference_type', DB::raw('COUNT(ft.reference_type) as total_count'),DB::raw('SUM(ft.amount) as total_amount'),'user.name as user_name')
            ->whereBetween('ft.transaction_date', [$start_date, $end_date])
            ->where('ft.is_active', 1)
            ->where('ft.reference_type', '!=', 'commission')
            ->groupBy('ft.user_id', 'ft.reference_type', 'fh.name')
            ->orderBy('ft.user_id')
            ->get()
            ->groupBy('user_id');

        foreach ($report as $key => $value){

            $advance = user_advance($key,$start_date,$end_date);
            $value->user_advance = $advance;
        }





        return view('Finance.Reports.user_base_closing_report',compact("start_date","end_date","report"));
    }

}
