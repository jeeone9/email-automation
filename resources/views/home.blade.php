@extends('layouts.app')

@section('content')
<div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Upload CSV</div>

                    <div class="panel-body">
                        <form id="importCsvForm" class="form-horizontal" method="POST" action="{{ route('import_csv') }}" enctype="multipart/form-data">
                            {{ csrf_field() }}

                            <div class="form-group">
                                <label for="csv_file" class="col-md-4 control-label">CSV file to import</label>

                                <div class="col-md-6">
                                    <input id="csv_file" type="file" class="form-control" name="csv_file" accept=".csv" required>
                                        <!-- <span class="help-block">
                                        <strong>{{ $errors->first('csv_file') }}</strong>
                                    </span> -->
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-8 col-md-offset-4">
                                    <button type="submit" class="btn btn-primary" onClick="importCsv()">
                                        Upload CSV
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 table-responsive">
                <table id="contractTable" class="table table-striped table-bordered" style="width:100%; background-color: white;">
                    <thead>
                        <tr>
                            <th>Contract Number</th>
                            <th>Expiration Date</th>
                            <th>Email of Salesperson</th>
                            <th>Customer number</th>
                            <th>Reminder Date</th>
                            <th>Reminder Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            createContractsViewTable()
        });

        $('#importCsvForm').submit(function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            $.ajaxSetup({
            headers: { 'X-CSRF-Token' : $('meta[name=csrf-token]').attr('content') }
            });
            $.ajax({
                type:'post',
                processData: false,
                contentType: false,
                url:'import_csv',
                data: fd,
                success:function(data){
                    if(data.msg){
                        alert(data.msg)
                    }
                    createContractsViewTable()
                },
                error:function(request, status, error){
                    alert('Could not process Request')
                }
            });
        }); 

        function createContractsViewTable() {
            $('#contractTable').DataTable().destroy()
            $('#contractTable').DataTable({
                ajax: {
                    url: '/contracts',
                    dataSrc: 'data'
                },
                columns: [
                    {data : "contract_id"},
                    {data : "expiry_date"},
                    {data : "sales_person_email"},
                    {data : "customer_number"},
                    {data : "reminder_date"},
                    {data : "reminder_status"},
                ]
            });
        }   
    </script>
@endsection
