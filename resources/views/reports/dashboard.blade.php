@extends('layouts.'.config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@push('stylesheets')
    <style>
        .table > :not(caption) > * > * {padding: 5px;}
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
                        <div class="col-lg-3 col-md-3 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-truck fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">GRN Report</h5>
                                                <small class="text-muted"><a target="_blank" href="{{ route('reports.grn_supplier_report') }}">Supplier Purchase</a></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-success rounded-circle"><i class="bx bx-box fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">Inventory</h5>
                                                <small class="text-muted"><a target="_blank" href="{{ route('reports.inventory_summary') }}">Summary Report</a></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        

                        <div class="col-lg-3 col-md-3 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-info rounded-circle"><i class="bx bx-line-chart fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">Sales Trends</h5>
                                                <small class="text-muted"><a target="_blank" href="{{ route('reports.analytics') }}">View Dashboard</a></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-3 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar">
                                                <span class="avatar-initial bg-label-info rounded-circle"><i class="bx bx-line-chart fs-4"></i></span>
                                            </div>
                                            <div class="card-info">
                                                <h5 class="card-title mb-0 me-2">Sales Statistics</h5>
                                                <small class="text-muted"><a target="_blank" href="{{ route('sale.dashboard') }}">View Dashboard</a></small>
                                            </div>
                                        </div>
                                    </div>
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
