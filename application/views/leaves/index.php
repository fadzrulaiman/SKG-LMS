<?php
/**
 * This view displays the list of leave requests created by an employee.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since         0.1.0
 */
?>

<style>
.filter-label {
    margin-right: 10px;
}

.filter-label input {
    margin-right: 5px;
}

.table-container {
    margin-top: 20px;
}

#leaves_wrapper {
    margin-top: 20px;
}

.action-icons i {
    margin-right: 10px;
    cursor: pointer;
}

.modal-header h3 {
    margin: 0;
}

.modal-body p {
    margin-bottom: 15px;
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    margin: 10px 0;
}


#leaves thead th,
#leaves tbody td {
    text-align: center;
    vertical-align: middle;
}
</style>

<h2><?php echo lang('leaves_index_title');?> &nbsp;</h2>

<?php echo $flash_partial_view;?>

<div class="row">
    <div class="span3">
        <label for="cboLeaveType">
            <?php echo lang('leaves_index_thead_type');?>
            <select name="cboLeaveType" id="cboLeaveType">
                <option value="" selected></option>
                <?php foreach ($types as $type): ?>

                <?php if ($type['id'] != 0): ?>
                <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                <?php endif; ?>
                <?php endforeach; ?>

            </select>
        </label>

    </div>
    <div class="span1">&nbsp;</div>
    <div class="span8">
        <span class="label label-success"><input type="checkbox" checked id="chkAccepted" class="filterStatus">
            &nbsp;<?php echo lang('Accepted');?></span> &nbsp;
        <span class="label label-warning"><input type="checkbox" checked id="chkRequested" class="filterStatus">
            &nbsp;<?php echo lang('Requested');?></span> &nbsp;
        <span class="label label-warning"><input type="checkbox" checked id="chkleavebank" class="filterStatus">
            &nbsp;<?php echo lang('Pending From HR');?></span> &nbsp;
        <span class="label label-important" style="background-color: #ff0000;"><input type="checkbox" checked
                id="chkRejected" class="filterStatus"> &nbsp;<?php echo lang('Rejected');?></span> &nbsp;
        <span class="label label-important" style="background-color: #ff0000;"><input type="checkbox" checked
                id="chkCanceled" class="filterStatus"> &nbsp;<?php echo lang('Canceled');?></span>
    </div>
</div>

<div class="table-container">
    <table cellpadding="0" cellspacing="0" border="0" class="display" id="leaves" width="100%">
        <thead>
            <tr>
                <th><?php echo lang('leaves_index_thead_id');?></th>
                <th><?php echo lang('leaves_index_thead_start_date');?></th>
                <th><?php echo lang('leaves_index_thead_end_date');?></th>
                <th><?php echo lang('leaves_index_thead_cause');?></th>
                <th><?php echo lang('leaves_index_thead_duration');?></th>
                <th><?php echo lang('leaves_index_thead_type');?></th>
                <th><?php echo lang('leaves_index_thead_status');?></th>
                <?php
                if ($this->config->item('enable_history') == TRUE){
                    echo "<th>".lang('leaves_index_thead_requested_date')."</th>";
                    echo "<th>".lang('leaves_index_thead_last_change')."</th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaves as $leave):
            $datetimeStart = new DateTime($leave['startdate']);
            $tmpStartDate = $datetimeStart->getTimestamp();
            $startdate = $datetimeStart->format(lang('global_date_format'));
            $datetimeEnd = new DateTime($leave['enddate']);
            $tmpEndDate = $datetimeEnd->getTimestamp();
            $enddate = $datetimeEnd->format(lang('global_date_format'));
            if ($this->config->item('enable_history') == TRUE){
                if($leave['request_date'] == NULL){
                    $tmpRequestDate = "";
                    $requestdate = "";
                }else{
                    $datetimeRequested = new DateTime($leave['request_date']);
                    $tmpRequestDate = $datetimeRequested->getTimestamp();
                    $requestdate = $datetimeRequested->format(lang('global_date_format'));
                }
                if($leave['change_date'] == NULL){
                    $tmpLastChangeDate = "";
                    $lastchangedate = "";
                }else{
                    $datetimelastChanged = new DateTime($leave['change_date']);
                    $tmpLastChangeDate = $datetimelastChanged->getTimestamp();
                    $lastchangedate = $datetimelastChanged->format(lang('global_date_format'));
                }
            }?>
            <tr>
                <td data-order="<?php echo $leave['id']; ?>">
                    <a href="<?php echo base_url();?>leaves/leaves/<?php echo $leave['id']; ?>"
                        title="<?php echo lang('leaves_index_thead_tip_view');?>"><?php echo $leave['id']; ?></a>
                    &nbsp;
                    <div class="action-icons">
                        <?php
                        $showDelete = FALSE;
                        $showCancel = FALSE;
                        $showCancelByUser = FALSE;
                        $showEdit = FALSE;
                        $showReminder = FALSE;

                        if (($leave['status'] == LMS_PLANNED)) {
                            $showEdit = TRUE;
                        }
                        if (($leave['status'] == LMS_REJECTED) &&
                                ($this->config->item('edit_rejected_requests') === TRUE)) {
                            $showEdit = TRUE;
                        }
                        if ($leave['status'] == LMS_ACCEPTED) {
                            $showCancel = FALSE;
                        }
                        if ($leave['status'] == LMS_PLANNED) {
                            $showDelete = TRUE;
                        }
                        if (($leave['status'] == LMS_REJECTED) &&
                                ($this->config->item('delete_rejected_requests') === TRUE)) {
                            $showDelete = TRUE;
                        }
                        if (($leave['status'] == LMS_REQUESTED) ||
                                ($leave['status'] == LMS_CANCELLATION)) {
                            $showReminder = TRUE;
                        }
                        if (($leave['status'] == LMS_REQUESTED)) {
                            $showCancelByUser = TRUE;
                        }
                        ?>
                        <?php if ($showEdit == TRUE) { ?>
                        <a href="<?php echo base_url();?>leaves/edit/<?php echo $leave['id']; ?>"
                            title="<?php echo lang('leaves_index_thead_tip_edit');?>"><i
                                class="mdi mdi-pencil nolink"></i></a>
                        <?php } ?>
                        <?php if ($showDelete == TRUE) { ?>
                        <a href="#" class="confirm-delete" data-id="<?php echo $leave['id'];?>"
                            title="<?php echo lang('leaves_index_thead_tip_delete');?>"><i
                                class="mdi mdi-delete nolink"></i></a>
                        <?php } ?>
                        <?php if ($showCancel == TRUE) { ?>
                        <a href="<?php echo base_url();?>leaves/cancellation/<?php echo $leave['id']; ?>"
                            title="<?php echo lang('leaves_index_thead_tip_cancel');?>"><i
                                class="mdi mdi-undo nolink"></i></a>
                        <?php } ?>
                        <?php if ($showCancelByUser == TRUE) { ?>
                        <a href="<?php echo base_url();?>leaves/cancel/<?php echo $leave['id']; ?>"
                            title="<?php echo lang('leaves_index_thead_tip_cancel');?>"><i
                                class="mdi mdi-undo nolink"></i></a>
                        <?php } ?>
                        <?php if ($showReminder == TRUE) { ?>
                        <a href="<?php echo base_url();?>leaves/reminder/<?php echo $leave['id']; ?>"
                            title="<?php echo lang('leaves_button_send_reminder');?>"><i
                                class="mdi mdi-email nolink"></i></a>
                        <?php } ?>
                        <a href="<?php echo base_url();?>leaves/leaves/<?php echo $leave['id']; ?>"
                            title="<?php echo lang('leaves_index_thead_tip_view');?>"><i
                                class="mdi mdi-eye nolink"></i></a>
                        <?php if ($this->config->item('enable_history') === TRUE) { ?>
                        <a href="#" class="show-history" data-id="<?php echo $leave['id'];?>"
                            title="<?php echo lang('leaves_index_thead_tip_history');?>"><i
                                class="mdi mdi-history nolink"></i></a>
                        <?php } ?>
                    </div>
                </td>
                <td data-order="<?php echo $tmpStartDate; ?>"><?php echo $startdate?></td>
                <td data-order="<?php echo $tmpEndDate; ?>"><?php echo $enddate?></td>
                <td><?php echo $leave['cause']; ?></td>
                <td><?php echo $leave['duration']; ?> Days</td>
                <td><?php echo $leave['type_name']; ?></td>
                <?php
                switch ($leave['status']) {
                    case 1: echo "<td><span class='label'>" . lang($leave['status_name']) . "</span></td>"; break;
                    case 2: echo "<td><span class='label label-warning'>" . lang($leave['status_name']) . "</span></td>"; break;
                    case 3: echo "<td><span class='label label-success'>" . lang($leave['status_name']) . "</span></td>"; break;
                    case 7: echo "<td><span class='label label-warning'>" . lang($leave['status_name']) . "</span></td>"; break;
                    default: echo "<td><span class='label label-important' style='background-color: #ff0000;'>" . lang($leave['status_name']) . "</span></td>"; break;
                }?>
                <?php
                if ($this->config->item('enable_history') == TRUE){
                    echo "<td data-order='".$tmpRequestDate."'>" . $requestdate . "</td>";
                    echo "<td data-order='".$tmpLastChangeDate."'>" . $lastchangedate . "</td>";
                }
                ?>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<div class="row-fluid">
    <div class="span12">&nbsp;</div>
</div>

<div class="row-fluid">
    <div class="span12">
        <a href="<?php echo base_url();?>leaves/export" class="btn btn-primary"><i class="mdi mdi-download"></i>
            <?php echo lang('leaves_index_button_export');?></a>
        <a href="<?php echo base_url();?>leaves/create" class="btn btn-primary"><i class="mdi mdi-plus-circle"></i>
            <?php echo lang('leaves_index_button_create');?></a>
        <?php if ($this->config->item('ics_enabled') == TRUE) {?>
        <a id="lnkICS" href="#"><i class="mdi mdi-earth nolink"></i> ICS</a>
        <?php }?>
    </div>
</div>

<div class="row-fluid">
    <div class="span12">&nbsp;</div>
</div>

<div id="frmDeleteLeaveRequest" class="modal hide fade">
    <div class="modal-header">
        <a href="#" class="close">&times;</a>
        <h3><?php echo lang('leaves_index_popup_delete_title');?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo lang('leaves_index_popup_delete_message');?></p>
        <p><?php echo lang('leaves_index_popup_delete_question');?></p>
    </div>
    <div class="modal-footer">
        <a href="#" id="lnkDeleteUser"
            class="btn btn-danger"><?php echo lang('leaves_index_popup_delete_button_yes');?></a>
        <a href="#" onclick="$('#frmDeleteLeaveRequest').modal('hide');"
            class="btn"><?php echo lang('leaves_index_popup_delete_button_no');?></a>
    </div>
</div>

<div id="frmShowHistory" class="modal hide fade">
    <div class="modal-body" id="frmShowHistoryBody">
        <img src="<?php echo base_url();?>assets/images/loading.gif">
    </div>
    <div class="modal-footer">
        <a href="#" onclick="$('#frmShowHistory').modal('hide');" class="btn"><?php echo lang('OK');?></a>
    </div>
</div>

<div id="frmLinkICS" class="modal hide fade">
    <div class="modal-header">
        <h3>ICS<a href="#" onclick="$('#frmLinkICS').modal('hide');" class="close">&times;</a></h3>
    </div>
    <div class="modal-body" id="frmSelectDelegateBody">
        <div class='input-append'>
            <?php $icsUrl = base_url() . 'ics/individual/' . $user_id . '?token=' . $this->session->userdata('random_hash');?>
            <input type="text" class="input-xlarge" id="txtIcsUrl" onfocus="this.select();" onmouseup="return false;"
                value="<?php echo $icsUrl;?>" />
            <button id="cmdCopy" class="btn" data-clipboard-text="<?php echo $icsUrl;?>">
                <i class="mdi mdi-content-copy"></i>
            </button>
            <a href="#" id="tipCopied" data-toggle="tooltip" title="<?php echo lang('copied');?>" data-placement="right"
                data-container="#cmdCopy"></a>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#" onclick="$('#frmLinkICS').modal('hide');" class="btn btn-primary"><?php echo lang('OK');?></a>
    </div>
</div>

<link href="<?php echo base_url();?>assets/datatable/DataTables-1.10.11/css/jquery.dataTables.min.css" rel="stylesheet">
<script type="text/javascript"
    src="<?php echo base_url();?>assets/datatable/DataTables-1.10.11/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/bootbox.min.js"></script>

<script type="text/javascript">
var leaveTable = null;

function getURLParameter(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null,
        ''
    ])[1].replace(/\+/g, '%20')) || null;
}

function filterStatusColumn() {
    var filter = "^(";
    if ($('#chkAccepted').prop('checked')) filter += "<?php echo lang('Accepted');?>|";
    if ($('#chkRequested').prop('checked')) filter += "<?php echo lang('Requested');?>|";
    if ($('#chkRejected').prop('checked')) filter += "<?php echo lang('Rejected');?>|";
    if ($('#chkleavebank').prop('checked')) filter += "<?php echo lang('Pending From HR');?>|";
    if ($('#chkCanceled').prop('checked')) filter += "<?php echo lang('Canceled');?>|";
    filter = filter.slice(0, -1) + ")$";
    if (filter.indexOf('(') == -1) filter = 'nothing is selected';
    leaveTable.columns(6).search(filter, true, false).draw();
}

$(document).ready(function() {
    $('#frmDeleteLeaveRequest').alert();

    leaveTable = $('#leaves').DataTable({
        order: [
            [1, "desc"]
        ],
        language: {
            decimal: "<?php echo lang('datatable_sInfoThousands');?>",
            processing: "<?php echo lang('datatable_sProcessing');?>",
            search: "<?php echo lang('datatable_sSearch');?>",
            lengthMenu: "<?php echo lang('datatable_sLengthMenu');?>",
            info: "<?php echo lang('datatable_sInfo');?>",
            infoEmpty: "<?php echo lang('datatable_sInfoEmpty');?>",
            infoFiltered: "<?php echo lang('datatable_sInfoFiltered');?>",
            infoPostFix: "<?php echo lang('datatable_sInfoPostFix');?>",
            loadingRecords: "<?php echo lang('datatable_sLoadingRecords');?>",
            zeroRecords: "<?php echo lang('datatable_sZeroRecords');?>",
            emptyTable: "<?php echo lang('datatable_sEmptyTable');?>",
            paginate: {
                first: "<?php echo lang('datatable_sFirst');?>",
                previous: "<?php echo lang('datatable_sPrevious');?>",
                next: "<?php echo lang('datatable_sNext');?>",
                last: "<?php echo lang('datatable_sLast');?>"
            },
            aria: {
                sortAscending: "<?php echo lang('datatable_sSortAscending');?>",
                sortDescending: "<?php echo lang('datatable_sSortDescending');?>"
            }
        }
    });

    $('#frmDeleteLeaveRequest').on('show', function() {
        var link = "<?php echo base_url();?>leaves/delete/" + $(this).data('id');
        $("#lnkDeleteUser").attr('href', link);
    })

    $("#leaves tbody").on('click', '.confirm-delete', function() {
        var id = $(this).data('id');
        $('#frmDeleteLeaveRequest').data('id', id).modal('show');
    });

    $('#frmDeleteLeaveRequest').on('hidden', function() {
        $(this).removeData('modal');
    });
    <?php if ($this->config->item('enable_history') === TRUE) { ?>
    $('#frmShowHistory').on('hidden', function() {
        $("#frmShowHistoryBody").html('<img src="<?php echo base_url();?>assets/images/loading.gif">');
    });

    $("#leaves tbody").on('click', '.show-history', function() {
        $("#frmShowHistory").modal('show');
        $("#frmShowHistoryBody").load('<?php echo base_url();?>leaves/' + $(this).data('id') +
            '/history',
            function(response, status, xhr) {
                if (xhr.status == 401) {
                    $("#frmShowHistory").modal('hide');
                    bootbox.alert("<?php echo lang('global_ajax_timeout');?>", function() {
                        location.reload();
                    });
                }
            });
    });
    <?php } ?>

    var client = new ClipboardJS("#cmdCopy");
    $('#lnkICS').click(function() {
        $("#frmLinkICS").modal('show');
    });
    client.on("success", function() {
        $('#tipCopied').tooltip('show');
        setTimeout(function() {
            $('#tipCopied').tooltip('hide')
        }, 1000);
    });

    $('#cboLeaveType').on('change', function() {
        var leaveType = $("#cboLeaveType option:selected").text();
        if (leaveType != '') {
            leaveTable.columns(5).search("^" + leaveType + "$", true, false).draw();
        } else {
            leaveTable.columns(5).search("", true, false).draw();
        }
    });

    if (getURLParameter('type') != null) {
        var leaveType = $("#cboLeaveType option[value='" + getURLParameter('type') + "']").text();
        $("#cboLeaveType option[value='" + getURLParameter('type') + "']").prop("selected", true);
        leaveTable.columns(5).search("^" + leaveType + "$", true, false).draw();
    }

    var statuses = getURLParameter('statuses');
    if (statuses != null) {
        $(".filterStatus").prop("checked", false);
        statuses.split(/\|/).forEach(function(status) {
            switch (status) {
                case '1':
                    $("#chkPlanned").prop("checked", true);
                    break;
                case '2':
                    $("#chkRequested").prop("checked", true);
                    break;
                case '3':
                    $("#chkAccepted").prop("checked", true);
                    break;
                case '4':
                    $("#chkRejected").prop("checked", true);
                    break;
                case '5':
                    $("#chkleavebank").prop("checked", true);
                    break;
                case '6':
                    $("#chkCanceled").prop("checked", true);
                    break;
            }
        });
        filterStatusColumn();
    }
    $('.filterStatus').on('change', function() {
        filterStatusColumn();
    });
});
</script>