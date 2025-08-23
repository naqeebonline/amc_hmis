<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Appointments\Appointment;
use App\Models\Finance\DailyUserClosing;
use App\Models\Finance\FinanceHead;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceVoucher;
use App\Models\Patient\InPatientAdmission;
use App\Models\Patient\PatientAdmission;
use App\Models\Patient\PatientInvestigation;
use App\Models\Patient\PatientInvestigationPayment;
use App\Models\Patient\PatientServiceCharges;
use App\Models\PharmacyRetrun;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SalePayment;
use App\Models\Users;
use Carbon\Carbon;
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
        $data['users'] = Users::where("status",1)
            ->when((getUserRole() != 'Super Admin' && getUserRole() != 'Finance'), function ($query) use ($user_id) {
                return $query->where('id',auth()->user()->id);
            })
            ->get();

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
        $data['consultant_charges'] = $this->consultant_charges($closing_date,$user_id);
        $data['voucher'] = FinanceVoucher::when((getUserRole() != 'Super Admin' && getUserRole() != 'Finance'), function ($query) use ($user_id) {
            return $query->where(["created_by"=>auth()->user()->id]);
        })->with(['user'])->orderBy("id","desc")
            ->where(["voucher_type"=>"closing"])
            ->paginate(20);


       return view("Finance.daily_closing",$data);
    }

    public function post_daily_closing()
    {


        $not_approve_transaction = FinanceVoucher::where(["voucher_type"=>"closing","created_by"=>auth()->user()->id])->whereNull('approved_by')->first();
        if($not_approve_transaction){
            return redirect()->back()->with("error","Unapproved transaction exist. approve it and then post next transaction");
        }

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
        $consultant_charges = $this->consultant_charges($closing_date,$user_id);
        $total_amount = ($sale) + ($appointments) + ($investigations) + ($service_charges) + ($consultant_charges);

        $voucher = generateVoucherNumber("closing",$user_id);

        $voucher_data = [
            "voucher_number" =>$voucher,
            "voucher_type"   => "closing",
            'user_id' => $user_id,
            'created_by' => auth()->user()->id,
            "voucher_date"   => date("Y-m-d"),
            "total_amount"   => $total_amount,
            "remarks"   => "Daily user closing of ".auth()->user()->name ?? '',
            "created_by"   => auth()->user()->id,
            "created_at"   => date("Y-m-d H:i:s"),
        ];
        $voucher = FinanceVoucher::create($voucher_data);
        $voucher_id = $voucher->id;
        //$voucher_id = 1;

        $record = [];

        if($sale > 0){
            $query = SalePayment::where("is_posted",0)
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by',$user_id);
                })->get();

            //naqeeb

            foreach ($query as $key => $value){
                if($value->sale_id){
                    $cogs_amount = $this->cogs_purchase($value->sale_id);
                }else{
                    $cogs_amount = $this->cogs_purchase_in_patient($value->admission_id);
                }

                $amount = $value->amount;
                $remarks = "Pharamacy Income";
                // cash at office debuit   pharmacy income credit
                make_entry($voucher_id,request()->finance_head_id,$amount,0,"sale",$value->id,$user_id,$remarks);
                make_entry($voucher_id,financeHeadId('pharmacy_income'),0,$amount,"sale",$value->id,$user_id,$remarks);

                $remarks = "pharmacy_sale_cogs";
                // cash at office debuit   pharmacy_purchase credit
                make_entry($voucher_id,financeHeadId('cogs'),$cogs_amount,0,"pharmacy_sale_cogs",$value->id,$user_id,$remarks);
                make_entry($voucher_id,financeHeadId('pharmacy_purchase'),0,$cogs_amount,"pharmacy_sale_cogs",$value->id,$user_id,$remarks);

            }
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
                $amount = $value->amount;
                $remarks = "Pharmacy Return";

                // pharmacy_return debit     cash at office credit
                make_entry($voucher_id,financeHeadId('pharmacy_return'),$amount,0,"pharmacy_return",$value->id,$user_id,$remarks);
                make_entry($voucher_id,request()->finance_head_id,0,$amount,"pharmacy_return",$value->id,$user_id,$remarks);

                $cost_of_good_sales_of_return_item = $this->cogs_after_return($value->id);
                // pharmacy_purchase debit    cogs credit
                make_entry($voucher_id,financeHeadId('pharmacy_purchase'),$cost_of_good_sales_of_return_item,0,"cogs_pharmacy_sale_return",$value->id,$user_id,$remarks);
                make_entry($voucher_id,financeHeadId('cogs'),0,$cost_of_good_sales_of_return_item,"cogs_pharmacy_sale_return",$value->id,$user_id,$remarks);



            }
        }
        if($appointments > 0){

            $all_appointments = Appointment::with(['consultant'])
                ->where("is_posted",0)
                ->where("is_active",1)
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('appointment_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by',$user_id);
                })->get();



            foreach ($all_appointments as $key => $value){

                //-- after posting cash to cashAtOffice here the docotro commision will post to doctor account  ---//
                if($value->consultant_share > 0){
                    $amount = $value->consultant_share;
                    $remarks = 'Appointment Share posted to Doctor: '.$value->consultant->name ?? "".' Account. posted by '.auth()->user()->name;

                    // Cash at office debit   doctor account credit
                    make_entry($voucher_id,request()->finance_head_id,$amount,0,"appointments_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,$value->consultant->finance_head_id,0,$amount,"appointments_shares",$value->id,$user_id,$remarks);


                    $amount = $value->hospital_share;
                    $remarks = 'Appointment Hospital shares posted by '.auth()->user()->name;

                    // Cash at office debit   Appointment income credit
                    make_entry($voucher_id,request()->finance_head_id,$amount,0,"appointments_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,financeHeadId('appointment_income'),0,$amount,"appointments_shares",$value->id,$user_id,$remarks);

                }else{
                    $amount = $value->fee;
                    $remarks = 'Appointment Income.';

                    // Cash at office debit   Appointment income credit
                    make_entry($voucher_id,request()->finance_head_id,$amount,0,"appointments_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,financeHeadId('appointment_income'),0,$amount,"appointments_shares",$value->id,$user_id,$remarks);

                }

            }

        }

        if($investigations > 0){
            $query = PatientInvestigationPayment::where("is_posted",0)
                ->where("is_active",1)
                //->whereNull("admission_id")
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by',$user_id);
                })->get();
            foreach ($query as $key => $value){
                $amount = $value->amount;
                $remarks =  'Investigation Income posted to cash at office. Posted by '.auth()->user()->name;
                // Cash at office debit   investigation income credit
                make_entry($voucher_id,request()->finance_head_id,$amount,0,"investigations_income",$value->id,$user_id,$remarks);
                make_entry($voucher_id,financeHeadId('investigation_income'),0,$amount,"investigations_income",$value->id,$user_id,$remarks);
            }

            //----------- from investigations we are posting doctor commission to doctor account  -----//
            $inves = PatientInvestigation::with(['consultant','investigation'])
                    ->where("is_posted",0)
                    ->where("is_active",1)
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('inv_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('created_by',$user_id);
                })->get();


            foreach ($inves as $key => $value){
                //------- only for out patient ... shares will be generated only for out patient investigations ----//
                if($value->consultant_share_amount > 0 && $value->admission_id == NULL){
                    $amount = $value->consultant_share_amount;
                    $remarks =  $value->investigation->name." Shared posted to Doctor Account. Rs.$amount. Posted By ".auth()->user()->name;
                    // Doctor Account Credit
                    make_entry($voucher_id,$value->consultant->finance_head_id,0,$amount,"investigation_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,financeHeadId('Investigation_Shares'),$amount,0,"investigation_shares",$value->id,$user_id,$remarks);
                }

                //-------- investigation per kitna kharcha aaya hai vo yaha calculate hota hai .......//
                $investigation_cost = ($value->investigation->price * $value->frequency) ?? 0;
                $remarks = $value->investigation->name." cost Rs.$investigation_cost";
                make_entry($voucher_id,financeHeadId('Laboratory_Purchase'),0,$investigation_cost,"cost_of_lab_consumable",$value->id,$user_id,$remarks);
                make_entry($voucher_id,financeHeadId('Cost_of_Investigation'),$investigation_cost,0,"cost_of_lab_consumable",$value->id,$user_id,$remarks);


            }
        }

        if($service_charges > 0){
            $query = PatientServiceCharges::where("patient_service_charges.is_posted",0)
                ->whereIn("in_patient_admissions.admission_status",["Discharged","Reffered","Canceled"])
                ->leftJoin("in_patient_admissions","in_patient_admissions.id","=","patient_service_charges.admission_id")
                //->whereNull("admission_id")
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('patient_service_charges.service_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })

                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('patient_service_charges.created_by',$user_id);
                })->get();
            foreach ($query as $key => $value){
              if($value->service_rate > 0){
                  $amount = $value->service_rate;
                  $remarks =  $value->service_type->name." Payment Posted to cash at office. posted by ".auth()->user()->name;

                  // Cash at office debit   patient services income credit
                  make_entry($voucher_id,request()->finance_head_id,$amount,0,"service_charges",$value->id,$user_id,$remarks);
                  make_entry($voucher_id,$value->service_type->finance_head_id,0,$amount,"service_charges",$value->id,$user_id,$remarks);

              }

            }

            //-------- doctor account will credit from procedure percentage of admission  -----------//
            $all_admissions = InPatientAdmission::with(['consultant'])
                ->where("is_posted",0)
                ->whereIn("admission_status",["Discharged"])
                ->when($closing_date, function ($query) use ($closing_date) {
                    return $query->whereDate('admission_date', '<=', date("Y-m-d", strtotime($closing_date)));
                })
                ->when($user_id, function ($query) use ($user_id) {
                    return $query->where('discharge_by',$user_id);
                })->get();
          //  dd($all_admissions);
            foreach ($all_admissions as $key => $value){
                if($value->consultant_share_amount > 0){
                    $amount = $value->consultant_share_amount;
                    $remarks = 'Procedure share posted to doctor: '.$value->consultant->name ?? "".' account by '.auth()->user()->name;

                    // Cash at office debit   doctor account credit
                    make_entry($voucher_id,request()->finance_head_id,$amount,0,"procedure_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,$value->consultant->finance_head_id,0,$amount,"procedure_shares",$value->id,$user_id,$remarks);



                    $amount = ($value->consultant_charges) - ($value->consultant_share_amount);
                    $remarks = 'Procedure income posted to Procedure Income by '.auth()->user()->name;

                    // Cash at office debit   procedure income credit
                    make_entry($voucher_id,request()->finance_head_id,$amount,0,"procedure_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,financeHeadId('procedure_income'),0,$amount,"procedure_shares",$value->id,$user_id,$remarks);

                }else{
                    $amount = ($value->consultant_charges);
                    $remarks = 'Procedure income posted by '.auth()->user()->name;

                    // Cash at office debit   procedure income credit
                    make_entry($voucher_id,request()->finance_head_id,$amount,0,"procedure_shares",$value->id,$user_id,$remarks);
                    make_entry($voucher_id,financeHeadId('procedure_income'),0,$amount,"procedure_shares",$value->id,$user_id,$remarks);

                }

            }
        }

      //  FinanceTransaction::insert($record);

        $remarks = "Closing done by ".auth()->user()->name." on ".date("Y-m-d H:i:s");
        DailyUserClosing::create([
            "user_id"=>auth()->user()->id,
            "closing_date"=>$closing_date,
            "investigation_amount"=>$investigations,
            "sale_amount"=>$sale,
            "appointment_amount"=>$appointments,
            "total_amount"=>$total_amount,
            "remarks"=>$remarks
            ]);

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
            ->whereIn("in_patient_admissions.admission_status",["Discharged","Reffered","Canceled"])
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('patient_service_charges.created_by',$user_id);
            });

        $totals = $query->sum('service_rate');

        //dd($totals);
        return $totals ?? 0;
    }

    public function update_post_status($closing_date,$user_id)
    {

        $all_admissions = InPatientAdmission::with(['consultant'])
            ->where("is_posted",0)
            ->whereIn("admission_status",["Discharged","Reffered","Canceled"])
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('admission_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('discharge_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d")]);

        SalePayment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"updated_by"=>auth()->user()->id,"updated_at"=>date("Y-m-d H:i:s")]);


        Appointment::where("is_posted",0)
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('appointment_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"updated_by"=>auth()->user()->id,"updated_at"=>date("Y-m-d H:i:s")]);

        PatientInvestigationPayment::where("is_posted",0)
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"updated_by"=>auth()->user()->id,"updated_at"=>date("Y-m-d H:i:s")]);

        PatientInvestigation::with(['consultant'])
            ->where("is_posted",0)
            ->where("is_active",1)
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->where("is_active",1)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"updated_by"=>auth()->user()->id,"updated_at"=>date("Y-m-d H:i:s")]);

       PatientServiceCharges::where("is_posted",0)
            ->where("is_active",1)
            ->with(['service_type'])
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('service_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"updated_by"=>auth()->user()->id,"updated_at"=>date("Y-m-d H:i:s")]);


        PharmacyRetrun::where("is_posted",0)
            //->whereNull("admission_id")
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('created_at', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('created_by',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"updated_by"=>auth()->user()->id,"updated_at"=>date("Y-m-d H:i:s")]);



        Sale::where("is_posted",0)->when($closing_date, function ($query) use ($closing_date) {
            return $query->whereDate('CreatedAt', '<=', date("Y-m-d", strtotime($closing_date)));
        })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('CreatedBy',$user_id);
            })->update(["is_posted"=>1,"posted_on"=>date("Y-m-d"),"ModifiedBy"=>auth()->user()->id,"ModifiedAt"=>date("Y-m-d")]);


        return true;
    }


    public function cash_payment_voucher()
    {
        $data['finance_heads'] = FinanceHead::whereIn("type",["asset","liability","expense","income"])->get();
        $data['sub_heads'] = FinanceHead::whereIn("type",["asset","liability","expense","income"])->get();
        $data['vouchers'] = FinanceVoucher::orderBy("id","DESC")->where("voucher_type","payment")->paginate(30);





        return view("Finance.cash_payment_voucher",$data);
    }

    public function save_cash_payment_voucher()
    {

        $amount = request()->amount;
        $voucher = generateVoucherNumber("Payment",auth()->user()->id);

        $voucher_data = [
            "voucher_number" =>$voucher,
            "user_id"   =>  auth()->user()->id,
            "voucher_type"   => "payment",
            "voucher_date"   => date("Y-m-d"),
            "total_amount"   => $amount,
            "remarks"   =>    request()->remarks.". Paid by ".auth()->user()->name,
            "created_by"   => auth()->user()->id,
            "created_at"   => date("Y-m-d H:i:s"),
        ];
        $voucher = FinanceVoucher::create($voucher_data);
        $voucher_id = $voucher->id;

        $remarks = request()->remarks.". Payment to ".financeHeadName(request()->debit_head_id)." From ".financeHeadName(request()->credit_head_id)." by ".auth()->user()->name;
        make_entry($voucher_id,request()->debit_head_id,$amount,0,"cash_payment_voucher",NULL,auth()->user()->id,$remarks);
        make_entry($voucher_id,request()->credit_head_id,0,$amount,"cash_payment_voucher",NULL,auth()->user()->id,$remarks);


        return redirect()->back()->with('success', 'Record saved successfully.');
    }



    public function cash_receipt_voucher()
    {
        $data['finance_heads'] = FinanceHead::whereIn("type",["liability","asset"])->get();
        $data['sub_heads'] = FinanceHead::whereIn("type",["liability"])->get();
        $data['vouchers'] = FinanceVoucher::orderBy("id","DESC")->where("voucher_type","receipt")->paginate(30);

        return view("Finance.cash_receipt_voucher",$data);
    }

    public function save_cash_receipt_voucher()
    {
        $amount = request()->amount;
        $voucher = generateVoucherNumber("Receipt",auth()->user()->id);

        $voucher_data = [
            "voucher_number" =>$voucher,
            "user_id"   =>  auth()->user()->id,
            "voucher_type"   => "receipt",
            "voucher_date"   => date("Y-m-d"),
            "total_amount"   => $amount,
            "remarks"   => request()->remarks.". Payment received by ".auth()->user()->name,
            "created_by"   => auth()->user()->id,
            "created_at"   => date("Y-m-d H:i:s"),
        ];
        $voucher = FinanceVoucher::create($voucher_data);
        if($amount > 0){
            $voucher_id = $voucher->id;

            $remarks = request()->remarks."- Received from ".financeHeadName(request()->credit_head_id)." by".auth()->user()->name;
            make_entry($voucher_id,request()->debit_head_id,$amount,0,"cash_payment_voucher",NULL,auth()->user()->id,$remarks);
            make_entry($voucher_id,request()->credit_head_id,0,$amount,"cash_payment_voucher",NULL,auth()->user()->id,$remarks);
        }


        return redirect()->back()->with('success', 'Record saved successfully.');
    }

    public function journal_voucher()
    {
        $data['finance_heads'] = FinanceHead::get();
        $data['sub_heads'] = FinanceHead::get();
        $data['vouchers'] = FinanceVoucher::orderBy("id","DESC")->where("voucher_type","journal_voucher")->paginate(30);
        return view("Finance.journal_voucher",$data);
    }

    public function save_journal_voucher()
    {

        $amount = request()->amount;
        $voucher = generateVoucherNumber("journal_voucher",auth()->user()->id);

        $voucher_data = [
            "voucher_number" =>$voucher,
            "user_id"   =>  auth()->user()->id,
            "voucher_type"   => "journal_voucher",
            "voucher_date"   => date("Y-m-d"),
            "total_amount"   => $amount,
            "remarks"   =>    request()->remarks.". Paid by ".auth()->user()->name,
            "created_by"   => auth()->user()->id,
            "created_at"   => date("Y-m-d H:i:s"),
        ];
        $voucher = FinanceVoucher::create($voucher_data);
        $voucher_id = $voucher->id;

        $remarks = request()->remarks.". Payment to ".financeHeadName(request()->debit_head_id)." From ".financeHeadName(request()->credit_head_id)." by ".auth()->user()->name;
        make_entry($voucher_id,request()->debit_head_id,$amount,0,"journal_voucher",NULL,auth()->user()->id,$remarks);
        make_entry($voucher_id,request()->credit_head_id,0,$amount,"journal_voucher",NULL,auth()->user()->id,$remarks);


        return redirect()->back()->with('success', 'Record saved successfully.');
    }

    public function getBalance()
    {
        $totals = DB::table('finance_transactions')
            ->select(
                'head_id',
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->where('is_active', 1)
            ->groupBy('head_id');

        $report = FinanceHead::leftJoinSub($totals, 'totals', 'finance_heads.id', '=', 'totals.head_id')
            ->select(
                'finance_heads.id',
                'finance_heads.name',
                'finance_heads.type',
                DB::raw('COALESCE(totals.total_debit, 0) as total_debit'),
                DB::raw('COALESCE(totals.total_credit, 0) as total_credit')
            )
            ->when(request()->filled('id'), function ($query) {
                $query->where('finance_heads.id', request()->id);
            })
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
            ->where('admission_id',"!=",0)
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('CreatedBy',$user_id);
            });

        $totals = $query->selectRaw('SUM(TotalSale)-SUM(Discount) as in_patient_sale')->first();
        return $totals->in_patient_sale ?? 0;
    }

    public function consultant_charges($closing_date='',$user_id='')
    {



        $total = InPatientAdmission::with(['consultant'])
            ->where("is_posted",0)
            ->whereIn("admission_status",["Discharged"])
            ->when($closing_date, function ($query) use ($closing_date) {
                return $query->whereDate('admission_date', '<=', date("Y-m-d", strtotime($closing_date)));
            })
            ->when($user_id, function ($query) use ($user_id) {
                return $query->where('discharge_by',$user_id);
            })->sum('consultant_charges');


        return $total;


    }

    public function approve_transaction_entry()
    {
        $voucher = FinanceVoucher::where("id",request()->id)->first();
        FinanceVoucher::where(["id"=>request()->id])->update(["approved_by"=>auth()->user()->id,"approved_at"=>date("Y-m-d H:i:s")]);

        if($voucher->voucher_type == 'closing'){
            $this->update_post_status($voucher->voucher_date,$voucher->user_id);
        }

        return ["status"=>true,"message"=>"record approved successfully"];
    }

    public function delete_transaction_entry()
    {
        FinanceVoucher::where(["id"=>request()->id])->delete();
        FinanceTransaction::where(["voucher_id"=>request()->id])->delete();
        return ["status"=>true,"message"=>"record approved successfully"];
    }

    public function cogs_purchase($sale_id)
    {
        $cogs = DB::table('sale_details')
            ->selectRaw('SaleID, SUM((Quantity - ReturnQuantity) * PurchasePrice) as cogs')
            ->where('SaleID', $sale_id)
            ->groupBy('SaleID')
            ->first();
        return $cogs->cogs ?? 0;
    }



    public function cogs_purchase_in_patient($admission_id)
    {
        $sale_ids = Sale::where("admission_id",$admission_id)->pluck('SaleID');
        $cogs = DB::table('sale_details')
            ->selectRaw('SaleID, SUM((Quantity - ReturnQuantity) * PurchasePrice) as cogs')
            ->whereIn('SaleID', $sale_ids)
            ->groupBy('SaleID')
            ->first();
        return $cogs->cogs ?? 0;
    }

    public function cogs_after_return($pharmacy_return_id)
    {
        $return = PharmacyRetrun::where("id",$pharmacy_return_id)->first();
        $return_qty = $return->quantity;
        $cogs = DB::table('sale_details')
            ->where('SaleID', $return->sale_id)
            ->where('ProductID', $return->product_id)
            ->first();

        return ($return_qty ?? 0) * ($cogs->PurchasePrice ?? 0);

    }

    public function create()
    {
       // $finance_heads = FinanceHead::whereNotNul('parent_id')->get();
        $finance_heads = FinanceHead::whereNotNull('parent_id')->get();
        $vouchers = FinanceVoucher::orderBy("id","DESC")->where("voucher_type","journal_voucher")->paginate(30);
        return view('Finance.journal_voucher', compact('finance_heads','vouchers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'entries' => 'required'
        ]);

        $entries = json_decode($request->entries, true);

        if(empty($entries)){
            return back()->with('error', 'No entries found.');
        }
        $remarks = "";
        foreach($entries as $entry){
            $remarks = $remarks.$entry['remarks']." , ";
        }

        $totalAmount = collect($entries)->where('type', 'credit')->sum('amount');
       // dd($totalAmount);


            $voucher = generateVoucherNumber("journal_voucher",auth()->user()->id);

            $voucher_data = [
                "voucher_number" =>$voucher,
                "user_id"   =>  auth()->user()->id,
                "voucher_type"   => "journal_voucher",
                "voucher_date"   => date("Y-m-d"),
                "total_amount"   => $totalAmount,
                "remarks"   =>    "JV Created by ".auth()->user()->name,
                "created_by"   => auth()->user()->id,
                "created_at"   => date("Y-m-d H:i:s"),
            ];
            $voucher = FinanceVoucher::create($voucher_data);
            $voucher_id = $voucher->id;

            foreach($entries as $entry){

                FinanceTransaction::create([
                    'voucher_id' => $voucher_id,
                    'transaction_date' => date("Y-m-d"),
                    'reference_type' => "journal_voucher",
                    'reference_id' => 0,
                    'head_id' => $entry['head_id'],
                    'debit' => $entry['type'] === 'debit' ? $entry['amount'] : 0,
                    'credit' => $entry['type'] === 'credit' ? $entry['amount'] : 0,
                    'remarks' => $remarks,
                    'user_id' => auth()->user()->id,
                    'created_by' => auth()->user()->id,
                    'created_at' => date("Y-m-d H:i:s"),
                ]);
            }


        return redirect()->route('pos.journal_voucher')->with('success', 'Journal Voucher Posted');
    }



}
