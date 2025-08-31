@extends('layouts.' . config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@push('stylesheets')
    <style>
        .table> :not(caption)>*>* {
            padding: 5px;
        }
        .red-row {
            background-color: #e9747f !important;
            color:white;
        }

    </style>

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">


            {{-- LISTIN PATIENTS --}}
            <div class="card my-5">
                <h3>Syncing Data.........</h3>
            </div>
            <!-- /traffic sources -->
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


        setInterval(function () {
            $('#screen-blocker').show();
            $('#loading-icon').show();
            load_sync();

        }, 120000);//300000

        load_sync();

        function load_sync() {
            $.ajax({
                type: 'POST',
                url: "{{ route('pos.syncDataLive') }}",
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    $('#screen-blocker').hide();
                    $('#loading-icon').hide();
                },
                error: function(xhr, status, error) {
                    $('#screen-blocker').hide();
                    $('#loading-icon').hide();
                    console.error("Error:", error);
                },
                complete: function() {
                    // Runs on both success and error
                    $('#screen-blocker').hide();
                    $('#loading-icon').hide();
                }
            });
        }
    </script>
@endpush
