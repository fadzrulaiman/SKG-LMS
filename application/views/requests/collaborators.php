<?php
/**
 * This view displays the list of collaborators of the connected employee.
 * e.g. users having the connected user as their line manager.
 * @since 0.4.0
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <h2><?php echo lang('requests_collaborators_title'); ?></h2>

            <?php echo $flash_partial_view; ?>

            <p><?php echo lang('requests_collaborators_description'); ?></p>

            <table id="collaborators" class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th><?php echo lang('requests_collaborators_thead_id'); ?></th>
                        <th><?php echo lang('requests_collaborators_thead_fullname'); ?></th>
                        <th><?php echo lang('requests_collaborators_thead_email'); ?></th>
                        <th><?php echo lang('requests_collaborators_thead_identifier'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collaborators as $collaborator): ?>
                        <tr>
                            <td data-order="<?php echo $collaborator['id']; ?>">
                                <?php echo $collaborator['id']; ?>
                                <div class="float-right">
                                    <?php if ($this->config->item('requests_by_manager') == TRUE) { ?>
                                        <a href="<?php echo base_url(); ?>requests/createleave/<?php echo $collaborator['id']; ?>" title="<?php echo lang('requests_collaborators_thead_link_create_leave'); ?>"><i class="mdi mdi-file-plus nolink"></i></a>
                                    <?php } ?>
                                    <a href="<?php echo base_url(); ?>hr/counters/collaborators/<?php echo $collaborator['id']; ?>" title="<?php echo lang('requests_collaborators_thead_link_balance'); ?>"><i class="mdi mdi-information-outline nolink"></i></a>
                                    &nbsp;<a href="<?php echo base_url(); ?>hr/presence/collaborators/<?php echo $collaborator['id']; ?>" title="<?php echo lang('requests_collaborators_thead_link_presence'); ?>"><i class="mdi mdi-chart-pie nolink"></i></a>
                                    &nbsp;<a href="<?php echo base_url(); ?>calendar/year/<?php echo $collaborator['id']; ?>" title="<?php echo lang('requests_collaborators_thead_link_year'); ?>"><i class="mdi mdi-calendar-text nolink"></i></a>
                                </div>
                            </td>
                            <td><?php echo $collaborator['firstname'] . ' ' . $collaborator['lastname']; ?></td>
                            <td><a href="mailto:<?php echo $collaborator['email']; ?>"><?php echo $collaborator['email']; ?></a></td>
                            <td><?php echo $collaborator['identifier']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php if ($this->config->item('ics_enabled') == TRUE) { ?>
                <a id="lnkICS" href="#"><i class="mdi mdi-earth nolink"></i> ICS</a>
            <?php } ?>
        </div>
    </div>

    <div id="frmLinkICS" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="icsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="icsModalLabel">ICS</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <?php $icsUrl = base_url() . 'ics/collaborators/' . $user_id . '?token=' . $this->session->userdata('random_hash'); ?>
                        <input type="text" class="form-control" id="txtIcsUrl" value="<?php echo $icsUrl; ?>" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="cmdCopy" data-clipboard-text="<?php echo $icsUrl; ?>">
                                <i class="mdi mdi-content-copy"></i>
                            </button>
                        </div>
                    </div>
                    <a href="#" id="tipCopied" data-toggle="tooltip" title="<?php echo lang('copied'); ?>" data-placement="right" data-container="#cmdCopy"></a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo lang('OK'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS Dependencies -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/css/buttons.dataTables.min.css">

<!-- JS Dependencies -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/js/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/js/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/Buttons-1.1.2/js/buttons.colVis.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/ColReorder-1.3.1/js/dataTables.colReorder.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/clipboard.min.js"></script>

<script>
$(document).ready(function() {
    // Transform the HTML table into a fancy datatable
    $('#collaborators').DataTable({
        stateSave: true,
        order: [[3, 'asc'], [2, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pageLength',
                text: '<?php echo lang('datatable_pagination'); ?>'
            },
            {
                extend: 'colvis',
                columns: ':not(:first-child)',
                postfixButtons: [
                    {
                        extend: 'colvisRestore',
                        text: '<?php echo lang('datatable_colvisRestore'); ?>'
                    }
                ]
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
        colReorder: {
            fixedColumnsLeft: 1
        },
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
        }
    });

    // Copy/Paste ICS Feed
    var clipboard = new ClipboardJS('#cmdCopy');
    $('#lnkICS').click(function () {
        $('#frmLinkICS').modal('show');
    });
    clipboard.on('success', function() {
        $('#tipCopied').tooltip('show');
        setTimeout(function() { $('#tipCopied').tooltip('hide'); }, 1000);
    });
});
</script>
