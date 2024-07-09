<style?php
/**
 * This view allows a manager or HR admin to visualize the leave balance (taken/available/entitled) of an employee.
 * @since 0.2.0
 */
?>
<style>
.superlink {
    color: #007bff;
    text-decoration: none;
    cursor: pointer;
    font-size: 1.75rem; /* This sets the font size similar to an h3 */
    font-weight: 500; /* This sets the font weight similar to an h3 */
}

.superlink:hover {
    color: #0056b3;
    text-decoration: underline;
}
</style>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h2><?php echo lang('hr_summary_title'); ?>&nbsp;<?php echo $employee_id; ?>&nbsp;<span class="text-muted"> (<?php echo $employee_name; ?>)</span></h2>
            <p><?php echo lang('hr_summary_date_field'); ?>&nbsp;<input type="text" id="refdate" class="form-control d-inline-block w-auto" /></p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <table id="counters" class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th><?php echo lang('hr_summary_thead_type'); ?></th>
                        <th><?php echo lang('hr_summary_thead_available'); ?></th>
                        <th><?php echo lang('hr_summary_thead_taken'); ?></th>
                        <th><?php echo lang('hr_summary_thead_entitled'); ?></th>
                        <th><?php echo lang('hr_summary_thead_description'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $key => $value) { ?>
                        <tr>
                            <td><?php echo $key; ?></td>
                            <td><?php echo round(((float) $value[1] - (float) $value[0]), 3, PHP_ROUND_HALF_DOWN); ?></td>
                            <td><?php echo $value[0] != '-' ? $value[0] : '-'; ?></td>
                            <td><?php echo ((float) $value[1]); ?></td>
                            <td><?php echo $value[2] != 'x' ? $value[2] : ''; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <?php if ($source == 'employees') { ?>
                        <a class="superlink" href="<?php echo base_url(); ?>entitleddays/contract/<?php echo $contract_id; ?>">
                            <?php echo lang('entitleddays_counters_title_contract'); ?><?php echo $contract_id; ?>
                        </a>
                        <span class="text-muted"> (<?php echo $contract_name; ?>)</span>
                    <?php } else { ?>
                        <h3 class="card-title"><?php echo lang('entitleddays_counters_title_contract') . ' ' . $contract_id; ?>&nbsp;<span class="text-muted"> (<?php echo $contract_name; ?>)</span></h3>
                    <?php } ?>
                </div>
                <div class="card-body">
                    <p><?php echo lang('entitleddays_counters_description_contract'); ?><?php echo $contract_start; ?> - <?php echo $contract_end; ?></p>
                    <table id="entitleddayscontract" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th><?php echo lang('entitleddays_contract_index_thead_start'); ?></th>
                                <th><?php echo lang('entitleddays_contract_index_thead_end'); ?></th>
                                <th><?php echo lang('entitleddays_contract_index_thead_days'); ?></th>
                                <th><?php echo lang('entitleddays_contract_index_thead_type'); ?></th>
                                <th><?php echo lang('entitleddays_contract_index_thead_description'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entitleddayscontract as $days) { ?>
                                <tr>
                                    <?php 
                                        $startDate = new DateTime($days['startdate']);
                                        $endDate = new DateTime($days['enddate']);
                                    ?>
                                    <td data-order="<?php echo $startDate->getTimestamp(); ?>"><?php echo $startDate->format(lang('global_date_format')); ?></td>
                                    <td data-order="<?php echo $endDate->getTimestamp(); ?>"><?php echo $endDate->format(lang('global_date_format')); ?></td>
                                    <td><?php echo $days['days']; ?></td>
                                    <td><?php echo $days['type_name']; ?></td>
                                    <td><?php echo $days['description']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <?php if ($source == 'employees') { ?>
                        <a class="superlink" href="<?php echo base_url(); ?>entitleddays/user/<?php echo $employee_id; ?>">
                            <?php echo lang('entitleddays_counters_title_employee'); ?>
                        </a>
                    <?php } else { ?>
                        <h3 class="card-title"><?php echo lang('entitleddays_counters_title_employee'); ?></h3>
                    <?php } ?>
                </div>
                <div class="card-body">
                    <table id="entitleddaysemployee" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th><?php echo lang('entitleddays_user_index_thead_start'); ?></th>
                                <th><?php echo lang('entitleddays_user_index_thead_end'); ?></th>
                                <th><?php echo lang('entitleddays_user_index_thead_days'); ?></th>
                                <th><?php echo lang('entitleddays_user_index_thead_type'); ?></th>
                                <th><?php echo lang('entitleddays_user_index_thead_description'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entitleddaysemployee as $days) { ?>
                                <tr>
                                    <?php 
                                        $startDate = new DateTime($days['startdate']);
                                        $endDate = new DateTime($days['enddate']);
                                    ?>
                                    <td><?php echo $startDate->format(lang('global_date_format')); ?></td>
                                    <td><?php echo $endDate->format(lang('global_date_format')); ?></td>
                                    <td><?php echo $days['days']; ?></td>
                                    <td><?php echo $days['type_name']; ?></td>
                                    <td><?php echo $days['description']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-3">
            <?php if ($source == 'employees') { ?>
                <a href="<?php echo base_url(); ?>hr/employees" class="btn btn-primary">
                    <i class="mdi mdi-arrow-left-bold"></i>&nbsp;<?php echo lang('hr_summary_button_list'); ?>
                </a>
            <?php } else { ?>
                <a href="<?php echo base_url(); ?>requests/collaborators" class="btn btn-primary">
                    <i class="mdi mdi-arrow-left-bold"></i>&nbsp;<?php echo lang('hr_summary_button_list'); ?>
                </a>
            <?php } ?>
        </div>
        <div class="col-9">&nbsp;</div>
    </div>
</div>

<!-- CSS Dependencies -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/flick/jquery-ui.custom.min.css">

<!-- JS Dependencies -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/js/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.custom.min.js"></script>
<?php if ($language_code != 'en') { ?>
    <script src="<?php echo base_url(); ?>assets/js/i18n/jquery.ui.datepicker-<?php echo $language_code; ?>.js"></script>
<?php } ?>

<script>
$(document).ready(function() {
    // Initialize datepicker
    var isDefault = <?php echo $isDefault; ?>;
    moment.locale('<?php echo $language_code; ?>', { longDateFormat: { L: '<?php echo lang('global_date_momentjs_format'); ?>' } });
    var reportDate = '<?php $date = new DateTime($refDate); echo $date->format(lang('global_date_format')); ?>';
    var todayDate = moment().format('L');
    
    if (isDefault == 1) {
        $("#refdate").val(todayDate);
    } else {
        $("#refdate").val(reportDate);
    }

    $('#refdate').datepicker({
        onSelect: function(dateText, inst) {
            var tmpUnix = moment($("#refdate").datepicker("getDate")).unix();
            var url = "<?php echo base_url(); ?>hr/counters/<?php echo $source; ?>/<?php echo $employee_id; ?>/" + tmpUnix;
            window.location = url;
        }
    });

    // Initialize DataTables
    $('#counters').DataTable({
        order: [[0, "desc"]],
        language: {
            decimal: "<?php echo lang('datatable_sInfoThousands'); ?>",
            processing: "<?php echo lang('datatable_sProcessing'); ?>",
            search: "<?php echo lang('datatable_sSearch'); ?>",
            lengthMenu: "<?php echo lang('datatable_sLengthMenu'); ?>",
            info: "<?php echo lang('datatable_sInfo'); ?>",
            infoEmpty: "<?php echo lang('datatable_sInfoEmpty'); ?>",
            infoFiltered: "<?php echo lang('datatable_sInfoFiltered'); ?>",
            infoPostFix: "<?php echo lang('datatable_sInfoPostFix'); ?>",
            loadingRecords: "<?php echo lang('datatable_sLoadingRecords'); ?>",
            zeroRecords: "<?php echo lang('datatable_sZeroRecords'); ?>",
            emptyTable: "<?php echo lang('datatable_sEmptyTable'); ?>",
            paginate: {
                first: "<?php echo lang('datatable_sFirst'); ?>",
                previous: "<?php echo lang('datatable_sPrevious'); ?>",
                next: "<?php echo lang('datatable_sNext'); ?>",
                last: "<?php echo lang('datatable_sLast'); ?>"
            },
            aria: {
                sortAscending: "<?php echo lang('datatable_sSortAscending'); ?>",
                sortDescending: "<?php echo lang('datatable_sSortDescending'); ?>"
            }
        }
    });

    $('#entitleddayscontract').DataTable({
        order: [[0, "desc"]],
        language: {
            decimal: "<?php echo lang('datatable_sInfoThousands'); ?>",
            processing: "<?php echo lang('datatable_sProcessing'); ?>",
            search: "<?php echo lang('datatable_sSearch'); ?>",
            lengthMenu: "<?php echo lang('datatable_sLengthMenu'); ?>",
            info: "<?php echo lang('datatable_sInfo'); ?>",
            infoEmpty: "<?php echo lang('datatable_sInfoEmpty'); ?>",
            infoFiltered: "<?php echo lang('datatable_sInfoFiltered'); ?>",
            infoPostFix: "<?php echo lang('datatable_sInfoPostFix'); ?>",
            loadingRecords: "<?php echo lang('datatable_sLoadingRecords'); ?>",
            zeroRecords: "<?php echo lang('datatable_sZeroRecords'); ?>",
            emptyTable: "<?php echo lang('datatable_sEmptyTable'); ?>",
            paginate: {
                first: "<?php echo lang('datatable_sFirst'); ?>",
                previous: "<?php echo lang('datatable_sPrevious'); ?>",
                next: "<?php echo lang('datatable_sNext'); ?>",
                last: "<?php echo lang('datatable_sLast');
