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


    function get_user_base_daily_closing_report2(){
        $start_date = '2025-07-10'; // or request('start_date')
        $end_date = '2025-07-13';   // or request('end_date')

        $rawReport = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'fh.id', '=', 'ft.credit_head_id')
            ->join('users as user', 'ft.user_id', '=', 'user.id')
            ->leftJoin('finance_vouchers', 'ft.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->select(
                'ft.transaction_date',
                'ft.user_id',
                'user.name as user_name',
                'fh.name as head_name',
                'ft.reference_type',
                DB::raw('COUNT(ft.reference_type) as total_count'),
                DB::raw('SUM(ft.amount) as total_amount')
            )
            ->whereBetween('ft.transaction_date', [$start_date, $end_date])
            ->where('ft.is_active', 1)
            ->where('ft.reference_type', '!=', 'commission')
            ->groupBy(
                'ft.transaction_date',
                'ft.user_id',
                'user.name',
                'ft.reference_type',
                'fh.name'
            )
            ->orderBy('ft.transaction_date')
            ->orderBy('ft.user_id')
            ->get()
            ->groupBy('transaction_date');

        // Build day-wise report
        $finalReport = [];

        foreach ($rawReport as $date => $dayGroup) {
            $users = $dayGroup->groupBy('user_id')->map(function ($transactions, $userId) use ($date) {
                $userName = $transactions->first()->user_name;
                $advance = user_advance($userId, $date, $date); // daily advance

                $txns = $transactions->map(function ($t) {
                    return [
                        'head_name'      => $t->head_name,
                        'reference_type' => $t->reference_type,
                        'total_count'    => $t->total_count,
                        'total_amount'   => $t->total_amount,
                    ];
                });

                return [
                    'user_id'      => $userId,
                    'name'         => $userName,
                    'user_advance' => $advance,
                    'transactions' => $txns,
                ];
            })->values();

            $finalReport[$date] = [
                'date' => $date,
                'users' => $users
            ];
        }

        return view('Finance.Reports.user_base_closing_report2', compact('start_date', 'end_date', 'finalReport'));
        //return view('Finance.Reports.user_base_closing_report2',compact('start_date', 'end_date', 'finalReport'));
    }


    public function trail_balance_report(Request $request)
    {
        $start_date = request()->start_date ?? date('Y-m-d');
        $end_date = request()->end_date ?? date('Y-m-d');

        // Get all debit amounts grouped by head
        $debits = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'ft.debit_head_id', '=', 'fh.id')
            ->leftJoin('finance_vouchers', 'ft.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->whereBetween('ft.transaction_date', [$start_date, $end_date])
            ->select('fh.id', 'fh.name', DB::raw('SUM(ft.amount) as total_debit'))
            ->groupBy('fh.id', 'fh.name')
            ->get();

        // Get all credit amounts grouped by head
        $credits = DB::table('finance_transactions as ft')
            ->join('finance_heads as fh', 'ft.credit_head_id', '=', 'fh.id')
            ->whereBetween('ft.transaction_date', [$start_date, $end_date])
            ->leftJoin('finance_vouchers', 'ft.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->select('fh.id', 'fh.name', DB::raw('SUM(ft.amount) as total_credit'))
            ->groupBy('fh.id', 'fh.name')
            ->get();

        // Merge both results using head_id
        $final_balance = [];

        foreach ($debits as $row) {
            $final_balance[$row->id] = [
                'head_name' => $row->name,
                'debit' => $row->total_debit,
                'credit' => 0,
            ];
        }

        foreach ($credits as $row) {
            if (isset($final_balance[$row->id])) {
                $final_balance[$row->id]['credit'] = $row->total_credit;
            } else {
                $final_balance[$row->id] = [
                    'head_name' => $row->name,
                    'debit' => 0,
                    'credit' => $row->total_credit,
                ];
            }
        }

        // Totals
        $total_debit = array_sum(array_column($final_balance, 'debit'));
        $total_credit = array_sum(array_column($final_balance, 'credit'));
        $is_balanced = ($total_debit == $total_credit);

        return view('Finance.Reports.trail_balance_report', compact(
            'final_balance', 'total_debit', 'total_credit', 'is_balanced',
            'start_date', 'end_date'
        ));

        return view('Finance.Reports.trail_balance_report', compact('final_balance', 'total_debit', 'total_credit', 'is_balanced', 'start_date', 'end_date'));
    }

    public function finance_vouchers_report()
    {
        $start_date = request()->start_date ?? now()->toDateString();
        $end_date = request()->end_date ?? now()->toDateString();
        $voucher_type_param = request()->voucher_type ?? "";


        $enumRaw = DB::select("SHOW COLUMNS FROM finance_vouchers WHERE Field = 'voucher_type'");
        $enumString = collect($enumRaw)->pluck('Type')->first();
        preg_match('/enum\((.*)\)/', $enumString, $matches);
        $voucherTypes = collect(explode(',', $matches[1]))->map(fn($v) => trim($v, " '"));

        $vouchers = DB::table('finance_vouchers as fv')
            ->whereBetween('fv.voucher_date', [$start_date, $end_date])
            ->whereNotNull('fv.approved_by')
            ->orderBy('fv.voucher_date')
            ->when($voucher_type_param, function ($query) use ($voucher_type_param) {
                return $query->where('voucher_type',$voucher_type_param);
            })
            ->get();

       // dd($vouchers);

        // Fetch transactions grouped by voucher_id
        $transactions = DB::table('finance_transactions as ft')
            ->join('finance_heads as dh', 'ft.debit_head_id', '=', 'dh.id')
            ->join('finance_heads as ch', 'ft.credit_head_id', '=', 'ch.id')
            ->join('users', 'ft.user_id', '=', 'users.id')
            ->leftJoin('finance_vouchers', 'ft.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->whereBetween('ft.transaction_date', [$start_date, $end_date])

            ->select(
                'ft.voucher_id',
                'ft.transaction_date',
                'ft.amount',
                'dh.name as debit_head',
                'ch.name as credit_head',
                'users.name as user_name',
                'ft.remarks'
            )
            ->orderBy('ft.transaction_date')
            ->get()
            ->groupBy('voucher_id');

        return view('Finance.Reports.finance_vouchers_report', compact('vouchers', 'transactions', 'start_date', 'end_date','voucherTypes',"voucher_type_param"));

        //Finance.Reports.
    }

    public function finance_ledger_report()
    {
        $finance_head_id = request()->finance_head_id ?? '';
        $start_date = request()->start_date ?? date('Y-m-01');
        $end_date = request()->end_date ?? date('Y-m-d');

        // All finance heads for dropdown
        $finance_heads = DB::table('finance_heads')->orderBy('name')->get();

        $entries = collect();
        $running_balance = 0;
        $head_type = null;

        if ($finance_head_id) {
            // Get the finance head type
            $head = DB::table('finance_heads')->where('id', $finance_head_id)->first();
            $head_type = $head?->type;

        $entries = DB::table('finance_transactions as ft')

            ->leftJoin('users', 'users.id', '=', 'ft.user_id')
            ->leftJoin('finance_vouchers', 'ft.voucher_id', '=', 'finance_vouchers.id')
            ->whereNotNull('finance_vouchers.approved_by')
            ->whereBetween('ft.transaction_date', [$start_date, $end_date])
            ->where(function ($query) use ($finance_head_id) {
                $query->where('ft.debit_head_id', $finance_head_id)
                    ->orWhere('ft.credit_head_id', $finance_head_id);
            })
            ->orderBy('ft.transaction_date')
            ->orderBy('ft.id')
            ->select(
                'ft.transaction_date',
                'ft.amount',
                'ft.debit_head_id',
                'ft.credit_head_id',
                'ft.remarks',
                'users.name as user_name'
            )
            ->get()
            ->map(function ($row) use ($finance_head_id, $head_type, &$running_balance) {
                $row->debit = $row->debit_head_id == $finance_head_id ? $row->amount : 0;
                $row->credit = $row->credit_head_id == $finance_head_id ? $row->amount : 0;

                // Calculate running balance based on head type
                if (in_array($head_type, ['asset', 'expense'])) {
                    $running_balance += $row->debit - $row->credit;
                } else {
                    $running_balance += $row->credit - $row->debit;
                }

                $row->running_balance = $running_balance;
                return $row;
            });
    }

        return view('Finance.Reports.finance_ledger_report', compact(
            'finance_heads',
            'finance_head_id',
            'entries',
            'start_date',
            'end_date'
        ));
    }

    public function outstanding_balances_report()
    {
        $heads = DB::table('finance_heads')
            ->whereIn('type', ['asset', 'liability'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        foreach ($heads as $head) {
            $totals = DB::table('finance_transactions')
                ->join('finance_vouchers', 'finance_transactions.voucher_id', '=', 'finance_vouchers.id')
                ->whereNotNull('finance_vouchers.approved_by')
                ->selectRaw("
                SUM(CASE WHEN debit_head_id = ? THEN amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN credit_head_id = ? THEN amount ELSE 0 END) as total_credit
            ", [$head->id, $head->id])
                ->first();

            $head->total_debit = $totals->total_debit ?? 0;
            $head->total_credit = $totals->total_credit ?? 0;

            if ($head->type === 'asset') {
                $head->balance = $head->total_debit - $head->total_credit;
            } else { // liability
                $head->balance = $head->total_credit - $head->total_debit;
            }

            // Optional: Add status label
            if ($head->type === 'asset') {
                $head->status = $head->balance >= 0 ? 'Receivable' : 'Overpaid';
            } else {
                $head->status = $head->balance >= 0 ? 'Payable' : 'Advance';
            }
        }


        return view('Finance.Reports.outstanding_balances_report', compact('heads'));
    }

}
