<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Appointments\Appointment;
use App\Models\Finance\DailyUserClosing;
use App\Models\Finance\FinanceHead;
use App\Models\Finance\FinanceTransaction;
use App\Models\Patient\InPatientAdmission;
use App\Models\Patient\PatientAdmission;
use App\Models\Patient\PatientInvestigation;
use App\Models\Patient\PatientInvestigationPayment;
use App\Models\Patient\PatientServiceCharges;
use App\Models\PharmacyRetrun;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function daily_closing()
    {
        $closing_date = $_GET['closing_date'] ?? date("Y-m-d");
        $user_id = $_GET['user_id'] ?? 0;
        $data['user_id'] = $user_id;
        $data['closing_date'] = $closing_date;
        $data['users'] = Users::where("status",1)->get();
        $data['finance_heads'] = FinanceHead::get();

        $query = SalePayment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            });

        $totals = $query->selectRaw('SUM(amount) as received_amount')->first();
        $data['data'] = $totals;


        $data['pharmacy_return'] = $this->total_return_in_pharmacy($closing_date,$user_id);
        $data['appointments'] = $this->appointmentsPayment($closing_date,$user_id);
        $data['investigations'] = $this->investigationPayment($closing_date,$user_id);
        $data['service_charges'] = $this->serviceCharges($closing_date,$user_id);
        $data['in_patient_sale'] = $this->in_patient_sale($closing_date,$user_id);


       return view("Finance.daily_closing",$data);
    }

    public function post_daily_closing()
    {
        $user_id = request()->user_id;
        $closing_date = request()->closing_date;
        if(request()->finance_head_id == ''){
            echo "Please select account head to post amount";
            exit;
        }
        $query = SalePayment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            });

        $sale_totals = $query->selectRaw('SUM(amount) as received_amount')->first();
        $appointments = $this->appointmentsPayment($closing_date,$user_id);
        $investigations = $this->investigationPayment($closing_date,$user_id);





        $sale = $sale_totals->received_amount ?? 0;
        $appointments = $appointments->total_fees ?? 0;
        $investigations = $investigations->cash_in_hand ?? 0;
        $pharmacy_return = $this->total_return_in_pharmacy($closing_date,$user_id);
        $service_charges = $this->serviceCharges($closing_date,$user_id);
        $total_amount = ($sale) + ($appointments) + ($investigations) + ($service_charges);


        $record = [];


        if($sale > 0){
            array_push($record,[
                'transaction_date' => today(),
                'amount' => ($sale),
                'debit_head_id' => request()->finance_head_id,  //cash at office
                'credit_head_id' => financeHeadId('pharmacy_income'), // pharmacy income
                'reference_type' => 'sale',
                'reference_id' => NULL,
                'user_id' => auth()->id(),
                'remarks' => 'Full pharmacy sale posted to cash at office by '.auth()->user()->name,
                'created_at' => now()
            ]);
        }
        if($pharmacy_return > 0){
            $return = $query = PharmacyRetrun::where("is_posted",0)
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by',$user_id);
                })->get();
            foreach ($return as $key => $value){
                array_push($record,[
                    'transaction_date' => today(),
                    'amount' => $value->amount, // return amount
                    'debit_head_id' => financeHeadId('pharmacy_income'), // reduce income
                    'credit_head_id' => request()->finance_head_id, // reduce cash
                    'reference_type' => 'pharmacy_return',
                    'reference_id' => null,
                    'user_id' => auth()->id(),
                    'remarks' => 'Pharmacy return (cash refunded)',
                    'created_at' => now()
                ]);
            }


        }
        if($appointments > 0){
            array_push($record,[
                'transaction_date' => today(),
                'amount' => $appointments,
                'debit_head_id' => request()->finance_head_id,  //cash at office
                'credit_head_id' => financeHeadId('appointment_income'), // Appointments income
                'reference_type' => 'appointments',
                'reference_id' => NULL,
                'user_id' => auth()->id(),
                'remarks' => 'Full appointment fee posted to cash at office by '.auth()->user()->name,
                'created_at' => now()
            ]);

            $all_appointments = Appointment::with(['consultant'])->where("is_posted",0)
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('appointment_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by',$user_id);
                })->get();
            foreach ($all_appointments as $key => $value){
                if($value->consultant_share > 0){
                    $rec = [
                        'transaction_date' => today(),
                        'amount' => $value->consultant_share,
                        'debit_head_id' => financeHeadId('doctor_commission'),  // Doctor commision expense
                        'credit_head_id' => $value->consultant->finance_head_id, // Dr. Naqeeb Ahmad (Liability)
                        'reference_type' => 'appointments',
                        'reference_id' => $value->id,
                        'user_id' => auth()->id(),
                        'remarks' => 'Consultant share posted to doctor account by '.auth()->user()->name,
                        'created_at' => now()
                    ];
                    array_push($record,$rec);
                }

            }

        }

        if($investigations > 0){
            array_push($record,[
                'transaction_date' => today(),
                'amount' => $investigations,
                'debit_head_id' => request()->finance_head_id,  //cash at office
                'credit_head_id' => financeHeadId('investigation_income'), //investigation_income
                'reference_type' => 'patient_investigations',
                'reference_id' => NULL,
                'user_id' => auth()->id(),
                'remarks' => 'Full investigation fee posted to cash at office by '.auth()->user()->name,
                'created_at' => now()
            ]);
        }

        if($service_charges > 0){
            $query = PatientServiceCharges::where("patient_service_charges.is_posted",0)
                ->whereIn("in_patient_admissions.admission_status",["Discharged","Reffered"])
                ->leftJoin("in_patient_admissions","in_patient_admissions.id","=","patient_service_charges.admission_id")
                //->whereNull("admission_id")
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('patient_service_charges.service_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })

                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('patient_service_charges.created_by',$user_id);
                })->get();
            foreach ($query as $key => $value){

                array_push($record,[
                    'transaction_date' => today(),
                    'amount' => $value->service_rate,
                    'debit_head_id' => request()->finance_head_id,  //cash at office
                    'credit_head_id' => $value->service_type->finance_head_id, // service_head id
                    'reference_type' => financeHeadCode($value->service_type->finance_head_id),
                    'reference_id' => $value->id,
                    'user_id' => auth()->id(),
                    'remarks' => $value->service_type->name." Payment received. posted by ".auth()->user()->name,
                    'created_at' => now()
                ]);
            }

            //-------- doctor account will credit from procedure percentage of admission  -----------//
            $all_admissions = InPatientAdmission::with(['consultant'])
                ->where("is_posted",0)
                ->whereIn("admission_status",["Discharged","Reffered"])
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('admission_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('discharge_by',$user_id);
                })->get();

            foreach ($all_admissions as $key => $value){
                if($value->consultant_share > 0){
                    $rec = [
                        'transaction_date' => today(),
                        'amount' => $value->consultant_share_amount,
                        'debit_head_id' => financeHeadId('doctor_commission'),  // Doctor commision expense
                        'credit_head_id' => $value->consultant->finance_head_id, // Dr. Naqeeb Ahmad (Liability)
                        'reference_type' => 'in_patient_admission',
                        'reference_id' => $value->id,
                        'user_id' => auth()->id(),
                        'remarks' => 'Consultant share posted to doctor account by '.auth()->user()->name,
                        'created_at' => now()
                    ];
                    array_push($record,$rec);
                }

            }
        }




        FinanceTransaction::insert($record);
        $remarks = "Closing done by ".auth()->user()->name." on ".date("Y-m-d H:i:s");
        DailyUserClosing::create(["user_id"=>auth()->user()->id,"closing_date"=>$closing_date,"investigation_amount"=>$investigations,"sale_amount"=>$sale,"appointment_amount"=>$appointments,"total_amount"=>$total_amount,"remarks"=>$remarks]);
        $this->update_post_status($closing_date,$user_id);
        return redirect()->back()->with('success', 'Record Posted Successfully.');
    }

    public function total_return_in_pharmacy($closing_date='',$user_id='')
    {
        $query = PharmacyRetrun::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->sum('amount');


        return $query;
    }

    public function appointmentsPayment($closing_date='',$user_id='')
    {
        $query = Appointment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('appointment_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            });

        $totals = $query->selectRaw('SUM(fee) as total_fees, SUM(hospital_share) as total_hospital_share, SUM(consultant_share) as total_consultant_share')->first();
        return $totals;
    }

    public function investigationPayment($closing_date='',$user_id='')
    {
        $query = PatientInvestigationPayment::where("is_posted",0)
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            });

        $totals = $query->selectRaw('SUM(amount) as cash_in_hand')->first();
        //dd($totals);
        return $totals;
    }

    public function serviceCharges($closing_date='',$user_id='')
    {
        $query = PatientServiceCharges::where("patient_service_charges.is_posted",0)
            ->leftJoin("in_patient_admissions","in_patient_admissions.id","=","patient_service_charges.admission_id")
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('patient_service_charges.service_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("patient_service_charges.is_active",1)
            ->whereIn("in_patient_admissions.admission_status",["Discharged","Reffered"])
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('patient_service_charges.created_by',$user_id);
            });

        $totals = $query->sum('service_rate');

        //dd($totals);
        return $totals ?? 0;
    }

    public function update_post_status($closing_date,$user_id)
    {
        SalePayment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);


        Appointment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('appointment_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);

        PatientInvestigationPayment::where("is_posted",0)
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);

       PatientServiceCharges::where("is_posted",0)
            ->where("is_active",1)
            ->with(['service_type'])
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('service_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);

        PharmacyRetrun::where("is_posted",0)
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);


        Sale::where("is_posted",0)->when($closing_date, function ($query) use ($closing_date) {
            return $query->whereDate('CreatedAt', '<=', date("Y-m-d", strtotime($closing_date)));
        })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('CreatedBy',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);


        return true;
    }


    public function cash_payment_voucher()
    {
        $data['finance_heads'] = FinanceHead::whereIn("type",["asset","expense"])->get();
        $data['sub_heads'] = FinanceHead::whereIn("type",["liability","expense","income"])->get();
        $data['voucher'] = FinanceTransaction::query()
            ->where('reference_type', 'cash_payment_voucher')
            ->where(["is_active"=>1])
            ->leftJoin('finance_heads as debit_heads', 'finance_transactions.debit_head_id', '=', 'debit_heads.id')
            ->leftJoin('finance_heads as credit_heads', 'finance_transactions.credit_head_id', '=', 'credit_heads.id')
            ->select(
                'finance_transactions.*',
                'debit_heads.name as debit_head_name',
                'credit_heads.name as credit_head_name'
            )
            ->orderBy("id","DESC")
            ->paginate(30);




        return view("Finance.cash_payment_voucher",$data);
    }

    public function save_cash_payment_voucher()
    {
        $amount = request()->amount;
        if($amount > 0){
            $record = [
                'transaction_date' => today(),
                'amount' => $amount,
                'credit_head_id' => request()->credit_head_id, // Appointments income
                'debit_head_id' => request()->debit_head_id,  //cash at office
                'reference_type' => 'cash_payment_voucher',
                'reference_id' => NULL,
                'user_id' => auth()->id(),
                'is_approved' => 0,
                'remarks' => request()->remarks.". This entry made by ".auth()->user()->name,
                'created_at' => now()
            ];
        }

        FinanceTransaction::insert($record);
        return redirect()->back()->with('success', 'Record saved successfully.');
    }

    public function cash_receipt_voucher()
    {
        $data['finance_heads'] = FinanceHead::where(["type"=>"asset"])->get();
        $data['sub_heads'] = FinanceHead::whereIn("type",["liability"])->get();

        $data['voucher'] = FinanceTransaction::query()
            ->where('reference_type', 'cash_receipt_voucher')
            ->where(["is_active"=>1])
            ->leftJoin('finance_heads as debit_heads', 'finance_transactions.debit_head_id', '=', 'debit_heads.id')
            ->leftJoin('finance_heads as credit_heads', 'finance_transactions.credit_head_id', '=', 'credit_heads.id')
            ->select(
                'finance_transactions.*',
                'debit_heads.name as debit_head_name',
                'credit_heads.name as credit_head_name'
            )

            ->orderBy("id","DESC")
            ->paginate(30);
        return view("Finance.cash_receipt_voucher",$data);
    }

    public function save_cash_receipt_voucher()
    {
        $amount = request()->amount;
        if($amount > 0){
            $record = [
                'transaction_date' => today(),
                'amount' => $amount,
                'debit_head_id' => request()->debit_head_id,  //cash at office
                'credit_head_id' => request()->credit_head_id, // Appointments income
                'reference_type' => 'cash_receipt_voucher',
                'reference_id' => NULL,
                'is_approved' => 0,
                'user_id' => auth()->id(),
                'remarks' => request()->remarks,
                'created_at' => now()
            ];
        }

        FinanceTransaction::insert($record);
        return redirect()->back()->with('success', 'Record saved successfully.');
    }

    public function getBalance()
    {
        $debits = DB::table('finance_transactions')
            ->select('debit_head_id as head_id', DB::raw('SUM(amount) as total_debit'))
            ->where(["is_approved"=>1,"is_active"=>1])
            ->groupBy('debit_head_id');

        // Subquery: Credit totals
        $credits = DB::table('finance_transactions')
            ->where(["is_approved"=>1,"is_active"=>1])
            ->select('credit_head_id as head_id', DB::raw('SUM(amount) as total_credit'))
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
            ->where("id",request()->id)
            ->get()
            ->map(function ($head) {
                if (in_array($head->type, ['asset', 'expense'])) {
                    $head->balance = $head->total_debit - $head->total_credit;
                } else {
                    $head->balance = $head->total_credit - $head->total_debit;
                }
                return $head;
            });

            return $report[0]['balance'] ?? 0;
    }


    public function in_patient_sale($closing_date='',$user_id='')
    {


        $query = Sale::where("is_posted",0)->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('CreatedAt', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('CreatedBy',$user_id);
            });

        $totals = $query->selectRaw('SUM(TotalSale)-SUM(Discount) as in_patient_sale')->first();

        return $totals->in_patient_sale ?? 0;


    }

    public function approve_transaction_entry()
    {
        FinanceTransaction::where(["id"=>request()->id])->update(["is_approved"=>1]);
        return ["status"=>true,"message"=>"record approved successfully"];
    }

    public function delete_transaction_entry()
    {
        FinanceTransaction::where(["id"=>request()->id])->update(["is_active"=>0]);
        return ["status"=>true,"message"=>"record approved successfully"];
    }



}
