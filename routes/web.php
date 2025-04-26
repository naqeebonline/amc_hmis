<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/testData', function () {
    $liveData = \Illuminate\Support\Facades\DB::connection('live_mysql')->table('company_levels')->get();
    dd($liveData);
});

Route::get("/", [\App\Http\Controllers\HomeController::class, 'index'])->name('app.landing-screen');
Route::get("dashboardAnalytics", [\App\Http\Controllers\HomeController::class, 'dashboardAnalytics'])->name('app.dashboardAnalytics');



Route::get('/testQue', [App\Http\Controllers\HomeController::class, 'testQue']);
Route::get('/telenorGatway', [\App\Http\Controllers\API\SmsController::class, 'telenorGatway']);

\Illuminate\Support\Facades\Auth::routes();

Route::post('custom-authenticate', [\App\Http\Controllers\Auth\LoginController::class, 'customAuthenticate'])->name('custom-authenticate');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::prefix('no-auth')->group(function () {

    Route::post('districts-by-province-id', [\App\Http\Controllers\NoAuthActionsControllers::class, 'districtsByProvinceId'])->name('noauth.districts-by-province-id');

    Route::post('districts-by-division-id', [\App\Http\Controllers\NoAuthActionsControllers::class, 'districtsByDivisionId'])->name('noauth.districts-by-division-id');

    Route::post('tehsils-by-district-id', [\App\Http\Controllers\NoAuthActionsControllers::class, 'tehsilsByDistrictId'])->name('noauth.tehsils-by-district-id');

    Route::post('sections-by-company-id', [\App\Http\Controllers\NoAuthActionsControllers::class, 'sectionsByCompanyId'])->name('noauth.sections-by-company-id');

    Route::post('users-hod-id-check', [\App\Http\Controllers\NoAuthActionsControllers::class, 'usersHodIdCheck'])->name('noauth.users-hod-id-check');

    Route::post('users-hod-check', [\App\Http\Controllers\NoAuthActionsControllers::class, 'usersHodCheck'])->name('noauth.users-hod-check');

    Route::post('users-list-by-company-id', [\App\Http\Controllers\NoAuthActionsControllers::class, 'usersListByCompanyId'])->name('noauth.users-list-by-company-id');

    Route::post('companies-by-type-id', [\App\Http\Controllers\NoAuthActionsControllers::class, 'companiesByTypeId'])->name('noauth.companies-by-type-id');

    Route::get('companies-list', [\App\Http\Controllers\NoAuthActionsControllers::class, 'companiesList'])->name('noauth.companies-list');
});

Route::middleware(['auth'])->prefix('dropDown')->group(function () {
    Route::get('/', [\App\Http\Controllers\DynamicController::class, 'dropDown'])->name('dynamic.dropDown');
});

Route::get('/psw-generate', function () {
    return \Illuminate\Support\Facades\Hash::make('12345678');
});

/*Route::post('create-meeting', function () {


})->name("create-meeting");*/

Route::group(['middleware' => 'auth'], function () {
    Route::get('pharmacy_dashboard', [\App\Http\Controllers\HomeController::class, 'pharmacy_dashboard'])->name('user.pharmacy_dashboard');
    Route::get('user-dashboard', [\App\Http\Controllers\HomeController::class, 'userDashboard'])->name('user.dashboard');
    Route::get('view-history', [\App\Http\Controllers\Admin\SmsHistoryController::class, 'index'])->name('view-history');
    Route::get('list-sms', [\App\Http\Controllers\Admin\SmsHistoryController::class, 'listSms'])->name('list-sms');
    Route::get('add_new_market', [\App\Http\Controllers\Admin\MarketController::class, 'add_new_market'])->name('pos.add_new_market');
    Route::get('list_market', [\App\Http\Controllers\Admin\MarketController::class, 'list_market'])->name('pos.list_market');
    Route::post('save-market', [\App\Http\Controllers\Admin\MarketController::class, 'save_market'])->name('pos.save_market');
    Route::post('delete-table-data', [\App\Http\Controllers\Admin\MarketController::class, 'delete_table_data'])->name('pos.delete-table-data');
    Route::post('delete_table_data_2', [\App\Http\Controllers\Admin\MarketController::class, 'delete_table_data_2'])->name('pos.delete_table_data_2');

    Route::get('add_new_product', [\App\Http\Controllers\Admin\ProductController::class, 'add_new_product'])->name('pos.add_new_product');
    Route::get('list_product', [\App\Http\Controllers\Admin\ProductController::class, 'list_product'])->name('pos.list_product');
    Route::post('save-product', [\App\Http\Controllers\Admin\ProductController::class, 'save_product'])->name('pos.save_product');
    Route::post('getProduct', [\App\Http\Controllers\Admin\ProductController::class, 'getProduct'])->name('pos.getProduct');
    Route::post('get_items_by_product_id', [\App\Http\Controllers\Admin\ProductController::class, 'get_items_by_product_id'])->name('pos.get_items_by_product_id');
    Route::post('get_items_by_barcode', [\App\Http\Controllers\Admin\ProductController::class, 'get_items_by_barcode'])->name('pos.get_items_by_barcode');

    Route::get('customers', [\App\Http\Controllers\Admin\CustomerController::class, 'add_new_customer'])->name('pos.add_new_customer');
    Route::get('list_customer', [\App\Http\Controllers\Admin\CustomerController::class, 'list_customer'])->name('pos.list_customer');
    Route::post('save-customer', [\App\Http\Controllers\Admin\CustomerController::class, 'save_customer'])->name('pos.save_customer');

    Route::get('add-stock', [\App\Http\Controllers\Admin\StockController::class, 'add_new_stock'])->name('pos.add_new_add_new_stock');
    Route::get('ware-house-stock', [\App\Http\Controllers\Admin\StockController::class, 'ware_house_stock'])->name('pos.ware_house_stock');
    Route::get('get-ware-house-stock', [\App\Http\Controllers\Admin\StockController::class, 'get_ware_house_stock'])->name('pos.get_ware_house_stock');
    Route::get('product_purchase_details/{id}', [\App\Http\Controllers\Admin\StockController::class, 'product_purchase_details'])->name('pos.product_purchase_details');
    Route::post("save-stock", [\App\Http\Controllers\Admin\StockController::class, 'save_stock'])->name('pos.save_stock');
    Route::get("edit-purchase-bill/{id?}", [\App\Http\Controllers\Admin\StockController::class, 'edit_purchase_bill'])->name('pos.edit_purchase_bill');


    Route::get('supplier-payments', [\App\Http\Controllers\Admin\SupplierPayments::class, 'supplier_payments'])->name('pos.supplier_payments');

    Route::post('save-payments', [\App\Http\Controllers\Admin\SupplierPayments::class, 'save_payments'])->name('pos.save_payments');
    Route::get('get-payments/{id?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'get_payments'])->name('pos.get_payments');
    Route::get('purchase-details/{id?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'purchase_details'])->name('pos.purchase_details');
    Route::get('get_purchase_bill_items/{id?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'get_purchase_bill_items'])->name('pos.get_purchase_bill_items');
    Route::get('add-bill-items/{id?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'add_bill_items'])->name('pos.add_bill_items');
    Route::get('print_purchase/{SCID?}/{GRNID?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'print_purchase'])->name('pos.print_purchase');
    Route::get('print_purchase_request/{SCID?}/{GRNID?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'print_purchase_request'])->name('pos.print_purchase_request');
    Route::get('print_thermel_purchase_details/{id?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'print_thermel_purchase_details'])->name('pos.print_thermel_purchase_details');
    Route::get('previous_bills', [\App\Http\Controllers\Admin\SupplierPayments::class, 'previous_bills'])->name('pos.previous_bills');
    

    Route::get('add-sale', [\App\Http\Controllers\Admin\SaleController::class, 'add_new_sale'])->name('pos.add_new_sale');
    Route::post('save_sale', [\App\Http\Controllers\Admin\SaleController::class, 'save_sale'])->name('pos.save_sale');
    Route::post('temp_save_sale', [\App\Http\Controllers\Admin\SaleController::class, 'temp_save_sale'])->name('pos.temp_save_sale');
    Route::get('print_temp_sale/{sale_id?}/{customer_id?}/{date?}/{received_amount?}', [\App\Http\Controllers\Admin\SaleController::class, 'print_temp_sale'])->name('pos.print_temp_sale');
    Route::get('print_purchase_detail/{sale_id?}/{date?}', [\App\Http\Controllers\Admin\SaleController::class, 'print_purchase_detail'])->name('pos.print_purchase_detail');
    Route::get('customer-payments', [\App\Http\Controllers\Admin\CustomerPayments::class, 'customer_payments'])->name('pos.customer_payments');
    
    Route::post('save-customer-payments', [\App\Http\Controllers\Admin\CustomerPayments::class, 'save_customer_payments'])->name('pos.save_customer_payments');
    
    Route::get('receiveable/{id?}', [\App\Http\Controllers\Admin\CustomerPayments::class, 'receiveables'])->name('pos.receiveables');
    Route::get('sale-details/{id?}', [\App\Http\Controllers\Admin\CustomerPayments::class, 'sale_details'])->name('pos.sale_details');
    Route::get('customer_previous_balance/{id?}/{date?}', [\App\Http\Controllers\Admin\CustomerPayments::class, 'customer_previous_balance'])->name('pos.customer_previous_balance');
    Route::get('supplier_previous_balance/{id?}/{date?}', [\App\Http\Controllers\Admin\SupplierPayments::class, 'supplier_previous_balance'])->name('pos.supplier_previous_balance');


    Route::get("expenses", [\App\Http\Controllers\Admin\ExpensesController::class, 'add_new_expenses'])->name('pos.add_new_expenses');
    Route::get("expenses-list", [\App\Http\Controllers\Admin\ExpensesController::class, 'expenses_list'])->name('pos.expenses_list');
    Route::get("sub_expenses_list", [\App\Http\Controllers\Admin\ExpensesController::class, 'sub_expenses_list'])->name('pos.sub_expenses_list');
    Route::post("save-expanses", [\App\Http\Controllers\Admin\ExpensesController::class, 'save_expanses'])->name('pos.save_expanses');
    Route::get("sub-expanses", [\App\Http\Controllers\Admin\ExpensesController::class, 'sub_expanses'])->name('pos.sub_expanses');
    Route::post("save-sub-expanses", [\App\Http\Controllers\Admin\ExpensesController::class, 'save_sub_expanses'])->name('pos.save_sub_expanses');


    Route::get("general-expanses", [\App\Http\Controllers\Admin\ExpensesController::class, 'general_expanses'])->name('pos.general_expanses');
    Route::get("general-expanses-list", [\App\Http\Controllers\Admin\ExpensesController::class, 'general_expanses_list'])->name('pos.general_expanses_list');
    Route::post("get_sub_expanses", [\App\Http\Controllers\Admin\ExpensesController::class, 'get_sub_expanses'])->name('pos.get_sub_expanses');
    Route::post("save-general-expanses", [\App\Http\Controllers\Admin\ExpensesController::class, 'save_general_expanses'])->name('pos.save_general_expanses');

    Route::get("get-daily-sale", [\App\Http\Controllers\HomeController::class, 'getDailySaleData'])->name('pos.getDailySaleData');

    Route::post('deactivate_record', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'deactivate_record'])->name('pos.deactivate_record');

    Route::get('main-category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'add_main_category'])->name('pos.add_main_category');
    Route::get('list_main_category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'list_main_category'])->name('pos.list_main_category');
    Route::post('save_main_category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'save_main_category'])->name('pos.save_main_category');

    Route::get('item-sub-category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'add_sub_category'])->name('pos.add_sub_category');
    Route::get('list_sub_category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'list_sub_category'])->name('pos.list_sub_category');
    Route::post('save_sub_category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'save_sub_category'])->name('pos.save_sub_category');
    Route::post('get_sub_category', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'get_sub_category'])->name('pos.get_sub_category');

    Route::get('item-form', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'add_item_form'])->name('pos.add_item_form');
    Route::get('list_item_form', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'list_item_form'])->name('pos.list_item_form');
    Route::post('save_item_form', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'save_item_form'])->name('pos.save_item_form');

    Route::get('item-make', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'add_make'])->name('pos.add_make');
    Route::get('list_make', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'list_make'])->name('pos.list_make');
    Route::post('save_make', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'save_make'])->name('pos.save_make');

    Route::get('item-generic-name', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'add_generic_name'])->name('pos.add_generic_name');
    Route::get('list_generic_name', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'list_generic_name'])->name('pos.list_generic_name');
    Route::post('save_generic_name', [\App\Http\Controllers\ProductConfiguration\CategoryController::class, 'save_generic_name'])->name('pos.save_generic_name');

    Route::get('list-wards', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'add_ward'])->name('pos.add_ward');
    Route::get('list_ward', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'list_ward'])->name('pos.list_ward');
    Route::post('save_ward', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'save_ward'])->name('pos.save_ward');

    Route::get('list-beds', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'add_bed'])->name('pos.add_bed');
    Route::get('list_bed', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'list_bed'])->name('pos.list_bed');
    Route::post('save_bed', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'save_bed'])->name('pos.save_bed');

    Route::get('list-service-type', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'add_service_type'])->name('pos.add_service_type');
    Route::get('list_service_type', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'list_service_type'])->name('pos.list_service_type');
    Route::post('save_service_type', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'save_service_type'])->name('pos.save_service_type');


    Route::get("patient-registration", [\App\Http\Controllers\PatientController\PatientController::class, "patient_regisration"])->name("pos.patient_regisration");
    Route::post("get_patient_by_cnic", [\App\Http\Controllers\PatientController\PatientController::class, "get_patient_by_cnic"])->name("pos.get_patient_by_cnic");
    Route::post("store-patient-registration", [\App\Http\Controllers\PatientController\PatientController::class, "store_patient_regisration"])->name("pos.store_patient_regisration");
    Route::get("list-patient", [\App\Http\Controllers\PatientController\PatientController::class, "list_patient"])->name("pos.list_patient");



    Route::get("patient-admission", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_admission"])->name("pos.patient_admission");
    Route::get("list-admission", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "list_admission"])->name("pos.list_admission");
    Route::post("ward-bed", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "ward_bed"])->name("pos.ward_bed");
    Route::post("store-patient-admission", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "store_patient_admission"])->name("pos.store_patient_admission");
    Route::post("admission-cancelation", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "admission_cancelation"])->name("pos.admission_cancelation");
    Route::get("print_patient_admission/{admission_id}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "print_patient_admission"])->name("pos.print_patient_admission");
    Route::get("discharged_patient", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "discharged_patient"])->name("pos.discharged_patient");
    Route::get("discharged_patient_list", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "discharged_patient_list"])->name("pos.discharged_patient_list");
    Route::post("cencel_patient_admission", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "cencel_patient_admission"])->name("pos.cencel_patient_admission");

    
    Route::get("patient-account/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_account"])->name("pos.patient_account");
    Route::get("get_admission_investigations/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_admission_investigations"])->name("pos.get_admission_investigations");
    Route::post("save_patient_investigation", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_patient_investigation"])->name("pos.save_patient_investigation");

    Route::get("get_admission_service_charges/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_admission_service_charges"])->name("pos.get_admission_service_charges");
    Route::post("save_patient_service_charges", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_patient_service_charges"])->name("pos.save_patient_service_charges");
    Route::get("get_admitted_patient_treatments/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_admitted_patient_treatments"])->name("pos.get_admitted_patient_treatments");

    Route::get('add_procedure_type', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'add_procedure_type'])->name('pos.add_procedure_type');
    Route::get('list_procedure_type', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'list_procedure_type'])->name('pos.list_procedure_type');
    Route::post('save_procedure_type', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'save_procedure_type'])->name('pos.save_procedure_type');

    Route::get('add_investigation_category', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'add_investigation_category'])->name('pos.add_investigation_category');
    Route::get('list_investigation_category', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'list_investigation_category'])->name('pos.list_investigation_category');
    Route::post('save_investigation_category', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'save_investigation_category'])->name('pos.save_investigation_category');

    Route::get('investigation_sub_category', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'add_investigation_sub_category'])->name('pos.add_investigation_sub_category');
    Route::get('list_investigation_sub_category', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'list_investigation_sub_category'])->name('pos.list_investigation_sub_category');
    Route::post('save_investigation_sub_category', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'save_investigation_sub_category'])->name('pos.save_investigation_sub_category');

    Route::get('investigation_parameter/{id?}', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'add_investigation_parameter'])->name('pos.add_investigation_parameter');
    Route::get('list_investigation_parameter/{id?}', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'list_investigation_parameter'])->name('pos.list_investigation_parameter');
    Route::post('save_investigation_parameter', [\App\Http\Controllers\GeneralConfigration\InvestigationController::class, 'save_investigation_parameter'])->name('pos.save_investigation_parameter');

    Route::get('add_consultant', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'add_consultant'])->name('pos.add_consultant');
    Route::get('list_consultant', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'list_consultant'])->name('pos.list_consultant');
    Route::post('save_consultant', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'save_consultant'])->name('pos.save_consultant');

    Route::get('add_consultant_department', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'add_consultant_department'])->name('pos.add_consultant_department');
    Route::get('list_consultant_department', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'list_consultant_department'])->name('pos.list_consultant_department');
    Route::post('save_consultant_department', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'save_consultant_department'])->name('pos.save_consultant_department');

    Route::get('add_consultant_speciality', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'add_consultant_speciality'])->name('pos.add_consultant_speciality');
    Route::get('list_consultant_speciality', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'list_consultant_speciality'])->name('pos.list_consultant_speciality');
    Route::post('save_consultant_speciality', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'save_consultant_speciality'])->name('pos.save_consultant_speciality');

    Route::get('add_consultant_type', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'add_consultant_type'])->name('pos.add_consultant_type');
    Route::get('list_consultant_type', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'list_consultant_type'])->name('pos.list_consultant_type');
    Route::post('save_consultant_type', [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, 'save_consultant_type'])->name('pos.save_consultant_type');



    Route::get("discharge-patient", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "discharge_patient"])->name("pos.discharge_patient");
    Route::get("list-admited-patients", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "list_admitted_patients"])->name("pos.list_admitted_patients");
    Route::post("save_discharge_checklist", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_discharge_checklist"])->name("pos.save_discharge_checklist");
    Route::get("discharge-patient-from-ward/{patient_admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "discharge_patient_from_ward"])->name("pos.discharge_patient_from_ward");

    Route::get("investigation_result",  [\App\Http\Controllers\Investigation\InvestigationResult::class, 'investigation_result'] )->name("post.investigation_result");
    Route::get("investigation_list",  [\App\Http\Controllers\Investigation\InvestigationResult::class, 'investigation_result_list'] )->name("pos.investigation_result_list");
    Route::get("investigation_completed_list",  [\App\Http\Controllers\Investigation\InvestigationResult::class, 'investigation_completed_list'] )->name("pos.investigation_completed_list");
    Route::get("investigation_add_result/{inv_id?}/{inv_cat_id?}",  [\App\Http\Controllers\Investigation\InvestigationResult::class, 'investigation_add_result'] )->name("pos.investigation_add_result");
    Route::post("store_inv_result",  [\App\Http\Controllers\Investigation\InvestigationResult::class, 'store_inv_result'] )->name("pos.store_inv_result");
    Route::get("print_inv_result/{inv_id}",  [\App\Http\Controllers\Investigation\InvestigationResult::class, 'print_inv_result'] )->name("pos.print_inv_result");

    Route::get("patient_summary", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_summary"])->name("pos.patient_summary");

    Route::get("patient_investigation", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_investigation"])->name("pos.patient_investigation");
    Route::get("patient_service_charges", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_service_charges"])->name("pos.patient_service_charges");
    
    Route::get("patient_ot_notes", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "ot_notes"])->name("pos.patient_ot_notes");
    Route::post("save_patient_ot_notes", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_patient_ot_notes"])->name("pos.save_patient_ot_notes");
    Route::get("get_patient_ot_notes/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_patient_ot_notes"])->name("pos.get_patient_ot_notes");


    Route::get("patient_nurse_notes", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_nurse_notes"])->name("pos.patient_nurse_notes");
    Route::post("save_patient_nurse_notes", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_patient_nurse_notes"])->name("pos.save_patient_nurse_notes");
    Route::get("get_patient_nurse_notes/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_patient_nurse_notes"])->name("pos.get_patient_nurse_notes");


    Route::get("patient_refunds", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_refunds"])->name("pos.patient_refunds");
    Route::post("save_patient_refunds", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_patient_refunds"])->name("pos.save_patient_refunds");
    Route::get("get_patient_refunds/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_patient_refunds"])->name("pos.get_patient_refunds");
    Route::get("view_patient_summary/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "view_patient_summary"])->name("pos.view_patient_summary");


    Route::get("print_lab_invoice/{invoice_no?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "print_lab_invoice"])->name("pos.print_lab_invoice");
    Route::post("get-ward-investigations/{ward_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "get_ward_investigations"])->name("pos.get_ward_investigations");


    Route::get("machine_patient", [\App\Http\Controllers\PatientController\MachinePatientController::class, "machine_patient"] )->name("pos.machine_patient");
    Route::get("machine_patient_list", [\App\Http\Controllers\PatientController\MachinePatientController::class, "machine_patient_list"] )->name("pos.machine_patient_list");
    Route::post("get_machine_category", [\App\Http\Controllers\PatientController\MachinePatientController::class, "get_machine_category"] )->name("post.get_machine_category");
    Route::post("store_machine_patient", [\App\Http\Controllers\PatientController\MachinePatientController::class, "store_machine_patient"] )->name("pos.store_machine_patient");
    

    Route::get("return_product", [\App\Http\Controllers\ProductConfiguration\ReturnProductController::class, "return_product"])->name("pos.return_product");



    Route::get("product_kit/{id?}", [\App\Http\Controllers\ProductConfiguration\ProductKitController::class, "product_kit"])->name("pos.product_kit");
    Route::post("product_kit_save", [\App\Http\Controllers\ProductConfiguration\ProductKitController::class, "product_kit_save"])->name("pos.save_product_kit");
    Route::get("product_kit_list/{product_main_id?}", [\App\Http\Controllers\ProductConfiguration\ProductKitController::class, "product_kit_list"])->name("pos.product_kit_list");
    
    Route::get("patient_vitals", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_vitals"])->name("pos.patient_vitals");
    Route::get("patient_vitals_list/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_vitals_list"])->name("pos.patient_vitals_list");
    Route::post("save_patient_vitals", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_patient_vitals"])->name("pos.save_patient_vitals");


    Route::get('get_products', [\App\Http\Controllers\Admin\StockController::class, 'get_products'])->name('pos.get_products');

    Route::post('update_grn', [\App\Http\Controllers\Admin\StockController::class, 'update_grn'])->name('pos.update_grn');
    Route::post('add_item_to_bill', [\App\Http\Controllers\Admin\StockController::class, 'add_item_to_bill'])->name('pos.add_item_to_bill');

    Route::get('delete_item_from_bill/{id?}', [\App\Http\Controllers\Admin\StockController::class, 'delete_item_from_bill'])->name('pos.delete_item_from_bill');

    Route::get('grn_request', [\App\Http\Controllers\Admin\StockController::class, 'grn_request'])->name('pos.grn_request');
    Route::get('list_grn_request/{id?}', [\App\Http\Controllers\Admin\StockController::class, 'list_grn_request'])->name('pos.list_grn_request');
    Route::post('approve_grn_bill', [\App\Http\Controllers\Admin\StockController::class, 'approve_grn_bill'])->name('pos.approve_grn_bill');
    Route::post('return_item', [\App\Http\Controllers\Admin\SaleController::class, 'return_item'])->name('pos.return_item');

    Route::get("print_admitted_patient_treatment_report/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "print_admitted_patient_treatment_report"])->name("pos.print_admitted_patient_treatment_report");
    Route::get("patient_treatment_chart_report/{patient_id?}/{admission_id?}/{medicine_type_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_treatment_chart_report"])->name("pos.patient_treatment_chart_report");
    Route::post("update_patient_admission", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "update_patient_admission"])->name("pos.update_patient_admission");
    Route::get("canceled_patients", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "canceled_patients"])->name("pos.canceled_patients");
    Route::get("canceled_patient_list", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "canceled_patient_list"])->name("pos.canceled_patient_list");
    Route::get("calculatePatientMedecineAmount/{admission_id?}", [\App\Http\Controllers\PatientController\PatientExpenseController::class, "calculatePatientMedecineAmount"])->name("pos.calculatePatientMedecineAmount");
    Route::get("totalCases/{from_date?}/{to_date?}/{procedure_type_id?}/{consultant_id?}", [\App\Http\Controllers\PatientController\PatientExpenseController::class, "totalCases"])->name("pos.totalCases");
    Route::get("totalCasesD/{from_date?}/{to_date?}/{procedure_type_id?}", [\App\Http\Controllers\PatientController\PatientExpenseController::class, "totalCasesD"])->name("pos.totalCasesD");
    Route::get("birth-certificate/{baby_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "birth_certificate"])->name("pos.birth_certificate");

    Route::get("patient_baby/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "patient_baby"])->name("pos.patient_baby");
    Route::get("list_patient_baby/{patient_id?}/{admission_id?}", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "list_patient_baby"])->name("pos.list_patient_baby");
    Route::post("save_baby", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "save_baby"])->name("pos.save_baby");
    Route::get("getMachinePatientReport", [\App\Http\Controllers\PatientController\PatientAdmissionController::class, "getMachinePatientReport"])->name("pos.getMachinePatientReport");

    Route::get("list_all_patients", [\App\Http\Controllers\PatientReports\PatientReportController::class, "list_all_patients"])->name("pos.list_all_patients");
    Route::get("patient-admission-report", [\App\Http\Controllers\PatientReports\PatientReportController::class, "patient_admission_report"])->name("pos.patient_admission_report");
    Route::get("print_patient_admission_report/{from_date?}/{to_date?}/{consultant_id?}/{procedure_type_id?}", [\App\Http\Controllers\PatientReports\PatientReportController::class, "print_patient_admission_report"])->name("pos.print_patient_admission_report");



    Route::get("print_pharmacy_audit_form", [\App\Http\Controllers\Admin\StockController::class, 'print_pharmacy_audit_form'])->name('pos.print_pharmacy_audit_form');
    Route::post("update_audit", [\App\Http\Controllers\Admin\StockController::class, 'update_audit'])->name('pos.update_audit');
    Route::post("store_audit", [\App\Http\Controllers\Admin\StockController::class, 'store_audit'])->name('pos.store_audit');
    Route::get("list_audit", [\App\Http\Controllers\Admin\StockController::class, 'list_audit'])->name('pos.list_audit');
    Route::get("start_pharmacy_audit/{audit_id?}", [\App\Http\Controllers\Admin\StockController::class, 'start_pharmacy_audit'])->name('pos.start_pharmacy_audit');
    Route::post("add_product_to_audit", [\App\Http\Controllers\Admin\StockController::class, 'add_product_to_audit'])->name('pos.add_product_to_audit');
    Route::get("delete_product_from_audit/{id?}", [\App\Http\Controllers\Admin\StockController::class, 'delete_product_from_audit'])->name('pos.delete_product_from_audit');
    Route::get("approve_close_audit/{id?}", [\App\Http\Controllers\Admin\StockController::class, 'approve_close_audit'])->name('pos.approve_close_audit');


    Route::get('add-locations', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'add_locations'])->name('pos.add_locations');
    Route::get('list_locations', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'list_locations'])->name('pos.list_locations');
    Route::post('save_locations', [\App\Http\Controllers\GeneralConfigration\GeneralConfigController::class, 'save_locations'])->name('pos.save_locations');


    Route::get("generate-doctor-invoice", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class, "index"])->name("pos.generate-doctor-invoice");
    Route::get("load_discharge_patients_for_doctor_payments", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class, "load_discharge_patients_for_doctor_payments"])->name("pos.load_discharge_patients_for_doctor_payments");
    Route::get("generateDoctorPayments/{from_date?}/{to_date?}/{consultant_id?}", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class, "generatePayment"])->name("pos.generatePayment");

    Route::get("doctor-sehat-card-payments", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class, "doctor_sehat_card_payments"])->name("pos.doctor-sehat-card-payments");


    Route::get("doctor-sc-invoices/{id?}", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class, "doctor_sc_invoices"])->name("pos.doctor_sc_invoices");
    Route::get("consultant_sc_balance/{id?}", [\App\Http\Controllers\GeneralConfigration\ConsultantController::class, "consultant_sc_balance"])->name("pos.consultant_sc_balance");
    Route::post("save_sc_payments/{id?}", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class,  "save_sc_payments"])->name("pos.save_sc_payments");
    Route::get("get_sc_payments_to_doctors/{id?}", [\App\Http\Controllers\Accounts\DoctorPaymentsController::class,  "get_sc_payments_to_doctors"])->name("pos.get_sc_payments_to_doctors");

});