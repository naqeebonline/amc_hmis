<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GenericName;
use App\Models\GrnDetails;
use App\Models\ItemForm;
use App\Models\ItemMake;
use App\Models\MainCategory;
use App\Models\Product;
use App\Models\ProductKit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function add_new_product()
    {
        $data["title"] = "Add New Product";
        $data["main_category"] = MainCategory::whereIsActive(1)->get();
        $data["item_form"] = ItemForm::whereIsActive(1)->get();
        $data["make"] = ItemMake::whereIsActive(1)->get();
        $data["generic_name"] = GenericName::whereIsActive(1)->get();
        return view("configuration.product",$data);
    }

    public function list_product()
    {
        $res = Product::with(["main_categroy","sub_categroy","item_form","item_make","generic_name"])->where(["IsActive"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                $url = route("pos.product_kit",[$cert->ProductID]);
                    $html = '<a href="' . $url .'" class="btn btn-success btn-icon btn-sm"><i class="bx bx-box"></i></a>
                    <a href="javascript:void(0)" class="btn btn-warning btn-icon btn-sm edit_record" data-details=\''.$details.'\'  data-id="'.$cert->ProductID.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->ProductID.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }

                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }



    public function save_product()
    {

        //dd(request()->all());
        Product::updateOrCreate(
            ["ProductID"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function getProduct()
    {
        $product = Product::when(request()->p_id, function ($query) {
            return $query->where('ProductID', request()->p_id);
        })->get();
        return ["status"=>true,"data"=> $product];
    }

   /* function get_items_by_product_id(){
        $p_id=request()->p_id;
        $resultSet = Product::where(["ProductID"=>$p_id])->get();
        if($resultSet){
            $totalAmount=0;
            foreach($resultSet as $value){
                $newAvaliableQty = GrnDetails::where(["ProductID"=> $value->ProductID])->sum('RemainingQuantity');
                $value->AvailableQuantity = $newAvaliableQty;
            }
        }
        return ["status" => true,"message"=>"record found","data"=>$resultSet];
    }*/

    function get_items_by_product_id(){
        $p_id=request()->p_id;
        $resultSet = Product::where(["ProductID"=>$p_id])->get();
        $is_product_kit = ProductKit::with(["product"])->where(["product_main_id"=>$p_id,"is_active"=>1])->get(["product_id","product_id as ProductID","qty"]);

        $is_kit = count($is_product_kit);


        //dd();
        if(count($is_product_kit)){
            $is_kit = 1;
        }

        if($resultSet){
            $totalAmount=0;
            if($is_kit){
                $resultSet = $is_product_kit;
            }

            foreach($resultSet as $value){
                //$newAvaliableQty = GrnDetails::where(["ProductID"=> $value->ProductID])->sum('RemainingQuantity');
                $newAvaliableQty = (new StockController())->avaliableQuantity($value->ProductID);



                if($is_kit){
                    $value->AvailableQuantity = $newAvaliableQty;
                    if($newAvaliableQty == 0){
                      //  $value->AvailableQuantity = $value->qty;
                    }
                    $value->is_product_kit = 1;
                }else{
                    $value->AvailableQuantity = $newAvaliableQty;
                    $value->is_product_kit = 0;
                }

            }
        }
        return ["status" => true,"message"=>"record found","data"=>$resultSet];
    }

    function get_items_by_barcode(){
        $barcode=request()->barcode;
        $resultSet = Product::where(["BarCode"=>$barcode])->get();
        if($resultSet){
            $totalAmount=0;
            foreach($resultSet as $value){
                //$newAvaliableQty = GrnDetails::where(["ProductID"=> $value->ProductID])->sum('RemainingQuantity');
                $newAvaliableQty = (new StockController())->avaliableQuantity($value->ProductID);
                $value->AvailableQuantity = $newAvaliableQty;
            }
            return ["status" => true,"message"=>"record found","data"=>$resultSet];
        }else{
            return ["status" => false,"message"=>"record not found","data"=>[]];
        }

    }



}
