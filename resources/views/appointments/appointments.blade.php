@extends('layouts.' . config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@push('stylesheets')
<style>
    .table> :not(caption)>*>* {
        padding: 5px;
    }

    .select2-container--default .select2-selection--single {
        min-height: 28px !important;
        height: 28px !important;
        padding: 0 6px !important;
        font-size: 0.8rem !important;
        border-radius: 0.375rem !important;
        display: flex !important;
        align-items: center !important;
        border: 1px solid #d1d5db !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        padding-left: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 28px !important;
    }

    /* Professional Compact Form Styling */
    .appointment-form-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        background: #ffffff;
    }

    .form-header {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px 8px 0 0;
        margin: -1px -1px 0 -1px;
    }

    .form-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
    }

    .form-header i {
        margin-right: 6px;
        font-size: 0.9rem;
    }

    .compact-section {
        background: #f8fafc;
        border-left: 3px solid #3b82f6;
        padding: 10px 12px;
        margin-bottom: 12px;
        border-radius: 0 6px 6px 0;
    }

    .section-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }

    .section-title i {
        margin-right: 5px;
        color: #3b82f6;
        font-size: 0.75rem;
    }

    .form-control-sm,
    .form-select-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.8rem;
        border-radius: 0.375rem;
        border: 1px solid #d1d5db;
        height: 28px;
    }

    .form-control-sm:focus,
    .form-select-sm:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 0.125rem rgba(59, 130, 246, 0.25);
    }

    .form-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }

    .asterisk {
        color: #ef4444;
        font-weight: bold;
    }

    .btn-save {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        border-radius: 6px;
        padding: 6px 20px;
        font-weight: 600;
        font-size: 0.8rem;
        color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .btn-save:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 6px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 0.7rem;
        z-index: 3;
    }

    .input-icon .form-control-sm {
        padding-left: 22px;
    }

    .age-group {
        background: #fef3f2;
        border: 1px solid #fecaca;
        border-radius: 6px;
        padding: 1px;
    }

    .age-group label {
        font-size: 0.7rem;
        color: #dc2626;
        font-weight: 600;
        margin: 0 4px;
    }

    .age-group .form-control-sm {
        width: 45px !important;
        height: 24px;
        padding: 2px 4px;
        font-size: 0.75rem;
        text-align: center;
    }

    .row.g-2>* {
        padding-right: calc(var(--bs-gutter-x) * 0.25);
        padding-left: calc(var(--bs-gutter-x) * 0.25);
    }
</style>

<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@section('content')
<div class="row">
    <div class="col-12">

        <!-- Patient Appointment Form -->
        <div class="card appointment-form-card">
            <div class="form-header">
                <h5 style="color: white;"><i class="fas fa-calendar-plus"></i>Patient Appointment Form</h5>
            </div>
            <div class="card-body p-3">
                <form class="form-submit-event" id="patient_register">
                    <!-- Hidden Fields -->
                    <input type="hidden" required id="id" name="id" value="0" class="form-control form-control-sm id_class" />
                    <input type="hidden" id="cnic" name="cnic" class="form-control form-control-sm" placeholder="" value="0" autocomplete="off" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="13" title="Only digits are allowed" />
                    <input type="hidden" id="father_husband_name" name="father_husband_name" class="form-control form-control-sm" placeholder="" autocomplete="off">
                    <input type="hidden" name="district_id" id="district_id" class="form-control form-control-sm" value="22">
                    <input type="date" style="pointer-events: none; display: none;" required id="regdate" value="{{ date('Y-m-d') }}" name="regdate" class="form-control form-control-sm" autocomplete="off">
                    <input type="date" required id="dob" name="dob" class="form-control form-control-sm" style="display: none;" autocomplete="off">

                    <!-- Search & OPD Details -->
                    <div class="compact-section">
                        <div class="section-title"><i class="fas fa-search"></i>Patient Search & OPD Details</div>
                        <div class="row g-2">
                            <div class="col-md-3 mb-2">
                                <label for="mr_number" class="form-label">MR No</label>
                                <div class="input-icon">
                                    <i class="fas fa-id-card"></i>
                                    <input type="text" id="mr_number" value="" class="form-control form-control-sm" placeholder="Enter MR No" autocomplete="off">
                                </div>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label for="opd_type_id" class="form-label">OPD Type<span class="asterisk">*</span></label>
                                <select name="opd_type_id" required id="opd_type_id" class="form-select form-select-sm select2">
                                    <option value="">Select OPD Type</option>
                                    @foreach ($opd_type as $value)
                                    <option value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="consultant_id" class="form-label">Consultant<span class="asterisk">*</span></label>
                                <select name="consultant_id" required id="consultant_id" class="form-select form-select-sm select2">
                                    <option value="">Select Consultant</option>
                                    @foreach ($consultants as $value)
                                    <option value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="location_id" class="form-label">Location<span class="asterisk">*</span></label>
                                <select name="location_id" required id="location_id" class="form-select form-select-sm">
                                    <option value="">Select Location</option>
                                    @foreach ($locations as $location)
                                    @if($location->id == "53")
                                    <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
                                    @else
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label for="contact_no" class="form-label">Contact#<span class="asterisk">*</span></label>
                                <div class="input-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" required id="contact_no" oninput="if(this.value.length > 11) this.value = this.value.slice(0, 11);" name="contact_no" class="form-control form-control-sm" placeholder="03xxxxxxxxx" autocomplete="off">
                                </div>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label for="name" class="form-label">Patient Name<span class="asterisk">*</span></label>
                                <input type="text" required id="name" name="name" class="form-control form-control-sm" placeholder="Enter patient name" autocomplete="off">
                            </div>

                            <div class="col-md-4 mb-2">
                                <label class="form-label text-center w-100" style="color: #dc2626; font-weight: 600; font-size: 0.75rem;">Patient Age</label>
                                <div class="age-group d-flex align-items-center justify-content-center">
                                    <label>Years:</label>
                                    <input type="text" id="age" name="age" required class="form-control form-control-sm" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="3" title="Only digits are allowed" />
                                    <label>Months:</label>
                                    <input type="text" id="months" value="0" name="months" class="form-control form-control-sm" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="2" title="Only digits are allowed" />
                                    <label>Days:</label>
                                    <input type="text" id="days" value="0" name="days" class="form-control form-control-sm" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="2" title="Only digits are allowed" />
                                </div>
                            </div>

                            <div class="col-md-2 mb-2">
                                <label for="gender" class="form-label">Gender<span class="asterisk">*</span></label>
                                <select name="gender" id="gender" required class="form-select form-select-sm">
                                    <option selected value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                     

                    <!-- Submit Button -->
                    <div class="text-center mt-3">
                        <button class="btn btn-save" id="submit_btn" type="submit">
                            <i class="fas fa-save me-1"></i>Save Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>


        {{-- LISTIN PATIENTS --}}
        <div class="card ">
            <div class="card-body">
                <h5 class="card-title">List Appointments</h5>

                <div class="row">
                    <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" class="form-control form-control-sm" value="{{date("Y-m-d")}}" id="filter_from_date">
                    </div>
                    <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" class="form-control form-control-sm" value="{{date("Y-m-d")}}" id="filter_to_date">
                    </div>

                    <div class="col-md-2">
                        <label>OPD Type</label>
                        <select class="form-select form-select-sm" id="filter_opd_type_id">
                            <option value="">View--All</option>
                            @foreach($opd_type as $key => $value)
                            <option value="{{$value->id}}">{{$value->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Consultant</label>
                        <select class="form-select form-select-sm" id="filter_consultant_id">
                            <option value="">View--All</option>
                            @foreach($consultants as $key => $value)
                            <option value="{{$value->id}}">{{$value->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Users</label>
                        <select class="form-select form-select-sm" id="created_by">
                            <option value="">View--All</option>
                            @foreach($users as $key => $value)
                            <option value="{{$value->id}}">{{$value->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mt-4">
                        <div class="btn btn-primary btn-sm mb-2 print_all_details"><i class="bx bx-printer tf-icons"></i></div>
                    </div>
                </div>

                <div class="table-responsive" style="min-height: 200px">

                    <table id="patient-list" class="table table-responsive table-striped  table-condensed">
                        <thead>
                            <tr>
                                <th width="15%">S.N</th>
                                <th width="15%">Name</th>
                                <th width="15%">Opd Type</th>
                                <th width="15%">Opd Fees</th>
                                <th width="15%">Consultant</th>
                                <th width="15%">Appointment Date</th>
                                <th>Created By</th>
                                <th style="width: 10%">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        <!-- /traffic sources -->
    </div>
</div>



<div class="modal fade" id="add_new_record_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content form-submit-event" id="from_submit">
            <input type="hidden" class="id_class" id="id" name="id" value="0">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel1">Patients</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <table class="table table-bordered">
                        <tr>
                            <th>Serial</th>
                            <th>Name</th>
                            <th>Father Name</th>
                            <th></th>
                        </tr>
                        <tbody id="prev_patients">

                        </tbody>
                    </table>

                </div>




            </div>

        </form>
    </div>
</div>


<div class="modal fade my_modal" id="patient_admission_edit_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content form-submit-event" id="cancel_admission_form">
            <input type="hidden" id="cancel_admission_id" name="id" value="0">

            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel1">Update Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">

                    - <div class="col-md-12 mb-3">
                        <label for="nameBasic" class="form-label">Opd Type<span
                                class="asterisk">*</span></label>
                        <input id="edit_id" value="" type="hidden">
                        <select id="edit_opd_type_id" class="form-select form-select-sm">
                            <option value="">Select Opd Type</option>
                            @foreach ($opd_type as $value)
                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="nameBasic" class="form-label">Consultant<span
                                class="asterisk">*</span></label>

                        <select id="edit_consultant_id" class="form-select form-select-sm">
                            <option value="">Select Consultant...</option>
                            @foreach ($consultants as $value)
                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>




            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Close </button>
                <div id="update_record" class="btn btn-primary">Update</div>
            </div>
        </form>
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
    registered_patients = [];
    setTimeout(function() {
        $(".select2").select2();
        // $("#district_id").select2();
        $("#location_id").select2();

        $("#edit_consultant_id").select2({
            dropdownParent: $('.my_modal')
        });
        $("#edit_opd_type_id").select2({
            dropdownParent: $('.my_modal')
        });
    }, 1000);



    $("body").on("click", ".print_all_details", function(e) {
        var from_date = $("#filter_from_date").val();
        var to_date = $("#filter_to_date").val();
        var filter_opd_type_id = $("#filter_opd_type_id").val();
        var filter_consultant_id = $("#filter_consultant_id").val();
        var created_by = $("#created_by").val();
        if (from_date == '') {
            from_date = 'nill';
        }
        if (to_date == '') {
            to_date = 'nill';
        }
        if (filter_opd_type_id == '') {
            filter_opd_type_id = 0;
        }
        if (filter_consultant_id == '') {
            filter_consultant_id = 0;
        }
        if (created_by == '') {
            created_by = 0;
        }
        var url = "{{route('pos.print_all_appointments')}}/" + from_date + "/" + to_date + "/" + filter_opd_type_id + "/" + filter_consultant_id + "/" + created_by;;
        var newWindow = window.open(url, '_blank', 'width=1200,height=800');
        newWindow.focus();

    });

    function calculateDOB(years, months, days) {
        // Get the current date
        const currentDate = new Date();

        // Create a new date object for the calculation
        const dob = new Date(currentDate);

        // Subtract years, months, and days
        dob.setFullYear(dob.getFullYear() - years); // Subtract years
        dob.setMonth(dob.getMonth() - months); // Subtract months
        dob.setDate(dob.getDate() - days); // Subtract days


        const date = new Date(dob);

        // Format the date as d-m-Y
        const day = String(date.getDate()).padStart(2, '0'); // Get day and add leading zero
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Get month (0-indexed, so add 1) and add leading zero
        const year = date.getFullYear(); // Get the full year

        // Combine into the desired format
        var formattedDate = `${year}-${month}-${day}`;

        return formattedDate;
    }

    function calculateAgeDetails(birthDate) {
        const currentDate = new Date(); // Current date
        const birth = new Date(birthDate); // Convert input to Date object

        // Calculate the differences
        let years = currentDate.getFullYear() - birth.getFullYear();
        let months = currentDate.getMonth() - birth.getMonth();
        let days = currentDate.getDate() - birth.getDate();
        if (days < 0) {
            months -= 1;
            const prevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0); // Last day of the previous month
            days += prevMonth.getDate(); // Add days from the previous month
        }
        if (months < 0) {
            years -= 1;
            months += 12;
        }
        $("#age").val(years);
        $("#months").val(months);
        $("#days").val(days);
    }




    $("body").on("click", "#update_record", function() {
        var id = $("#edit_id").val();
        var opd_type_id = $("#edit_opd_type_id").val();
        var consultant_id = $("#edit_consultant_id").val();
        if (id == '' || opd_type_id == '' || consultant_id == '') {
            alert("Please Fill all fields correctly");
            return false;
        }

        $.ajax({
            type: 'post',
            url: "{{ route('pos.update_appointment') }}",
            data: {
                id: id,
                opd_type_id: opd_type_id,
                consultant_id: consultant_id,

                _token: '{{ csrf_token() }}'

            },
            success: function(res) {
                $("#patient_admission_edit_modal").modal("hide");
                user_table.ajax.reload();
            }
        })



    });

    $("body").on("keyup", "#days,#months,#age", function() {
        var year = $("#age").val();
        var months = $("#months").val();
        var days = $("#days").val();
        if (year == '') {
            year = 0;
        }
        if (months == '') {
            months = 0;
        }
        if (days == '') {
            days = 0;
        }

        var dob = calculateDOB(year, months, days);
        $("#dob").val(dob);
    });



    $("body").on("change", "#dob", function() {
        calculateAgeDetails($(this).val());
    });
    $("body").on("blur", "#cnic,#contact_no", function() {
        var cnic = $("#cnic").val();
        var contact_no = $("#contact_no").val();
        var id = $("#id").val();
        if ((cnic.length > 10 || contact_no.length > 8) && id == 0) {
            $.ajax({
                type: 'post',
                url: "{{ route('pos.get_patient_by_cnic') }}",
                data: {
                    cnic: cnic,
                    contact_no: contact_no,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    // console.log(res);
                    if (res.status) {

                        // $("#submit_btn").hide();
                        $("#prev_patients").html('');
                        registered_patients = res.data;
                        if (registered_patients.length > 0) {
                            $.each(registered_patients, function(index, value) {
                                var html = `<tr>
                                            <td>${index + 1}</td>
                                            <td>${value.name}</td>
                                            <td>${value.father_husband_name}</td>
                                            <td><div class="btn btn-sm btn-success select_patient" data-id='${index}' >Select</div></td>

                                            `;
                                $("#prev_patients").append(html);

                            });
                            $("#add_new_record_model").modal("show");
                        }
                    } else {
                        registered_patients = [];
                    }
                }
            });

        }
    });

    $("body").on("blur", "#mr_number", function() {
        var mr_number = $("#mr_number").val();


        var id = $("#id").val();
        if ((mr_number.length > 3 || contact_no.length > 8)) {
            $.ajax({
                type: 'post',
                url: "{{ route('pos.get_patient_by_cnic') }}",
                data: {
                    mr_number: mr_number,

                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    // console.log(res);
                    if (res.status) {

                        $("#prev_patients").html('');
                        registered_patients = res.data;
                        if (registered_patients.length > 0) {
                            $.each(registered_patients, function(index, value) {
                                var html = `<tr>
                                            <td>${index + 1}</td>
                                            <td>${value.name}</td>
                                            <td>${value.father_husband_name}</td>
                                            <td><div class="btn btn-success btn-sm select_patient" data-id='${index}' >Select</div></td>

                                            `;
                                $("#prev_patients").append(html);

                            });
                            $("#add_new_record_model").modal("show");
                        }
                    } else {
                        registered_patients = [];
                        reset_fields();
                    }

                }
            });

        }
    });

    $("body").on("change", "#filter_from_date,#filter_to_date,#filter_opd_type_id,#filter_consultant_id,#created_by", function() {
        user_table.ajax.reload();
    });
    $("body").on("click", ".select_patient", function() {
        var index = $(this).attr("data-id");
        var details = registered_patients[index];

        $("#id").val(details.id);
        $("#cnic").val(details.cnic);
        $("#name").val(details.name);
        $("#contact_no").val(details.contact_no);
        $("#district_id").val(details.district_id).trigger('change');
        $("#location_id").val(details.location_id).trigger('change');
        $("#dob").val(details.dob);
        $("#father_husband_name").val(details.father_husband_name);
        $("#g4no").val(details.g4no);
        $("#age").val(details.age);
        $("#gender").val(details.gender);
        //$("#regdate").val(details.formatted_date);
        registered_patients = [];
        $("#add_new_record_model").modal("hide");

    });


    user_table = $('#patient-list').DataTable({
        processing: true,
        serverSide: true,

        pageLength: 20,
        ajax: {
            url: "{{ route('pos.list_appointments') }}",
            data: function(d) {
                d.from_date = $('#filter_from_date').val();
                d.to_date = $('#filter_to_date').val();
                d.opd_type_id = $('#filter_opd_type_id').val();
                d.consultant_id = $('#filter_consultant_id').val();
                d.created_by = $('#created_by').val();
            }
        },

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },

            {
                data: 'patient.name',
                name: 'patient.name',
                searchable: true
            },

            {
                data: 'opd_type.name',
                name: 'opd_type.name',
                searchable: true
            }, {
                data: 'fee',
                name: 'fee',
                searchable: true
            },
            {
                data: 'consultant.name',
                name: 'consultant.name',
                searchable: true
            },
            {
                data: 'appointment_date',
                name: 'appointment_date',
                searchable: true
            },

            {
                data: 'created_by_user',
                name: 'users.name',
                searchable: true
            },


            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],

        responsive: true,
        processing: true,
        serverSide: true,
        searching: true,
        sorting: true,
        paging: true,
        dom: 'Bfrtip',
        // buttons: [
        //     'copy', 'csv', 'excel', 'pdf', 'print'
        // ]
    });


    $("body").on("click", ".edit_record", function(e) {
        record_id = $(this).attr("data-id");
        var details = JSON.parse($(this).attr("data-details"));
        $("#edit_id").val(details.id);
        $("#edit_opd_type_id").val(details.opd_type_id).trigger('change');
        $("#edit_consultant_id").val(details.consultant_id).trigger('change');
        $("#patient_admission_edit_modal").modal('show');

    });


    //$("body").on("click", "#submit_btn", function(e) {
    $("#patient_register").on("submit", function(e) {

        e.preventDefault();

        let isValid = true;

        // Clear previous error messages
        $(".error-message").remove();
        $(".is-invalid").removeClass("is-invalid");

        // Validate required fields
        $(this).find("[required]").each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass("is-invalid"); // Highlight invalid field
                $(this).after(
                    `<span class="error-message text-danger">This field is required.</span>`
                ); // Show error message
            }
        });

        if (isValid) {

            $("#patient_register").ajaxSubmit({
                url: "{{ route('pos.save_appointments') }}",
                type: 'post',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status == 'exist') {
                        alert(response.message);
                        return false;
                    }
                    // alert(response.appointment_id);
                    var url = "{{route('pos.print_appointment')}}/" + response.appointment_id;
                    window.open(url, '_blank');
                    reset_fields();
                    user_table.ajax.reload();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    //console.log();
                    //alert("Status: " + textStatus); alert("Error: " + errorThrown);
                    alert(JSON.parse(XMLHttpRequest.responseText).message);
                }
            });
        }
    });

    $("body").on("click", ".delete_record", function(e) {
        var id = $(this).attr("data-id");
        if (confirm('Are you sure to delete this record ?')) {
            $.ajax({
                type: 'post',
                url: "{{ route('pos.delete_appointment') }}",
                data: {
                    id: id,
                    table: "appointments",
                    _token: '{{ csrf_token() }}'

                },
                success: function(res) {
                    // user_table.dataTable.reload();
                    window.location.reload();
                }
            })
        } else {
            alert('Why did you press cancel? You should have confirmed');
        }
    });

    function reset_fields() {
        $("#id").val(0);
        $(".id_class").val(0);
        $("#mr_number").val('');
        $("#contact_no").val('');
        $("#consultant_id").val('').trigger("change");
        $("#district_id").val('22').trigger("change");
        $("#dob").val('');
        $("#father_husband_name").val('-');
        $("#g4no").val(0);
        $("#age").val('');
        $("#months").val('0');
        $("#days").val('0');
        $("#gender").val('male');

        $("#location_id").val('53').trigger("change");
        $("#cnic").val('0');
        $("#cnic").val('0');
        $("#name").val('');
        $("#regdate").val('{{date("Y-m-d")}}');
        //$("#relation_id").val('');
        $("#sc_ref_no").val('0');
        $(".id_class").val("0");
    }
</script>
@endpush