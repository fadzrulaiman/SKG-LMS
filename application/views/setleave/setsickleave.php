<!DOCTYPE html>
<html>

<head>
    <title><?php echo lang('set_sickleave_title'); ?></title>
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
    </style>
</head>

<body>

    <h1><?php echo lang('set_sickleave_header'); ?></h1>
    <div class="header-section">
        <div class="action-section">
            <label for="year_select"><?php echo lang('set_sickleave_year'); ?></label>
            <select id="year_select" class="form-control">
                <?php 
                    $currentYear = date('Y');
                    for ($i = $currentYear - 1; $i <= $currentYear + 1; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <button id="set_sickleave_btn" class="btn btn-primary"><?php echo lang('set_sickleave_button'); ?></button>
        </div>
        <div class="filter-section">
            <form method="GET" action="<?php echo base_url('setleave/setsickleave'); ?>"
                style="display: flex; flex-direction: column; align-items: flex-start;">
                <label for="year_filter"><?php echo lang('set_sickleave_filter'); ?></label>
                <select id="year_filter" name="year" class="form-control">
                    <option value="">Select Year</option>
                    <?php 
                        for ($i = $currentYear - 1; $i <= $currentYear + 1; $i++): ?>
                    <option value="<?php echo $i; ?>"
                        <?php echo (isset($selected_year) && $selected_year == $i) ? 'selected' : ''; ?>>
                        <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary"><?php echo lang('set_sickleave_with_entitlements'); ?></button>
                <button id="find_null" href="#" type="button" class="btn btn-primary"><?php echo lang('set_sickleave_no_entitlements'); ?></button>
            </form>
        </div>
    </div>
    <div class="table-section">
        <table id="entitleddays_table" class="display table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th><?php echo lang('set_sickleave_employeeid'); ?></th>
                    <th><?php echo lang('set_sickleave_name'); ?></th>
                    <th><?php echo lang('set_sickleave_startdate'); ?></th>
                    <th><?php echo lang('set_sickleave_enddate'); ?></th>
                    <th><?php echo lang('set_sickleave_daysentitled'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($entitleddays) && !empty($entitleddays)) { foreach ($entitleddays as $row): ?>
                <tr>
                    <td><?php echo $row['employee']; ?></td>
                    <td><?php echo $row['employee_name']; ?></td>
                    <td><?php echo $row['startdate']; ?></td>
                    <td><?php echo $row['enddate']; ?></td>
                    <td><?php echo $row['days']; ?></td>
                </tr>
                <?php endforeach; } ?>
            </tbody>
        </table>
        <div id="reportResult"></div>
    </div>
    <div class="export-section" style="text-align: right; margin-top: 20px;">
        <a id="export_to_excel" href="#" class="btn btn-primary"><?php echo lang('set_sickleave_exportexcel'); ?></a>
    </div>

    <script>
$(document).ready(function() {
    var table = $('#entitleddays_table').DataTable({
        columns: [
            { data: 'employee' },
            { data: 'employee_name' },
            { data: 'startdate' },
            { data: 'enddate' },
            { data: 'days' }
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

    $('#set_sickleave_btn').click(function(event) {
        event.preventDefault();
        var selectedYear = $('#year_select').val();
        var ajaxQuery = '<?php echo base_url();?>setleave/executesickleaveyear/' + selectedYear;
        fetchData(ajaxQuery);
    });

    $('#find_null').click(function(event) {
        event.preventDefault();
        var selectedYear = $('#year_filter').val();
        var ajaxQuery = '<?php echo base_url();?>setleave/executenullsickleaveyear/' + selectedYear;
        fetchData(ajaxQuery);
    });

    $('#export_to_excel').click(function(event) {
        event.preventDefault();
        var year = $('#year_filter').val();
        var exportUrl = '<?php echo base_url();?>setleave/exportsickleave';
        if (year) {
            exportUrl += '?year=' + year;
        }
        window.location.href = exportUrl;
    });
});
    </script>

</body>

</html>
