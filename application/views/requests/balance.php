<?php
/**
 * This view displays the leave balance of the collaborators of the connected employee (manager).
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since      0.4.5
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <h2><?php echo lang('requests_balance_title'); ?></h2>

            <p><?php echo lang('requests_balance_description'); ?></p>

            <p>
                <?php echo lang('requests_balance_date_field'); ?>
                <input type="text" id="refdate" />
            </p>

            <table id="balance" class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th><?php echo lang('fullname'); ?></th>
                        <th><?php echo lang('employmentdate'); ?></th>
                        <th><?php echo lang('position'); ?></th>
                        <?php foreach ($types as $type): ?>
                            <th><?php echo $type['name']; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <?php foreach ($row as $key => $value): ?>
                                <td><?php echo $value == '' ? '&nbsp;' : $value; ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<!-- CSS Dependencies -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/datatable/ColReorder-1.3.1/css/colReorder.dataTables.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/flick/jquery-ui.custom.min.css">

<!-- JS Dependencies -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/js/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/js/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/js/buttons.colVis.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/js/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/ColReorder-1.3.1/js/dataTables.colReorder.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.custom.min.js"></script>
<?php if ($language_code != 'en'): ?>
<script src="<?php echo base_url(); ?>assets/js/i18n/jquery.ui.datepicker-<?php echo $language_code; ?>.js"></script>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#balance').DataTable({
        stateSave: true,
        dom: 'Bfrtip',
        colReorder: true,
        buttons: [
            {
                extend: 'pageLength',
                text: '<?php echo lang('datatable_pagination'); ?>'
            },
            {
                extend: 'colvis',
                postfixButtons: [
                    {
                        extend: 'colvisRestore',
                        text: '<?php echo lang('datatable_colvisRestore'); ?>'
                    }
                ]
            },
            {
                extend: 'excelHtml5',
                text: '<i class="mdi mdi-download"></i>',
                titleAttr: 'Excel'
            }
        ],
        lengthMenu: [
            [10, 25, 50, -1],
            [
                '<?php echo lang('datatable_10_rows'); ?>',
                '<?php echo lang('datatable_25_rows'); ?>',
                '<?php echo lang('datatable_50_rows'); ?>',
                '<?php echo lang('datatable_all_rows'); ?>'
            ]
        ],
        language: {
            buttons: {
                colvis: '<?php echo lang('datatable_colvis'); ?>'
            },
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
        },
    });

    // Initialize datepicker
    var isDefault = <?php echo $isDefault; ?>;
    moment.locale('<?php echo $language_code; ?>', {
        longDateFormat: { L: '<?php echo lang('global_date_momentjs_format'); ?>' }
    });
    var reportDate = '<?php $date = new DateTime($refDate); echo $date->format(lang('global_date_format')); ?>';
    var todayDate = moment().format('L');

    if (isDefault == 1) {
        $("#refdate").val(todayDate);
    } else {
        $("#refdate").val(reportDate);
    }

    $('#refdate').datepicker({
        dateFormat: '<?php echo lang('global_date_js_format'); ?>',
        onSelect: function(dateText, inst) {
            var tmpUnix = moment($("#refdate").datepicker("getDate")).unix();
            var url = "<?php echo base_url(); ?>requests/balance/" + tmpUnix;
            window.location = url;
        }
    });
});
</script>
