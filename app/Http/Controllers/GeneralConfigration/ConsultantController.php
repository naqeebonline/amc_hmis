<?php

namespace App\Http\Controllers\GeneralConfigration;

use App\Http\Controllers\Controller;
use App\Models\Accounts\ConsultantSehatCardPayment;
use App\Models\Accounts\ConsultantShareInvoice;
use App\Models\Configuration\ConsultantDepartment;
use App\Models\Configuration\Consultants;
use App\Models\Configuration\ConsultantSpeciality;
use App\Models\Configuration\ConsultantType;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ConsultantController extends Controller
{
    public function add_consultant()
    {
        $data["title"] = "Add Consultant";
        $data['department'] = ConsultantDepartment::get();
        $data['speciality'] = ConsultantSpeciality::get();
        $data['type'] = ConsultantType::get();
        return view("general_configuration.consultant_registration",$data);
    }

    public function list_consultant()
    {
        $res = Consultants::with(["consultant_department","consultant_speciality","consultant_type"])->where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_consultant()
    {
        Consultants::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function add_consultant_department()
    {
        $data["title"] = "Consultant Department";
        return view("general_configuration.consultant_department",$data);
    }

    public function list_consultant_department()
    {
        $res = ConsultantDepartment::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_consultant_department()
    {
        ConsultantDepartment::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function add_consultant_speciality()
    {
        $data["title"] = "Consultant Speciality";
        return view("general_configuration.consultant_speciality",$data);
    }

    public function list_consultant_speciality()
    {
        $res = ConsultantSpeciality::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_consultant_speciality()
    {
        ConsultantSpeciality::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function add_consultant_type()
    {
        $data["title"] = "Consultant Type";
        return view("general_configuration.consultant_type",$data);
    }

    public function list_consultant_type()
    {
        $res = ConsultantType::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function consultant_sc_balance($consultant_id)
    {
        $total_invoices = ConsultantShareInvoice::where("consultant_id",$consultant_id)->sum('total_amount');
        $total_payment_done_to_doctor = ConsultantSehatCardPayment::where("consultant_id",$consultant_id)->sum('amount');
        return ($total_invoices - $total_payment_done_to_doctor);
    }


    function supplier_previous_balance($customer_id,$date=''){
        $customer = Customer::where(["sup_cus_details.SCID"=>$customer_id])->first();

        $openingBalance=$customer->OpeningBalance;
        if(!$openingBalance){
            $openingBalance=0;
        }
        $where=array('SCID'=>$customer_id);
        if($date!=''){
            $where['Date <']=$date;

        }
        /*$TotalSale = Grn::where(["SCID"=>$customer_id])
            ->when($date, function ($query) use ($date) {
                return $query->where('Dated', '>=', date("Y-m-d",strtotime($date)));
            })->sum('TotalPurchase');*/

        $query = Grn::where('SCID', $customer_id)
            ->when($date, function ($query) use ($date) {
                return $query->where('Dated', '>=', date("Y-m-d", strtotime($date)));
            });

        $totals = $query->selectRaw('SUM(TotalPurchase) as total_bill, SUM(Discount) as discount, SUM(per_item_discount) as per_item_discount')
            ->first();

        $TotalSale = $totals->total_bill;
        $totalDiscount = $totals->discount;
        $per_item_discount = $totals->per_item_discount;


        $TotalPaid = PaymentDetail::where(["SCID"=>$customer_id])
            ->when($date, function ($query) use ($date) {
                return $query->where('Dated', '<', date("Y-m-d",strtotime($date)));
            })->sum('Amount');

        $TotalAmount = ($openingBalance + $TotalSale) - ($totalDiscount) - ($per_item_discount) - $TotalPaid;
        if($TotalAmount){
            return $TotalAmount;
        }else{
            return 0;
        }

    }




}
