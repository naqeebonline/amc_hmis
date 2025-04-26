<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Grn;
use App\Models\GrnAudit;
use App\Models\GrnAuditDetails;
use App\Models\GrnDetails;
use App\Models\GrnRequest;
use App\Models\GrnRequestDetails;
use App\Models\Market;
use App\Models\PaymentDetail;
use App\Models\Product;
use App\Models\ProductConsumption;
use App\Models\Sale;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    public function add_new_stock()
    {

        $data["title"] = "Add New Stock";
        $data['products'] = Product::with(["generic_name"])->orderBy("ProductName", "ASC")->get();
        $data['suppliers'] = Customer::where(["Type" => 1])->orderBy("Name", "ASC")->get();

        return view("warehouse.new_stock", $data);
    }

    public function get_products(Request $request){
        $search = $request->input('q', ''); // Search term
        $page = $request->input('page', 1);
        $limit = 10; // Items per page
        $offset = ($page - 1) * $limit;

        $query = Product::with(["generic_name"]);

        if (!empty($search)) {
            $query->where('ProductName', 'LIKE', "%{$search}%")
                ->where("pack_size","!=",0)->where("pack_price","!=",0)
            ->orWhereHas('generic_name', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%"); 
            });
           
        }

        $items = $query->skip($offset)->take($limit)->get();

        foreach($items as $key => $value){
            $value->ProductName = ($value->ProductName)." | PS:".$value->pack_size;
        }

        return response()->json([
            'items' => $items,
            'more' => $items->count() === $limit, // Check if more data is available
        ]);
    }

    public function ware_house_stock()
    {

        $data["title"] = "Warehouse";
        $data['market'] = Market::where(["IsActive" => 1])->get()->sortBy('Name');
        $data['products'] = Product::orderBy("ProductName", "ASC")->get();
        $totalAmount=0;
        foreach($data['products'] as $getQuantity){
            $newAvaliableQty = GrnDetails::where(["ProductID"=> $getQuantity->ProductID])->sum('RemainingQuantity');
            $getQuantity->AvailableQuantity = $newAvaliableQty;
            $getQuantity->TotalProductAmount=$this->total_product_price($getQuantity->ProductID);
            $totalAmount=($totalAmount)+($getQuantity->TotalProductAmount);
        }
        $data['totalAmount'] = $totalAmount;
        return view("warehouse.warehouse", $data);
    }

    public function get_ware_house_stock()
    {
        $data = Product::orderBy("ProductName", "ASC");
    return DataTables::of($data)
        ->addColumn('action', function ($cert) {
            return '<a href="' . route('pos.product_purchase_details',[$cert->ProductID]) .'" class="btn btn-success ">Purchase Details</a>';
        })
        ->addColumn('AvailableQuantity', function ($cert) {
            $newAvaliableQty = $this->avaliableQuantity($cert->ProductID);
            $cert->AvailableQuantity = $newAvaliableQty;
            return $newAvaliableQty;

        })
        ->addColumn('TotalProductAmount', function ($cert) {
            //$newAvaliableQty = GrnDetails::where(["ProductID"=> $cert->ProductID])->sum('RemainingQuantity');

            $this->total_product_purchase_price($cert->ProductID);
            return $this->total_product_price($cert->ProductID);

        })
        ->rawColumns(["AvailableQuantity","TotalProductAmount", "action"])
        ->make(true);
    }

    public function list_customer()
    {
        $res = Customer::with("market")->where(["IsActive" => 1]);

        return DataTables::of($res)
            ->addColumn('action', function ($cert) {
                $details = json_encode($cert);
                if (in_array(auth()->user()->roles->pluck('name')[0], ["Super Admin", "District Super Admin"])) {
                    $html = '<a href="javascript:void(0)" class="btn btn-warning btn-icon btn-sm edit_record" data-details=\'' . $details . '\'  data-id="' . $cert->SCID . '"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="' . $cert->SCID . '" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                } else {
                    $html = "";
                }

                return $html;
            })
            ->addColumn('customType', function ($cert) {
                $cert->customType;
                if ($cert->Type == 2) {
                    return $cert->customType = "Customer";
                } else {
                    return $cert->customType = "Supplier";
                }
            })
            ->rawColumns(["customType", "action"])
            ->make(true);
    }

    public function save_customer()
    {


        Customer::updateOrCreate(
            ["SCID" => request()->id],
            request()->except(["id", "_token"])
        );
        return ["status" => true, "message" => "Record saved successfully"];
    }

    




    public function save_stock(Request $request)
    {

        /*return response()->json([
            "data" => $request->all()
        ]);*/
        $Invoice = request()->invoice_number ?? "";
        $SupplierID = request()->SID;
        $Freight = 0;
        $PDate = date("Y-m-d",strtotime(request()->bill_date));
        $Desc = request()->BillDiscription;
        $discount = request()->bill_discount;
        $demage = 0;
        $paid_amount = request()->ReceivedAmount;
        $userID = auth()->user()->id;
        $totalTax = 0;
        $TotalPurchase = request()->BillAmount;
        $total_gst = request()->total_gst ?? 0;
        $total_advance_tax = request()->total_advance_tax ?? 0;
        /*foreach(request()->ProductList as $row){
            $totalTax = ($totalTax) + ($row['taxAmount']);
        }*/
        $total = ($TotalPurchase) + $totalTax;
        $bill_json_form = json_encode(request()->ProductList);

        $PurchaseArray = array(
            'SCID'          => $SupplierID,
            'bill_json_form'          => "nill",
            'InvoiceNo'     => $Invoice,
            'Freight'       => 0,
            'Dated'         => $PDate,
            'Description'   => $Desc,
            'TotalPurchase'  => 0,
            'paid_amount'  => 0,
            'Discount'      => $discount,
            'CreatedBy'     => $userID,
            'total_gst'     => 0,
            'total_advance_tax'     => 0,
            'CreatedAt'     => date('Y-m-d'),
            'CreatedBy'     => auth()->user()->id
        );
        //dd($PurchaseArray);
        $grn = GrnRequest::create($PurchaseArray);
        return ["status"=>true,"message"=>"Stock Added Successfully","id"=>$grn->GRNID];

        /*$last_id = $grn->GRNID;
        foreach(request()->ProductList as $row){
            $Detail_array = array(
                'GRNID'  =>    $last_id,
                'ProductID' => $row['ProductID'],
                'Quantity'  => $row['Quantity'] * $row['pack_size'],
                'Damage'  =>   $demage,
                'batch_no'  =>   $row['batch_no'],
                'UnitPrice'  => $row['unit_price'],
                'pack_price'  =>   $row['pack_price'],
                'pack_size'  =>   $row['pack_size'],
                'taxPercentage'  => $row['taxPercentage'],
                'taxAmount'  => $row['taxAmount'],
                'gst_tax_amount'  => $row['gst_tax_amount'],
                'advance_tax_amount'  => $row['advance_tax_amount'],
                'advance_tax'  => $row['advance_tax'],
                'gst_tax'  => $row['gst_tax'],
                'RemainingQuantity'=>($row['Quantity'] * $row['pack_size'])
            );
            GrnRequestDetails::create($Detail_array);
        }*/


        return ["status"=>true,"message"=>"Stock Added Successfully","id"=>$last_id];


    }

    function total_product_price($product_id){
        $result = GrnDetails::where(["ProductID"=>$product_id,"ProductStatus"=>1])->get();
        $totalAmount=0;
        foreach($result as $row=>$value){
            $discountPer = $value->discount / 100;
            $gstTax = $value->gst_tax / 100;
            $advance_tax = $value->advance_tax / 100;
            $gst_taxAmount = (($value->RemainingQuantity)*($value->UnitPrice)) * $gstTax;
            $advance_taxAmount = (($value->RemainingQuantity)*($value->UnitPrice)) * $advance_tax;
            $DiscountAmount = (($value->RemainingQuantity)*($value->UnitPrice)) * $discountPer;
            $totalAmount=($totalAmount)+(($value->RemainingQuantity)*($value->UnitPrice)) + ($gst_taxAmount + $advance_taxAmount) - ($DiscountAmount);
        }
        Product::where(["ProductID"=>$product_id])->update(["total_amount_of_avaliable_stock"=>$totalAmount]);
        return $totalAmount;
    }

    function total_product_purchase_price($product_id){
        $result = GrnDetails::where(["ProductID"=>$product_id])->get();
        $totalAmount=0;
        foreach($result as $row=>$value){
            $discountPer = $value->discount / 100;
            $gstTax = $value->gst_tax / 100;
            $advance_tax = $value->advance_tax / 100;
            $gst_taxAmount = (($value->Quantity)*($value->UnitPrice)) * $gstTax;
            $advance_taxAmount = (($value->Quantity)*($value->UnitPrice)) * $advance_tax;
            $DiscountAmount = (($value->Quantity)*($value->UnitPrice)) * $discountPer;
            $totalAmount=($totalAmount)+(($value->Quantity)*($value->UnitPrice)) + ($gst_taxAmount + $advance_taxAmount) - ($DiscountAmount);
        }
        Product::where(["ProductID"=>$product_id])->update(["total_amount_of_purchase_stock"=>$totalAmount]);
        return $totalAmount;
    }

    public function edit_purchase_bill($id)
    {
        $data['title'] = "Edit Purchase Bill";
        $data['id'] = $id;
        $data['grn'] = Grn::where(["GRNID"=>$id])->first();
        return view('warehouse.edit_purchase_bill',$data);
    }

    public function update_grn(){

        $id = 0;
        foreach(request()->GDID as $key=>$value){
            $gst = (request()->gst_tax[$key])/100 ;
            $gst_tax_amount = (request()->pack_price[$key] * request()->pack_qty[$key]) * $gst;
            $advance_tax = (request()->advance_tax[$key])/100 ;
            $advance_tax_amount = (request()->pack_price[$key] * request()->pack_qty[$key]) * $advance_tax;
            
          
            $data = array(
                'Quantity' => request()->Quantity[$key],
                'pack_price' => request()->pack_price[$key],
                'UnitPrice' => request()->pack_price[$key]/request()->pack_size[$key],
                'advance_tax' => request()->advance_tax[$key],
                'gst_tax' => request()->gst_tax[$key],
                'expiry_date' => request()->expiry_date[$key],
                'gst_tax_amount' => $gst_tax_amount,
                'advance_tax_amount' => $advance_tax_amount,
                'RemainingQuantity' => request()->Quantity[$key],
                'discount' => request()->discount[$key],
            );
            

            $id = $value;
            GrnRequestDetails::where(["GDID"=>$value])->update($data);
        }
        $this->calculateBill(request()->GRNID);
        return redirect()->route('pos.add_bill_items', [
            'id' => request()->GRNID,                // Route parameter
            'edit_id' => $id,       // Query string parameter

        ]);

    }

    public function add_item_to_bill(){
        $data = request()->except(["id","_token"]);
        $itemExists = GrnRequestDetails::where(["GRNID"=>request()->GRNID,"ProductID"=>request()->ProductID])->exists();
        if ($itemExists) {
            // Redirect back with error message
            return redirect()->back()->withErrors(['item_exist' => 'The item already exist in bill. please update quantity.']);
        }


        $data['RemainingQuantity'] = request()->Quantity;


        $gst = (request()->gst_tax) / 100;
        $gst_tax_amount = (request()->UnitPrice * request()->Quantity) * $gst;
        $advance_tax = (request()->advance_tax) / 100;
        $advance_tax_amount = (request()->UnitPrice * request()->Quantity) * $advance_tax;

        $data['gst_tax_amount'] = $gst_tax_amount;
        $data['advance_tax_amount'] = $advance_tax_amount;
        GrnRequestDetails::create($data);
        $this->calculateBill(request()->GRNID);
        return redirect()->route('pos.add_bill_items', [
            'id' => request()->GRNID,                // Route parameter
        ]);
       // return redirect()->back();
    }

    public function delete_item_from_bill($id){
           $g_details = GrnRequestDetails::where(["GDID"=>$id])->first();

           $grn_id = $g_details->GRNID;
           
            GrnRequestDetails::where(["GDID"=>$id])->delete();
            $this->calculateBill($grn_id);
            return redirect()->back();
    }

    public function calculateBill($grnID){
        $totalAmount = 0;
        $totalGst = 0;
        $totalAdvanceTax = 0;
        $per_item_discount = 0;
        $total_per_item_discount = 0;
        $items = GrnRequestDetails::where(["GRNID" => $grnID])->where(["ProductStatus"=>1])->get();


        foreach($items as $key => $value){

        
            $pack_qty = $value->Quantity/$value->pack_size;

            $gst = ($value->gst_tax) / 100;
            $gst_tax_amount = ($value->pack_price * $pack_qty) * $gst;
            //dd($gst_tax_amount);  
            $advance_tax = ($value->advance_tax) / 100;
            $advance_tax_amount = ($value->pack_price * $pack_qty) * $advance_tax;

            $item_dicount = ($value->discount) / 100;
            $per_item_discount = ($value->pack_price * $pack_qty) * $item_dicount;


            $qty = $pack_qty;
            $total_amount = $qty * $value->pack_price;
            $totalAmount += $total_amount;
            $totalAdvanceTax += $advance_tax_amount;
            $totalGst += $gst_tax_amount;
            $total_per_item_discount += $per_item_discount;
        }
        //dd(["ModifiedBy"=>auth()->user()->id,"TotalPurchase" => ($totalAmount + $totalAdvanceTax + $totalGst), "total_gst" => $totalGst, "total_advance_tax" => $totalAdvanceTax]);
        GrnRequest::where(["GRNID" => $grnID])->update(["ModifiedBy"=>auth()->user()->id,"per_item_discount"=>$total_per_item_discount,"TotalPurchase" => ($totalAmount + $totalAdvanceTax + $totalGst), "total_gst" => $totalGst, "total_advance_tax" => $totalAdvanceTax]);
        
        return true;

    }

    public function grn_request()
    {

        $data["title"] = "Add Supplier Bill";
        return view("warehouse.grn_request", $data);
    }

    public function list_grn_request()
    {

        $data =  GrnRequest::with("supplier")->orderBy("GRNID","DESC");
        //  <a class="btn btn-sm btn-primary" href="'.route('pos.edit_purchase_bill',[$data->GRNID]).'">Edit</a>
        return DataTables::of($data)
            ->addColumn('action', function ($data) {

                $buttons = '';
                if($data->bill_status == 0){

                    $buttons= '<a  target="_blank" class="btn btn-sm btn-success" href="'.route('pos.add_bill_items',[$data->GRNID]).'">Edit</a>';
                    /*$buttons = $buttons.'<a class="btn btn-sm btn-danger">Delete</a>';*/
                    $buttons = $buttons.'<a class="btn btn-sm btn-primary approve_bill" bill_id="'.$data->GRNID.'">Approve Bill</a>';

                }
                $buttons = $buttons.'<a target="_blank" class="btn btn-sm btn-success" href="'.route('pos.print_purchase_request',[$data->SCID, $data->GRNID]).'">Print Bill</a>';
                return $buttons;
            })
            ->addColumn('bill_c_status', function ($data) {

                return ($data->bill_status == 0) ? "Pending" : "Approved";
            })
            ->addColumn('final_bill', function ($data) {

                return (($data->TotalPurchase) - ($data->Discount + $data->per_item_discount));
            })
            ->rawColumns(['bill_c_status','action']) // Allow raw HTML for action buttons
            ->make(true);
    }

    public function approve_grn_bill()
    {

        $grn = GrnRequest::where(["GRNID"=>request()->id])->first()->toArray();

         unset($grn['GRNID']);
         unset($grn['bill_json_form']);
         unset($grn['bill_status']);
         $grn['grn_request_id'] = request()->id;
        $grn = Grn::create($grn);
        $grn_id = $grn->GRNID;
        $grn_request_details = GrnRequestDetails::where(["GRNID"=>request()->id])->get()->toArray();
        foreach ($grn_request_details as &$item) {
            $item['GRNID'] = $grn_id;
            unset($item['GDID']);
        }
        foreach ($grn_request_details as $key => $value){
            GrnDetails::create($value);
        }

        GrnRequest::where(["GRNID"=>request()->id])->update(["bill_status"=>1]);
        return response()->json(["status"=>true,"message"=>"done"]);
    }

    public function product_purchase_details($productID)
    {
        $res = DB::table("grn_details")->where("grn_details.ProductID",$productID)
            ->leftJoin("grn","grn.GRNID","=","grn_details.GRNID")
            ->leftJoin("sup_cus_details","sup_cus_details.SCID","=","grn.SCID")
            ->orderBy("grn.Dated","DESC")
            ->limit(100)
            ->get([
                "grn.InvoiceNo","grn.Dated","sup_cus_details.Name as SupplierName","grn_details.ProductID","grn_details.Quantity","grn_details.RemainingQuantity"
            ]);
        $data['data'] =$res;

        return view("warehouse.product_purchase_details", $data);


    }

    public function print_pharmacy_audit_form()
    {
        /*$datas = Product::orderBy("ProductName", "ASC")->where("IsActive",1)->get();
        foreach ($datas as $key => $cert){
            $cert->newAvaliableQty = GrnDetails::where(["ProductID"=> $cert->ProductID])->sum('RemainingQuantity');
            $this->total_product_purchase_price($cert->ProductID);
            $this->total_product_price($cert->ProductID);
            Product::where(["ProductID"=>$cert->ProductID])->update(["avaliable_quantity"=>$cert->newAvaliableQty]);
        }*/

        $data['data'] = Product::with(["generic_name"])->orderBy('avaliable_quantity',"desc")->where("IsActive",1)->get();
        return view("PatientReports.print_audit_form", $data);


    }



    public function pharmacy_audit()
    {
        $data['is_active_audit'] = GrnAudit::where(["status"=>0])->exists();

        $data['users'] = Users::whereHas('roles', function ($query) {
            $query->where('name', 'Pharmacy User')->orWhere('name',"Super Admin");
        })->get();

        return view("warehouse.pharmacy_audit", $data);
    }

    public function list_audit()
    {
        $res = GrnAudit::with("users")->orderBy("id","desc");

        return DataTables::of($res)
            ->addColumn('actions', function ($cert) {
                $details = json_encode($cert);
                $html = '';
                if (in_array(auth()->user()->roles->pluck('name')[0], ["Super Admin", "District Super Admin"]) && $cert->status == 0) {
                    $html .= ' <a  href="'.route('pos.start_pharmacy_audit',[$cert->id]).'" class="btn btn-primary " data-id="' . $cert->id . '">Add Items</a>';
                    $html .= ' <a href="'.route('pos.approve_close_audit',[$cert->id]).'?type=calculate_stock" class="btn btn-success " data-id="' . $cert->id . '">Approve</a>';
                    /*$html .= ' <button class="btn btn-danger btn-icon btn-sm delete_record" data-id="' . $cert->id . '" type="submit"><i class="bx bx-trash tf-icons"></i></button>';*/

                } else {
                    if($cert->status == 0){
                        $html .= ' <a href="'.route('pos.start_pharmacy_audit',[$cert->id]).'" class="btn btn-primary " data-id="' . $cert->id . '">Add Item</a>';
                    }else{
                        $html .= ' <a href="'.route('pos.start_pharmacy_audit',[$cert->id]).'" class="btn btn-warning " data-id="' . $cert->id . '">View</a>';
                    }

                }



                return $html;
            })

            ->rawColumns(["customType", "actions"])
            ->make(true);
    }

    public function store_audit()
    {
        $data = request()->except(["id","_token"]);
        if(request()->id == 0){
            $data['audit_no'] = $this->generateAuditNumber();
        }

        //GrnAudit::where(["status"=>0])->update(["status"=>1]);
        GrnAudit::updateOrCreate(
            ["id" => request()->id],
            $data
        );
        return ["status" => true, "message" => "Record saved successfully"];
    }

    public function start_pharmacy_audit($id)
    {
        /*if(isset($_GET['type'])){

            $datas = Product::orderBy("ProductName", "ASC")->where("IsActive",1)->get();
            foreach ($datas as $key => $cert){
                $cert->newAvaliableQty = GrnDetails::where(["ProductID"=> $cert->ProductID])->sum('RemainingQuantity');
                $this->total_product_purchase_price($cert->ProductID);
                $this->total_product_price($cert->ProductID);
                Product::where(["ProductID"=>$cert->ProductID])->update(["avaliable_quantity"=>$cert->newAvaliableQty]);
            }
        }*/
        $data['audit_id'] = $id;
        $data['audit'] = GrnAudit::where("id",$id)->first();
        $data['items'] = GrnAuditDetails::with(['product'])->where(["audit_id"=>$id])
            ->orderBy("id","desc")
            ->get();
        $product_ids = $data['items']->pluck("product_id");


        $data['data'] = Product::with(["generic_name"])->orderBy('avaliable_quantity',"desc")
            ->when((count($product_ids) > 0),function ($q) use($product_ids){
                $q->whereNotIn('ProductID',$product_ids);
            })
            ->where("IsActive",1)->get();


        return view("warehouse.start_pharmacy_audit", $data);
    }

    public function update_audit()
    {


        $id = request()->id;
        $product_id = request()->product_id;

        $phy_avaliable_quantity = request()->phy_avaliable_quantity;
        $avaliable_quantity = $this->avaliableQuantity($product_id);
        $diffrence = ($phy_avaliable_quantity) - ($avaliable_quantity);

        GrnAuditDetails::where(["id"=>$id])->update(["phy_avaliable_quantity"=>$phy_avaliable_quantity,"avaliable_quantity"=>$avaliable_quantity,"diffrence"=>$diffrence]);
        return redirect()->back();

    }



    public function add_product_to_audit()
    {
        $data = request()->except(["id","_token"]);
        $data['avaliable_quantity'] = $this->avaliableQuantity(request()->product_id);
        $data['diffrence'] = (request()->phy_avaliable_quantity) - ($data['avaliable_quantity']);
        $exist = GrnAuditDetails::where(["audit_id"=>request()->audit_id,"product_id"=>request()->product_id])->exists();
        if($exist){
            return redirect()->back();
        }else{
            GrnAuditDetails::create($data);
            return redirect()->back();
        }

    }

    public function approve_close_audit($id)
    {
        $data['items'] = GrnAuditDetails::with(['product'])->where(["audit_id"=>$id])
            ->orderBy("id","desc")
            ->get();
       foreach ($data['items'] as $key => $value){

          // $avaliable_quantity = $this->avaliableQuantity($value->product_id);
           $avaliable_quantity = $value->avaliable_quantity;
           $phy_avaliable_quantity = $value->phy_avaliable_quantity;
           $diffrence = $value->diffrence;
           if($diffrence == 0){
               continue;
           }

           if($diffrence < 0){
               $soldQuantity= ($diffrence) * (-1);
               $result = GrnDetails::where(["ProductID"=>$value->product_id,"ProductStatus"=>1])->get();
               foreach($result as $key=>$value) {
                   if ($soldQuantity <= $value->RemainingQuantity && $soldQuantity != 0) {
                       $remainingQuantity = $value->RemainingQuantity - $soldQuantity;
                       GrnDetails::where(["GDID" => $value->GDID])->update(['RemainingQuantity' => $remainingQuantity, 'SoldQuantity' => ($value->SoldQuantity + $soldQuantity)]);
                       if ($remainingQuantity == 0) {
                           GrnDetails::where(['GDID' => $value->GDID])->update(['ProductStatus' => 0]);
                       }
                       $soldQuantity = 0;
                   } else {
                       if ($soldQuantity > $value->RemainingQuantity && $soldQuantity != 0) {
                           $soldQuantity = ($soldQuantity) - ($value->RemainingQuantity);
                           GrnDetails::where(['GDID' => $value->GDID])->update(['RemainingQuantity' => 0, 'SoldQuantity' => ($value->SoldQuantity + $value->RemainingQuantity), 'ProductStatus' => 0]);
                       }
                   }
               }

               $data = [
                   "product_id" => $value->product_id,
                   "quantity" => ($diffrence) * (-1),
                   "audit_id" => $value->audit_id,
                   "created_by" => auth()->user()->id,
                   "created_at" => date("Y-m-d H:i:s"),
               ];
               ProductConsumption::create($data);
           }else{
               $product = Product::where(["ProductID"=>$value->product_id])->first();
               $data = [
                   "ProductID" => $value->product_id,
                   "GRNID"     => 0,
                   "batch_no"   =>"audit_adjustment|".$value->audit_id,
                   "Quantity"   => $diffrence,
                   "UnitPrice"   => 0,
                   "discount"   => 0,
                   "pack_price"   => $product->pack_price,
                   "pack_size"   => $product->pack_size,
                   "taxPercentage"   => 0,
                   "RemainingQuantity"   => $diffrence,
                   "ProductStatus"   => 1,

               ];

               GrnDetails::create($data);
           }
       }

       GrnAudit::where("id",$id)->update(["status"=>1,"approve_by"=>auth()->user()->id,"aprove_date"=>date("Y-m-d H:i:s")]);
       return redirect()->route('pos.pharmacy_audit');
    }

    public function delete_product_from_audit($id)
    {
        GrnAuditDetails::where("id",$id)->delete();
        return redirect()->back();
    }




    function generateAuditNumber() {
        $prefix = "Aud-";
        $appointment = DB::table('grn_audit')->orderBy("id","desc")->first();
        $number = $appointment ? $appointment->id : 0;
        $number = ($number + 1);
        // Calculate the required length (based on the number of digits)
        $currentLength = strlen((string) $number); // Length of the current number
        $paddingLength = max(4, $currentLength + 1); // Start with 4 digits, increase dynamically

        // Pad the number to the calculated length
        $paddedNumber = str_pad($number, $paddingLength, '0', STR_PAD_LEFT);

        // Combine prefix and padded number
        return $prefix . $paddedNumber;
    }

    public function avaliableQuantity($productID)
    {
        $qty = GrnDetails::where(["ProductID"=> $productID])->sum('RemainingQuantity');
        $consumed = ProductConsumption::where(["product_id"=> $productID])->sum('quantity');
        $qty = ($qty) - ($consumed);
        Product::where(["ProductID"=>$productID])->update(["avaliable_quantity"=>$qty]);
        return $qty;
    }
}
