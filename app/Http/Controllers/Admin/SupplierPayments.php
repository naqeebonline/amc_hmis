<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Grn;
use App\Models\GrnDetails;
use App\Models\GrnRequest;
use App\Models\GrnRequestDetails;
use App\Models\Market;
use App\Models\Patient\Patient;
use App\Models\PaymentDetail;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SupplierPayments extends Controller
{
    public function supplier_payments()
    {
        $data["title"] = "Supplier Payments";
        $data["suppliers"] = Customer::where("Type", 1)->get();
        $data["payment_type"] = PaymentType::get();
        return view("warehouse/supplier_payments", $data);
    }


    public function save_payments(Request $request)
    {
       
        $data = $request->all();
        $data["CreatedBy"] = Auth::user()->id;
        $data["CreatedAt"] = Carbon::today();
        $data["SCID"] = $request->SID;

        $paymentDetail = PaymentDetail::create($data);

        return response()->json([
            "data" => $paymentDetail
        ]);
    }


    public function get_payments($id)
    {
        $data = PaymentDetail::where('SCID', $id)
            ->with('paymentType');
        return DataTables::of($data)
            ->addColumn('payment_type', function ($data) {
                return $data->paymentType->payment_type ?? ''; // Safeguard against null
            })
           
            ->addColumn('action', function ($data) {
                // Example: Add an Edit/Delete button for actions
                return '<a class="btn btn-sm btn-primary">Edit</a>
                    <a class="btn btn-sm btn-danger">Delete</a>';
            })
            ->rawColumns(['payment_type','action']) // Allow raw HTML for action buttons
            ->make(true);
    }

    // public function get_payments($id){
    //     $data = PaymentDetail::where("SCID", $id)->with("paymentType")->get();

    //     // dd($data);
    //     return DataTables::of($data)
    //     ->addColumn('payment_type', function ($data) {
    //         return $data->paymentType->payment_type ?? '';
    //     });
    //     // return response()->json([
    //     //     "data"=> $data
    //     // ]);

    // }

    public function purchase_details($id=''){
        
        $data =  Grn::when($id, function ($query) use ($id) {
            return $query->where("SCID",$id);
        });
        //  <a class="btn btn-sm btn-primary" href="'.route('pos.edit_purchase_bill',[$data->GRNID]).'">Edit</a>
        return DataTables::of($data)
            ->addColumn('final_bill', function ($data) {
                return $data->TotalPurchase - ($data->per_item_discount) - ($data->Discount);
            })
            ->addColumn('action', function ($data) {

                return '<a class="btn btn-sm btn-success" href="'.route('pos.print_purchase',[$data->SCID, $data->GRNID]).'">Print Bill</a>
                <a class="btn btn-sm btn-success" href="'.route('pos.add_bill_items',[$data->GRNID]).'">Edit</a>
               
                    <a class="btn btn-sm btn-danger">Delete</a>';
            })
            ->rawColumns(['final_bill','action']) // Allow raw HTML for action buttons
            ->make(true);

        
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

    public function get_purchase_bill_items($id)
    {
        $data = GrnDetails::where('GRNID', $id)
            ->with('products');
        return DataTables::of($data)

            ->addColumn('total', function ($data) {
                return ($data->Quantity * $data->pack_price);

            })
            ->addColumn('action', function ($data) {
                return '<a class="btn btn-sm btn-primary edit_bill_item" data-details=\''.$data.'\'>Edit</a>
                    ';
            })
            ->rawColumns(['total','action']) // Allow raw HTML for action buttons
            ->make(true);
    }
    

    public function add_bill_items($id){
        $data["products"]= Product::with('generic_name')->where("IsActive", 1)->where("pack_size","!=",0)->where("pack_price","!=",0)->get();
        $data['grn'] = GrnRequest::where('GRNID', $id)->with("products")->first();
        $data['purchase'] = GrnRequestDetails::where('GRNID', $id)->with("products")->orderBy("GDID","DESC")->where(["ProductStatus" => 1])->get();
        $data['id'] = $id;



        // return $data;
        return view('reports/print_purchase_details', $data);
    }

    public function print_thermel_purchase_details($SaleID)
    {

        
        $date = date("Y-m-d");

        $data['record'] = Sale::where(['SaleID' => $SaleID])->get();
        $data['patient'] = Patient::where(["id"=> $data['record'][0]->patient_id])->first();
        $customer_id = $data['record'][0]->SCID;
        $billDate = date("d-m-Y", strtotime($data['record'][0]->Date));

        //$data['PreviousBalance']=(new CustomerPayments())->customer_previous_balance($customer_id,$date);

        $data['data'] = SaleDetails::with('product')->where(['SaleID' => $SaleID])->get();
        $data['title'] = 'Sale Details Report';
        $return = "No";
        $totalAmount = 0;
        $data['prev_balance'] = (new CustomerPayments())->customer_previous_balance($customer_id, '');

        foreach ($data['data'] as $rec) {
            $rec->AvaliableQuantity = ($rec->Quantity) - ($rec->ReturnQuantity);
            $rec->totalAmount = ($rec->AvaliableQuantity) * ($rec->UnitePrice);
            $totalAmount = ($totalAmount) + ($rec->totalAmount);
            if ($rec->ReturnQuantity > 0) {
                $return = "Yes";
            }
        }

        $result = [];

        // Iterate through the array remove duplicate items . sum the quantity ,totalamount, taxamount and remove duplication for bill print only...//
        foreach ($data['data'] as $item) {
            $productId = $item->ProductID;

            // If ProductID already exists in the result, sum up the Quantity and UnitePrice
            if (isset($result[$productId])) {
                $result[$productId]->Quantity += $item->Quantity;
                $result[$productId]->totalAmount += $item->totalAmount;
                $result[$productId]->taxAmount += $item->taxAmount;
            } else {
                // Add new ProductID to result
                $result[$productId] = clone $item;
            }
        }
        $result = array_values($result);
        $data['data'] = $result;


        if ($return == "Yes") {
            $data['return'] = "Yes";
        } else {
            $data['return'] = "No";
        }


        $data['TotalAmount'] = $totalAmount;
        $data['show_customer_contact'] = "true";

        $data['customer'] = Customer::where("SCID", $customer_id)->get();

        return view('reports/print_sale_invoice', $data);
    }

    public function previous_bills(){
        $bills = Sale::orderBy("SaleID", "DESC")->with("patient")->limit(50);

        return DataTables::of($bills)
            
            ->addColumn('action', function ($data) {
                return '<a target="_blank" href="' . route("pos.print_thermel_purchase_details", [$data->SaleID]) . '" class="btn btn-sm btn-success ">Print Thermal</a>';
            })
            ->rawColumns(["action"])
            ->make(true);
    }
    
    public function print_purchase($SCID, $GRNID){
        $data["supplier"] = Customer::where("Type", 1)->where("SCID", $SCID)->with("market")->first();
        $data["products"]= Product::where("IsActive", 1)->get();
        $data['purchase'] = GrnDetails::where('GRNID', $GRNID)->with("grn","products")->orderBy("GDID","DESC")->where(["GRNID" => $GRNID])->get();
        $data['id'] = $GRNID;
        
        // return $data;
        return view('reports/print_purchase', $data);
    }

    public function print_purchase_request($SCID, $GRNID){
        $data["supplier"] = Customer::where("Type", 1)->where("SCID", $SCID)->with("market")->first();
        $data["products"]= Product::where("IsActive", 1)->get();
        $data['purchase'] = GrnRequestDetails::where('GRNID', $GRNID)->with("products")->orderBy("GDID","DESC")->where(["GRNID" => $GRNID])->get();


        $data['id'] = $GRNID;

        // return $data;
        return view('reports/print_purchase', $data);
    }
    
    
}
