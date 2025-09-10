@extends('layouts.'.config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@push('stylesheets')
    <style>
        .table > :not(caption) > * > * {padding: 5px;}
        .report-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
    </style>

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Reports Dashboard -->
            <div class="card">
                <div class="card-header header-elements-inline">
                    <div class="text-muted">
                        <i class="fas fa-chart-bar me-2"></i>Reports Dashboard
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card report-card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-truck fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="card-title">GRN Supplier Purchase Report</h5>
                                    <p class="card-text">
                                        Complete purchase report from specific supplier showing all products, quantities, and total amounts purchased.
                                    </p>
                                    <a href="{{ route('reports.grn_supplier_report') }}" class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>View Report
                                    </a>
                                </div>
                                <div class="card-footer text-muted text-center">
                                    <small><i class="fas fa-info-circle me-1"></i>Currently showing data for Supplier ID: 1</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card report-card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-boxes fa-3x text-success"></i>
                                    </div>
                                    <h5 class="card-title">Inventory Summary</h5>
                                    <p class="card-text">
                                        Detailed inventory summary report showing current stock levels, quantities, and valuation for all products.
                                    </p>
                                    <a href="{{ route('reports.inventory_summary') }}" class="btn btn-success">
                                        <i class="fas fa-eye me-2"></i>View Report
                                    </a>
                                </div>
                                <div class="card-footer text-muted text-center">
                                    <small><i class="fas fa-info-circle me-1"></i>Real-time inventory data from GRN records</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card report-card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-chart-line fa-3x text-warning"></i>
                                    </div>
                                    <h5 class="card-title">Purchase Analytics</h5>
                                    <p class="card-text">
                                        Coming Soon - Advanced purchase analytics with trends and comparative analysis.
                                    </p>
                                    <button class="btn btn-outline-warning" disabled>
                                        <i class="fas fa-clock me-2"></i>Coming Soon
                                    </button>
                                </div>
                                <div class="card-footer text-muted text-center">
                                    <small><i class="fas fa-wrench me-1"></i>Under Development</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /reports dashboard -->
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-responsive/datatables.responsive.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.form.min.js') }}"></script>
    <script>
        // Dashboard scripts can be added here if needed
        $(document).ready(function() {
            // Initialize any dashboard-specific functionality
            console.log('Reports Dashboard loaded successfully');
        });
    </script>
@endpush
