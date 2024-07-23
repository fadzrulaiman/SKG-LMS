<!DOCTYPE html>
<html>

<head>
    <title><?php echo lang('set_leavebank_title'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/datatable/datatables.min.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>">
    <script src="<?php echo base_url('assets/js/jquery.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/datatable/datatables.min.js'); ?>"></script>
    <style>
        body {
            margin: 20px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .filter-section,
        .action-section {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .table-section {
            margin-top: 20px;
        }

        .btn {
            margin-top: 10px;
        }

        .form-control {
            width: 200px;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        .export-section {
            text-align: right;
            margin-top: 20px;
        }

        #reportResult img {
            max-width: 50px;
        }

        #reportResult {
            margin-top: 20px;
        }

        .table th, .table td {
            text-align: center;
        }
    </style>
</head>

<body>
    <h1><?php echo lang('set_leavebank_header'); ?></h1>
    <div class="header-section">
        <div class="action-section">
            <label><?php echo lang('set_leavebank_year'); ?></label>
            <button id="set_leavebank_btn" class="btn btn-primary"><?php echo lang('set_leavebank_button'); ?></button>
        </div>
        <div class="filter-section">
            <form method="GET" action="<?php echo base_url('setleave/setleavebank'); ?>" style="display: flex; flex-direction: column; align-items: flex-start;">
                <label for="year_filter"><?php echo lang('set_sickleave_filter'); ?></label>
                <select id="year_filter" name="year" class="form-control">
                    <option value="">Select Year</option>
                    <?php 
                        $currentYear = date('Y');
                        for ($i = $currentYear - 1; $i <= $currentYear + 1; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo (isset($selected_year) && $selected_year == $i) ? 'selected' : ''; ?>>
                        <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary"><?php echo lang('set_leavebank_filter'); ?></button>
            </form>
        </div>
    </div>
    <div class="table-section">
        <table id="entitleddays_table" class="display table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th><?php echo lang('set_leavebank_employeeid'); ?></th>
                    <th><?php echo lang('set_leavebank_name'); ?></th>
                    <th><?php echo lang('set_leavebank_contract'); ?></th>
                    <th><?php echo lang('set_leavebank_annualleave_balance'); ?></th>
                    <th><?php echo lang('set_leavebank_leavebank_balance'); ?></th>
                    <th><?php echo lang('set_leavebank_lastyearunusedleave'); ?></th>
                    <th><?php echo lang('set_leavebank_daysentitled'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($entitleddays) && !empty($entitleddays)) { foreach ($entitleddays as $row): ?>
                <tr>
                    <td><?php echo $row['employee_id']; ?></td>
                    <td><?php echo $row['fullname']; ?></td>
                    <td><?php echo $row['contract_name']; ?></td>
                    <td><?php echo $row['annual_leave_balance_last_year']; ?></td>
                    <td><?php echo $row['leave_bank_balance_last_year']; ?></td>
                    <td><?php echo $row['leave_burned']; ?></td>
                    <td><?php echo $row['leave_bank_entitlement_this_year']; ?></td>
                </tr>
                <?php endforeach; } ?>
            </tbody>
        </table>
        <div id="reportResult"></div>
    </div>
    <div class="export-section">
        <a id="export_to_excel" href="#" class="btn btn-primary"><?php echo lang('set_leavebank_exportexcel'); ?></a>
    </div>

    <script>
        $(document).ready(function() {
            var table = $('#entitleddays_table').DataTable({
                columns: [
                    { data: 'employee_id' },
                    { data: 'fullname' },
                    { data: 'contract_name' },
                    { data: 'annual_leave_balance_last_year' },
                    { data: 'leave_bank_balance_last_year' },
                    { data: 'leave_burned' },
                    { data: 'leave_bank_entitlement_this_year' }
                ]
            });

            function handleAjaxResponse(data) {
                console.log(data); // Debug response
                if (data.status === 'success') {
                    table.clear().rows.add(data.data).draw();
                    $('#reportResult').html(''); // Clear any previous messages
                } else {
                    $('#reportResult').html('Select year to filter');
                }
            }

            function handleAjaxError(xhr, status, error) {
                $('#reportResult').html('Select year to filter');
                console.error('Error:', error); // Log error details
            }

            function fetchData(url) {
                $('#reportResult').html("<img src='<?php echo base_url();?>assets/images/loading.gif' />");
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        handleAjaxResponse(data);
                    },
                    error: function(xhr, status, error) {
                        handleAjaxError(xhr, status, error);
                    }
                });
            }

            $('#set_leavebank_btn').click(function(event) {
                event.preventDefault();
                var ajaxQuery = '<?php echo base_url();?>setleave/executeleavebank/';
                fetchData(ajaxQuery);
            });

            $('#export_to_excel').click(function(event) {
                event.preventDefault();
                var year = $('#year_filter').val();
                var exportUrl = '<?php echo base_url();?>setleave/exportleavebank';
                if (year) {
                    exportUrl += '?year=' + year;
                }
                window.location.href = exportUrl;
            });
        });
    </script>
</body>

</html>
