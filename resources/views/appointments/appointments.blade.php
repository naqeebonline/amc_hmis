@extends('layouts.' . config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@push('stylesheets')
    <style>
        .table> :not(caption)>*>* {
            padding: 5px;
        }

    </style>

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">

            <!-- Traffic sources -->
            <div class="card">
                <div class="card-body">
                    <form class=" form-submit-event" id="patient_register">
                        <div class="row" style="background-color: lightgrey; padding: 5px">
                            <div class="col-md-3 col-sm-4 mt-1">
                                <select name="opd_type_id" required id="opd_type_id" class="form-control select2">
                                    <option value="">OPD Type</option>
                                    @foreach ($opd_type as $value)
                                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-4 mt-1">
                                <select name="consultant_id" required id="consultant_id" class="form-control select2">
                                    <option value="">Select Consultant</option>
                                    @foreach ($consultants as $value)
                                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 mt-1 ">

                                <input type="date" style="pointer-events: none" required id="regdate" value="{{ date('Y-m-d') }}" name="regdate"
                                       class="form-control" placeholder="" autocomplete="off">
                            </div>
                        </div>


                        <div class="row">
                            <input type="hidden" required id="id" name="id" value="0"
                                   class="form-control" />

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">Contact Number<span
                                            class="asterisk">*</span></label>
                                <input type="text" required id="contact_no" oninput="if(this.value.length > 11) this.value = this.value.slice(0, 11);"  name="contact_no" class="form-control"
                                       placeholder="" autocomplete="off">
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label class="form-label">CNIC (without -)<span class="asterisk">*</span></label>
                                <input type="text" required id="cnic" name="cnic"

                                       class="form-control" placeholder="" autocomplete="off"   pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="13" title="Only digits are allowed"/>
                            </div>



                            <div class="col-md-2 col-sm-4 mb-3">
                                <label class="form-label">Patient Name<span class="asterisk">*</span></label>
                                <input type="text"  required id="name" name="name" class="form-control"
                                       placeholder="" autocomplete="off">
                            </div>

                            <div class="col-md-3 col-sm-4 ">
                                <label style="text-align: center; width: 100%; font-weight: bold; color:red">Age</label>
                                <div class="d-flex align-items-center">
                                    <label for="years" class="me-2">Years:</label>
                                    <input type="text" id="age" name="age" class="form-control" style="width: 50px;" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="4" title="Only digits are allowed"/>

                                    <label for="months" class="me-2">Months:</label>
                                    <input type="text" id="months" value="0" name="months" class="form-control" style="width: 50px;" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="2" title="Only digits are allowed"/>

                                    <label for="days" class="me-2">Days:</label>
                                    <input type="text" id="days" value="0" name="days" class="form-control" style="width: 50px;" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="2" title="Only digits are allowed"/>
                                </div>
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label class="form-label">Gender:<span class="asterisk">*</span></label>
                                <select name="gender" id="gender" class="form-control">
                                    <option selected value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">Father/Husband Name<span
                                            class="asterisk">*</span></label>
                                <input type="text" required id="father_husband_name" name="father_husband_name"
                                       class="form-control" placeholder="" autocomplete="off">
                            </div>



                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">District<span class="asterisk">*</span></label>
                                <select name="district_id" required id="district_id" class="form-control">
                                    <option value="">Select District</option>
                                    @foreach ($district as $dist)
                                        <option value="{{ $dist->id }}">{{ $dist->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">location<span class="asterisk">*</span></label>

                                <select name="location_id" required id="location_id" class="form-select">
                                    <option value="">Select Locations</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>


                            </div>

                            <div class="col-md-3 col-sm-4 mb-3" >
                                <label for="nameBasic" class="form-label">Dob<span class="asterisk">*</span></label>
                                <input  type="date" required id="dob" name="dob" class="form-control"
                                        placeholder="" autocomplete="off">
                            </div>




                            <div class="text-left mt-3">
                                <button class="btn btn-success" id="submit_btn">Save</button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>


            {{-- LISTIN PATIENTS --}}
            <div class="card my-5">
                <div class="card-body">
                    <h5 class="card-title">List Appointments</h5>

                        <div class="row">
                            <div class="col-md-2">
                                <label>From Date</label>
                                <input type="date" class="form-control" value="{{date("Y-m-d")}}" id="filter_from_date">
                            </div>
                            <div class="col-md-2">
                                <label>To Date</label>
                                <input type="date" class="form-control" value="{{date("Y-m-d")}}" id="filter_to_date">
                            </div>

                            <div class="col-md-2">
                                <label>OPD Type</label>
                                <select class="form-select" id="filter_opd_type_id">
                                    <option value="">Please select option...</option>
                                    @foreach($opd_type as $key => $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label>Consultant</label>
                                <select class="form-select" id="filter_consultant_id">
                                    <option value="">Please select option...</option>
                                    @foreach($consultants as $key => $value)
                                        <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mt-4">
                                <div class="btn btn-primary print_all_details">Print</div>
                            </div>
                        </div>

                    <div class="table-responsive" style="min-height: 200px">

                        <table id="patient-list" class="table table-responsive table-striped  table-condensed">
                            <thead>
                            <tr>
                                <th width="15%">S.N</th>
                                <th width="15%">Name</th>
                                <th width="15%">Opd Type</th>
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
                <input type="hidden" id="id" name="id" value="0">

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

               -         <div class="col-md-12 mb-3">
                            <label for="nameBasic" class="form-label">Opd Type<span
                                        class="asterisk">*</span></label>
                            <input id="edit_id" value="" type="hidden">
                            <select  id="edit_opd_type_id" class="form-select">
                                <option value="">Select Opd Type</option>
                                @foreach ($opd_type as $value)
                                    <option  value="{{ $value->id }}">{{ $value->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="nameBasic" class="form-label">Consultant<span
                                        class="asterisk">*</span></label>

                            <select id="edit_consultant_id" class="form-select">
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
                        Close                </button>
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
            $("#district_id").select2();
            $("#location_id").select2();

            $("#edit_consultant_id").select2({dropdownParent: $('.my_modal')});
            $("#edit_opd_type_id").select2({dropdownParent: $('.my_modal')});
        }, 1000);


        $("body").on("click", ".print_all_details", function(e) {
            var from_date = $("#filter_from_date").val();
            var to_date = $("#filter_to_date").val();
            var filter_opd_type_id = $("#filter_opd_type_id").val();
            var filter_consultant_id = $("#filter_consultant_id").val();
            if(from_date == ''){
                from_date = 'nill';
            }
            if(to_date == ''){
                to_date = 'nill';
            }
            if(filter_opd_type_id == ''){
                filter_opd_type_id = 0;
            }
            if(filter_consultant_id == ''){
                filter_consultant_id = 0;
            }
            var url = "{{route('pos.print_all_appointments')}}/"+from_date+"/"+to_date+"/"+filter_opd_type_id+"/"+filter_consultant_id;
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
            dob.setMonth(dob.getMonth() - months);      // Subtract months
            dob.setDate(dob.getDate() - days);          // Subtract days


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
            var opd_type_id =  $("#edit_opd_type_id").val();
            var consultant_id = $("#edit_consultant_id").val();
            if(id == '' || opd_type_id == '' || consultant_id == ''){
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
            if(year == ''){
                year = 0;
            }
            if(months == ''){
                months = 0;
            }
            if(days == ''){
                days = 0;
            }

            var dob = calculateDOB(year,months,days);
            $("#dob").val(dob);
        });



        $("body").on("change", "#dob", function() {
            calculateAgeDetails($(this).val());
        });
        $("body").on("blur", "#cnic,#contact_no", function() {
            var cnic = $("#cnic").val();
            var contact_no = $("#contact_no").val();
            var id = $("#id").val();
            if ((cnic.length > 10 || contact_no.length > 8) && id == 0 ) {
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
                            if(registered_patients.length > 0){
                                $.each(registered_patients, function(index, value) {
                                    var html = `<tr>
                                            <td>${index + 1}</td>
                                            <td>${value.name}</td>
                                            <td>${value.father_husband_name}</td>
                                            <td><div class="btn btn-success select_patient" data-id='${index}' >Select</div></td>

                                            `;
                                    $("#prev_patients").append(html);

                                });
                                $("#add_new_record_model").modal("show");
                            }
                        }else{
                            registered_patients = [];
                        }
                    }
                });

            }
        });

        $("body").on("change","#filter_from_date,#filter_to_date,#filter_opd_type_id,#filter_consultant_id",function () {
            user_table.ajax.reload();
        });
        $("body").on("click",".select_patient",function () {
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
                 data: function (d) {
                     d.from_date = $('#filter_from_date').val();
                     d.to_date = $('#filter_to_date').val();
                     d.opd_type_id = $('#filter_opd_type_id').val();
                     d.consultant_id = $('#filter_consultant_id').val();

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
                    data: 'created_by.name',
                    name: 'created_by.name',
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
                    url: '{{ route('pos.save_appointments') }}',
                    type: 'post',
                    data: {
                        _token: '{{ csrf_token() }}'

                    },
                    success: function(response) {
                        if(response.status =='exist'){
                            alert(response.message);
                            return false;
                        }
                       // alert(response.appointment_id);
                        var url = "{{route('pos.print_appointment')}}/"+response.appointment_id;
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
                    url: "{{ route('pos.deactivate_record') }}",
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
            $("#contact_no").val('');
            $("#consultant_id").val('').trigger("change");
            $("#district_id").val('').trigger("change");
            $("#dob").val('');
            $("#father_husband_name").val('');
            $("#g4no").val(0);
            $("#age").val('');
            $("#months").val('');
            $("#days").val('');
            $("#gender").val('');
            $("#id").val('');
            $("#location_id").val('').trigger("change");
            $("#cnic").val('');
            $("#cnic").val('');
            $("#name").val('');
            $("#regdate").val('{{date("Y-m-d")}}');
            //$("#relation_id").val('');
            $("#sc_ref_no").val('');
        }
    </script>
@endpush
