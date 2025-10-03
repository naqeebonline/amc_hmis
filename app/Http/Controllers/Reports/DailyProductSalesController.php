<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\TempSale;
use App\Models\TempSaleDetails;
use App\Models\SaleDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyProductSalesController extends Controller
{
    public function index(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');

        $data = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'page_title' => 'Daily Product-wise Sales Dashboard'
        ];

        return view('reports.daily_product_sales', $data);
    }

    public function getProductSalesData(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');

        // Get product-wise sales data from temp_sale_details
        $productSales = TempSaleDetails::select([
            'products.ProductID',
            'products.ProductName',
            DB::raw('SUM(temp_sale_details.Quantity - COALESCE(temp_sale_details.ReturnQuantity, 0)) as total_quantity_sold'),
            DB::raw('SUM(temp_sale_details.ReturnQuantity) as total_returned'),
            DB::raw('SUM(temp_sale_details.Quantity) as gross_quantity'),
            DB::raw('AVG(temp_sale_details.UnitePrice) as avg_sale_price'),
            DB::raw('SUM(temp_sale_details.UnitePrice * (temp_sale_details.Quantity - COALESCE(temp_sale_details.ReturnQuantity, 0))) as total_revenue'),
            DB::raw('COUNT(DISTINCT temp_sale_details.SaleID) as total_transactions'),
            DB::raw('MIN(temp_sale.Date) as first_sale_date'),
            DB::raw('MAX(temp_sale.Date) as last_sale_date')
        ])
            ->join('products', 'temp_sale_details.ProductID', '=', 'products.ProductID')
            ->join('temp_sale', 'temp_sale_details.SaleID', '=', 'temp_sale.SaleID')
            ->whereDate('temp_sale.Date', '>=', $from_date)
            ->whereDate('temp_sale.Date', '<=', $to_date)
            ->groupBy('products.ProductID', 'products.ProductName')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productSales,
            'summary' => [
                'total_products' => $productSales->count(),
                'total_revenue' => $productSales->sum('total_revenue'),
                'total_quantity_sold' => $productSales->sum('total_quantity_sold'),
                'total_transactions' => $productSales->sum('total_transactions')
            ]
        ]);
    }

    public function getProductSalesWithPurchasePrice(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');

        // Get product-wise sales data with purchase price and discounts from sale_details
        $productSalesWithCost = SaleDetails::select([
            'products.ProductID',
            'products.ProductName',
            DB::raw('SUM(sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0)) as total_quantity_sold'),
            DB::raw('SUM(sale_details.ReturnQuantity) as total_returned'),
            DB::raw('SUM(sale_details.Quantity) as gross_quantity'),
            DB::raw('SUM(sale_details.UnitePrice * sale_details.Quantity) as total_sale_amount'),
            DB::raw('SUM(sale_details.PurchasePrice * sale_details.Quantity) as total_purchase_amount'),
            DB::raw('SUM(sale_details.UnitePrice * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as total_revenue'),
            DB::raw('SUM(sale_details.PurchasePrice * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as total_cost'),
            DB::raw('SUM((sale_details.Quantity * sale_details.UnitePrice * COALESCE(sale.discount_percentage, 0) / 100)) as total_discount'),
            DB::raw('SUM((sale_details.UnitePrice - sale_details.PurchasePrice) * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as gross_profit'),
            DB::raw('COUNT(DISTINCT sale_details.SaleID) as total_transactions'),
            DB::raw('MIN(sale.Date) as first_sale_date'),
            DB::raw('MAX(sale.Date) as last_sale_date')
        ])
            ->join('products', 'sale_details.ProductID', '=', 'products.ProductID')
            ->join('sale', 'sale_details.SaleID', '=', 'sale.SaleID')
            ->whereDate('sale.Date', '>=', $from_date)
            ->whereDate('sale.Date', '<=', $to_date)
            ->groupBy('products.ProductID', 'products.ProductName')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Calculate net profit after discounts and profit margins
        $productSalesWithCost->transform(function ($item) {
            // Calculate net profit after deducting discounts
            $item->total_profit = $item->gross_profit - $item->total_discount;

            // Calculate profit margin based on net profit
            $item->profit_margin = $item->total_revenue > 0 ?
                round(($item->total_profit / $item->total_revenue) * 100, 2) : 0;

            // Calculate average prices per unit
            $item->avg_sale_price = $item->total_quantity_sold > 0 ?
                round($item->total_sale_amount / ($item->total_quantity_sold + $item->total_returned), 2) : 0;
            $item->avg_purchase_price = $item->total_quantity_sold > 0 ?
                round($item->total_purchase_amount / ($item->total_quantity_sold + $item->total_returned), 2) : 0;

            $item->avg_profit_per_unit = $item->total_quantity_sold > 0 ?
                round($item->total_profit / $item->total_quantity_sold, 2) : 0;

            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $productSalesWithCost,
            'summary' => [
                'total_products' => $productSalesWithCost->count(),
                'total_sale_amount' => $productSalesWithCost->sum('total_sale_amount'),
                'total_purchase_amount' => $productSalesWithCost->sum('total_purchase_amount'),
                'total_revenue' => $productSalesWithCost->sum('total_revenue'),
                'total_cost' => $productSalesWithCost->sum('total_cost'),
                'total_discount' => $productSalesWithCost->sum('total_discount'),
                'gross_profit' => $productSalesWithCost->sum('gross_profit'),
                'total_profit' => $productSalesWithCost->sum('total_profit'),
                'total_quantity_sold' => $productSalesWithCost->sum('total_quantity_sold'),
                'total_transactions' => $productSalesWithCost->sum('total_transactions'),
                'avg_profit_margin' => $productSalesWithCost->avg('profit_margin')
            ]
        ]);
    }

    public function getDailySalesChart(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');

        $dailySales = SaleDetails::select([
            DB::raw('DATE(sale.Date) as sale_date'),
            DB::raw('SUM(sale_details.UnitePrice * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as daily_revenue'),
            DB::raw('SUM(sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0)) as daily_quantity'),
            DB::raw('SUM((sale_details.Quantity * sale_details.UnitePrice * COALESCE(sale.discount_percentage, 0) / 100)) as daily_discount'),
            DB::raw('COUNT(DISTINCT sale_details.SaleID) as daily_transactions')
        ])
            ->join('sale', 'sale_details.SaleID', '=', 'sale.SaleID')
            ->whereDate('sale.Date', '>=', $from_date)
            ->whereDate('sale.Date', '<=', $to_date)
            ->groupBy(DB::raw('DATE(sale.Date)'))
            ->orderBy('sale_date')
            ->get();

        return response()->json([
            'success' => true,
            'labels' => $dailySales->pluck('sale_date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            }),
            'revenue' => $dailySales->pluck('daily_revenue'),
            'quantity' => $dailySales->pluck('daily_quantity'),
            'discount' => $dailySales->pluck('daily_discount'),
            'transactions' => $dailySales->pluck('daily_transactions')
        ]);
    }

    public function getTopSellingProducts(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');
        $limit = $request->limit ?? 10;

        $topProducts = SaleDetails::select([
            'products.ProductName',
            DB::raw('SUM(sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0)) as total_sold'),
            DB::raw('SUM(sale_details.UnitePrice * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as total_revenue'),
            DB::raw('SUM((sale_details.Quantity * sale_details.UnitePrice * COALESCE(sale.discount_percentage, 0) / 100)) as total_discount')
        ])
            ->join('products', 'sale_details.ProductID', '=', 'products.ProductID')
            ->join('sale', 'sale_details.SaleID', '=', 'sale.SaleID')
            ->whereDate('sale.Date', '>=', $from_date)
            ->whereDate('sale.Date', '<=', $to_date)
            ->groupBy('products.ProductID', 'products.ProductName')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'products' => $topProducts->pluck('ProductName'),
            'revenue' => $topProducts->pluck('total_revenue'),
            'quantity' => $topProducts->pluck('total_sold')
        ]);
    }

    public function getSalesStatistics(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');

        // Get statistics from sale_details with discount information
        $stats = DB::select("
            SELECT 
                COUNT(DISTINCT sd.ProductID) as unique_products,
                SUM(sd.Quantity - COALESCE(sd.ReturnQuantity, 0)) as total_quantity_sold,
                SUM(sd.ReturnQuantity) as total_returned,
                SUM(sd.UnitePrice * (sd.Quantity - COALESCE(sd.ReturnQuantity, 0))) as total_revenue,
                SUM((sd.Quantity * sd.UnitePrice * COALESCE(s.discount_percentage, 0) / 100)) as total_discount,
                COUNT(DISTINCT sd.SaleID) as total_transactions,
                AVG(sd.UnitePrice) as avg_selling_price
            FROM sale_details sd
            JOIN sale s ON sd.SaleID = s.SaleID
            WHERE DATE(s.Date) >= ? AND DATE(s.Date) <= ?
        ", [$from_date, $to_date]);

        $statistics = $stats[0] ?? null;

        if ($statistics) {
            $statistics->avg_transaction_value = $statistics->total_transactions > 0 ?
                round($statistics->total_revenue / $statistics->total_transactions, 2) : 0;
            $statistics->return_rate = $statistics->total_quantity_sold > 0 ?
                round(($statistics->total_returned / ($statistics->total_quantity_sold + $statistics->total_returned)) * 100, 2) : 0;
        }

        return response()->json([
            'success' => true,
            'statistics' => $statistics
        ]);
    }

    public function exportPrintableReport(Request $request)
    {
        $from_date = $request->from_date ?? date('Y-m-d');
        $to_date = $request->to_date ?? date('Y-m-d');

        // Get comprehensive product sales data with discounts
        $productSales = SaleDetails::select([
            'products.ProductID',
            'products.ProductName',
            DB::raw('SUM(sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0)) as net_quantity'),
            DB::raw('SUM(sale_details.ReturnQuantity) as returned_quantity'),
            DB::raw('SUM(sale_details.UnitePrice * sale_details.Quantity) as total_sale_amount'),
            DB::raw('SUM(sale_details.PurchasePrice * sale_details.Quantity) as total_purchase_amount'),
            DB::raw('SUM(sale_details.UnitePrice * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as total_revenue'),
            DB::raw('SUM(sale_details.PurchasePrice * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as total_cost'),
            DB::raw('SUM((sale_details.Quantity * sale_details.UnitePrice * COALESCE(sale.discount_percentage, 0) / 100)) as total_discount'),
            DB::raw('SUM((sale_details.UnitePrice - sale_details.PurchasePrice) * (sale_details.Quantity - COALESCE(sale_details.ReturnQuantity, 0))) as gross_profit'),
            DB::raw('COUNT(DISTINCT sale_details.SaleID) as total_transactions')
        ])
            ->join('products', 'sale_details.ProductID', '=', 'products.ProductID')
            ->join('sale', 'sale_details.SaleID', '=', 'sale.SaleID')
            ->whereDate('sale.Date', '>=', $from_date)
            ->whereDate('sale.Date', '<=', $to_date)
            ->groupBy('products.ProductID', 'products.ProductName')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Calculate net profit after discounts
        $productSales->transform(function ($item) {
            $item->total_profit = $item->gross_profit - $item->total_discount;
            $item->profit_margin = $item->total_revenue > 0 ?
                round(($item->total_profit / $item->total_revenue) * 100, 2) : 0;
            return $item;
        });

        $data = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'products' => $productSales,
            'total_revenue' => $productSales->sum('total_revenue'),
            'total_cost' => $productSales->sum('total_cost'),
            'total_discount' => $productSales->sum('total_discount'),
            'gross_profit' => $productSales->sum('gross_profit'),
            'total_profit' => $productSales->sum('total_profit'),
            'total_products' => $productSales->count(),
            'report_generated_at' => now()->format('Y-m-d H:i:s')
        ];

        return view('reports.print_daily_product_sales', $data);
    }
}
