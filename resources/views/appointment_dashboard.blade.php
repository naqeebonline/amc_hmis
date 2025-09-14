
@extends('layouts.'.config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp
@section('content')
<style>
    .tags { list-style: none; display: flex; justify-content: center; align-items: center; padding: 10px 0; width: 95%; margin: 0 auto; }
    .tags li { padding: 0 20px; position: relative; display: flex; justify-content: center; align-items: center; }
    .tags li.warning:after { background-color: orange; }
    .tags li.success:after { background-color: green; }
    .tags li.danger:after { background-color: red; }
    .tags li:after { content: ''; position: absolute; top: 50%; transform: translateY(-50%); left: 0px; width: 10px; border-radius: 10px; height: 10px; }
    .text_height_map{ line-height: 18px; }
</style>
<div class="container-xxl flex-grow-1 px-0">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-4" style="color: white; font-weight: bold">Appointment Analytics Dashboard</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-3 mb-4">
            <label style="color:white;font-weight: bold">From Date</label>
            <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $from_date ?? $selected_date }}">
        </div>
        <div class="col-lg-3 col-md-3 mb-4">
            <label style="color:white;font-weight: bold">To Date</label>
            <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $to_date ?? $selected_date }}">
        </div>
        <div class="col-lg-3 col-md-3 mb-4">
            <label style="color:white;font-weight: bold">Consultant</label>
            <select class="form-control" id="consultant_id" name="consultant_id">
                <option value="">All Consultants</option>
                @foreach($consultants as $consultant)
                    <option value="{{ $consultant->id }}" {{ $selected_consultant == $consultant->id ? 'selected' : '' }}>{{ $consultant->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-3 mb-4">
            <label style="color:white;font-weight: bold">OPD Type</label>
            <select class="form-control" id="opd_type_id" name="opd_type_id">
                <option value="">All OPD Types</option>
                @foreach($opd_types as $opd)
                    <option value="{{ $opd->id }}" {{ $selected_opd_type == $opd->id ? 'selected' : '' }}>{{ $opd->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-3 mb-4 d-flex align-items-end">
            <a class="btn btn-primary" style="color: white; font-weight: bold" href="javascript:void(0)" id="search_dashboard">Search</a>
        </div>
    </div>
    <div class="row">
        
        <div class="col-lg-12 col-md-12 mb-4">
            <div class="row">
                <div class="col-lg-3 col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar">
                                        <span class="avatar-initial bg-label-success rounded-circle">
                                            <i class="bx bx-calendar fs-4"></i>
                                        </span>
                                    </div>
                                    <div class="card-info">
                                        <h5 class="card-title mb-0 me-2">{{ $total_appointments }}</h5>
                                        <small class="text-muted">Total Appointments</small>
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
                                        <span class="avatar-initial bg-label-info rounded-circle">
                                            <i class="bx bx-calculator fs-4"></i>
                                        </span>
                                    </div>
                                    <div class="card-info">
                                        <h5 class="card-title mb-0 me-2">{{ number_format($total_amount, 2) }}</h5>
                                        <small class="text-muted">Total Amount</small>
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
                                        <span class="avatar-initial bg-label-primary rounded-circle">
                                            <i class="bx bx-building fs-4"></i>
                                        </span>
                                    </div>
                                    <div class="card-info">
                                        <h5 class="card-title mb-0 me-2">{{ number_format($total_hospital_share, 2) }}</h5>
                                        <small class="text-muted">Total Hospital Share</small>
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
                                        <span class="avatar-initial bg-label-warning rounded-circle">
                                            <i class="bx bx-user fs-4"></i>
                                        </span>
                                    </div>
                                    <div class="card-info">
                                        <h5 class="card-title mb-0 me-2">{{ number_format($total_consultant_share, 2) }}</h5>
                                        <small class="text-muted">Total Consultant Share</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
               
                
              
                @foreach($opd_type_counts as $row)
                    <div class="col-lg-3 col-md-3 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-info rounded-circle">
                                                <i class="bx bx-clinic fs-4"></i>
                                            </span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ $row->total }}</h5>
                                            <small class="text-muted">{{ $opd_types->firstWhere('id', $row->opd_type_id)->name ?? $row->opd_type_id }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Appointment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Date:</label>
                                <p class="mb-0">{{ $selected_date }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Consultant Filter:</label>
                                <p class="mb-0">
                                    @if($selected_consultant)
                                        {{ $consultants->firstWhere('id', $selected_consultant)->name ?? 'Unknown Consultant' }}
                                    @else
                                        All Consultants
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">OPD Type Filter:</label>
                                <p class="mb-0">
                                    @if($selected_opd_type)
                                        {{ $opd_types->firstWhere('id', $selected_opd_type)->name ?? 'Unknown OPD Type' }}
                                    @else
                                        All OPD Types
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.getElementById('search_dashboard').addEventListener('click', function() {
        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;
        const consultantId = document.getElementById('consultant_id').value;
        const opdTypeId = document.getElementById('opd_type_id').value;
        let url = `{{ route('appointment.dashboard') }}?from_date=${fromDate}&to_date=${toDate}`;
        if (consultantId) url += `&consultant_id=${consultantId}`;
        if (opdTypeId) url += `&opd_type_id=${opdTypeId}`;
        window.location.href = url;
    });
</script>
@endpush
