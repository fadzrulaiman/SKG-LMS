<?php
/**
 * This view displays the list of leave requests submitted to a manager.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since 0.1.0
 */
?>
<h2><?php echo lang('leavebankrequests_index_title');?></h2>

<?php echo $flash_partial_view;?>

<p><?php echo lang('leavebankrequests_index_description');?></p>

<table cellpadding="0" cellspacing="0" border="0" class="display" id="leaves" width="100%">
    <thead>
        <tr>
            <th><?php echo lang('requests_index_thead_id');?></th>
            <th><?php echo lang('requests_index_thead_fullname');?></th>
            <th><?php echo lang('requests_index_thead_staffno');?></th>
            <th><?php echo lang('requests_index_thead_startdate');?></th>
            <th><?php echo lang('requests_index_thead_enddate');?></th>
            <th><?php echo lang('requests_index_thead_duration');?></th>
            <th><?php echo lang('requests_index_thead_type');?></th>
            <th><?php echo lang('requests_index_thead_status');?></th>
            <?php if ($this->config->item('enable_history') == TRUE){?>
            <th><?php echo lang('requests_index_thead_requested_date');?></th>
            <th><?php echo lang('requests_index_thead_last_change');?></th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $request):
            $date = new DateTime($request['startdate']);
            $tmpStartDate = $date->getTimestamp();
            $startdate = $date->format(lang('global_date_format'));
            $date = new DateTime($request['enddate']);
            $tmpEndDate = $date->getTimestamp();
            $enddate = $date->format(lang('global_date_format'));
            if ($this->config->item('enable_history') == TRUE){
              if($request['request_date'] == NULL){
                $tmpRequestDate = "";
                $requestdate = "";
              }else{
                $datetimeRequested = new DateTime($request['request_date']);
                $tmpRequestDate = $datetimeRequested->getTimestamp();
                $requestdate = $datetimeRequested->format(lang('global_date_format'));
              }
              if($request['change_date'] == NULL){
                $tmpLastChangeDate = "";
                $lastchangedate = "";
              }else{
                $datetimelastChanged = new DateTime($request['change_date']);
                $tmpLastChangeDate = $datetimelastChanged->getTimestamp();
                $lastchangedate = $datetimelastChanged->format(lang('global_date_format'));
              }
            }
            ?>
        <tr>
            <td data-order="<?php echo $request['leave_id']; ?>" class="text-center">
                <a href="<?php echo base_url();?>leaves/requests/<?php echo $request['leave_id']; ?>"
                    title="<?php echo lang('requests_index_thead_tip_view');?>"><?php echo $request['leave_id']; ?></a>
                &nbsp;
                <div class="pull-right">
                    <?php if ($request['status'] == LMS_CANCELLATION) { ?>
                    <a href="#" class="lnkCancellationAccept" data-id="<?php echo $request['leave_id']; ?>"
                        title="<?php echo lang('requests_index_thead_tip_accept');?>"><i
                            class="mdi mdi-check nolink"></i></a>
                    &nbsp;
                    <a href="#" class="lnkCancellationReject" data-id="<?php echo $request['leave_id']; ?>"
                        title="<?php echo lang('requests_index_thead_tip_reject');?>"><i
                            class="mdi mdi-close nolink"></i></a>
                    <?php } else if ($request['status'] == LMS_REQUESTEDBANK) { ?>
                    <a href="#" class="lnkAccept" data-id="<?php echo $request['leave_id']; ?>"
                        title="<?php echo lang('requests_index_thead_tip_accept'); ?>"><i
                            class="mdi mdi-check nolink"></i></a>
                    &nbsp;
                    <a href="#" class="lnkReject" data-id="<?php echo $request['leave_id']; ?>"
                        title="<?php echo lang('requests_index_thead_tip_reject');?>"><i
                            class="mdi mdi-close nolink"></i></a>
                    <?php } ?>
                    <?php if ($this->config->item('enable_history') === TRUE) { ?>
                    &nbsp;
                    <a href="<?php echo base_url();?>leaves/leaves/<?php echo $request['leave_id']; ?>"
                        title="<?php echo lang('requests_index_thead_tip_view');?>"><i
                            class="mdi mdi-eye nolink"></i></a>
                    &nbsp;
                    <a href="#" class="show-history" data-id="<?php echo $request['leave_id'];?>"
                        title="<?php echo lang('requests_index_thead_tip_history');?>"><i
                            class="mdi mdi-history nolink"></i></a>
                    <?php } ?>
                </div>
            </td>
            <td class="text-center"><?php echo $request['firstname'] . ' ' . $request['lastname']; ?></td>
            <td class="text-center"><?php echo $request['employee']; ?></td>
            <td data-order="<?php echo $tmpStartDate; ?>" class="text-center">
                <?php echo $startdate /*. ' (' . lang($request['startdatetype']). ')';*/ ?></td>
            <td data-order="<?php echo$tmpEndDate; ?>" class="text-center">
                <?php echo $enddate /*. ' (' . lang($request['enddatetype']) . ')';*/ ?></td>

            <td class="text-center"><?php echo $request['duration']; ?></td>

            <td class="text-center"><?php echo $request['type_name']; ?></td>
            <?php
        switch ($request['status']) {
            case 1: echo "<td class='text-center'><span class='label'>" . lang($request['status_name']) . "</span></td>"; break;
            case 2: echo "<td class='text-center'><span class='label label-warning'>" . lang($request['status_name']) . "</span></td>"; break;
            case 3: echo "<td class='text-center'><span class='label label-success'>" . lang($request['status_name']) . "</span></td>"; break;
            case 7: echo "<td class='text-center'><span class='label label-warning'>" . lang($request['status_name']) . "</span></td>"; break;
            default: echo "<td class='text-center'><span class='label label-important' style='background-color: #ff0000;'>" . lang($request['status_name']) . "</span></td>"; break;
        }?>
            <?php
        if ($this->config->item('enable_history') == TRUE){
          echo "<td data-order='".$tmpRequestDate."' class='text-center'>" . $requestdate . "</td>";
          echo "<td data-order='".$tmpLastChangeDate."' class='text-center'>" . $lastchangedate . "</td>";
        }
        ?>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<div class="row-fluid">
    <div class="span12 text-center">
        <form action="<?php echo base_url('requests/leavebankapproveAll'); ?>" method="post" style="display:inline;">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
            <button type="submit" class="btn btn-success"><?php echo lang('requests_index_approve_all'); ?></button>
        </form>
        &nbsp;&nbsp;
        <a href="<?php echo base_url();?>requests/leavebank/export/<?php echo $filter; ?>" class="btn btn-primary"><i
                class="mdi mdi-download"></i>&nbsp; <?php echo lang('requests_index_button_export');?></a>
        &nbsp;&nbsp;
        <a href="<?php echo base_url();?>requests/leavebank/allleavebank" class="btn btn-primary"><i
                class="mdi mdi-filter-remove"></i>&nbsp; <?php echo lang('requests_index_button_show_all');?></a>
        &nbsp;&nbsp;
        <a href="<?php echo base_url();?>requests/leavebank/leavebankrequested" class="btn btn-primary"><i
                class="mdi mdi-filter"></i>&nbsp; <?php echo lang('requests_index_button_show_pending');?></a>
        &nbsp;&nbsp;
        <?php if ($this->config->item('ics_enabled') == TRUE) {?>
        <a id="lnkICS" href="#"><i class="mdi mdi-earth nolink"></i> ICS</a>
        <?php }?>
        &nbsp;&nbsp;
    </div>
</div>

<div class="row-fluid">
    <div class="span12">&nbsp;</div>
</div>

<div id="frmShowHistory" class="modal hide fade">
    <div class="modal-body" id="frmShowHistoryBody">
        <img src="<?php echo base_url();?>assets/images/loading.gif">
    </div>
    <div class="modal-footer">
        <a href="#" onclick="$('#frmShowHistory').modal('hide');" class="btn"><?php echo lang('OK');?></a>
    </div>
</div>

<div class="modal fade" id="frmModalAjaxWait" tabindex="-1" role="dialog" aria-labelledby="loadingLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h5 class="modal-title w-100" id="loadingLabel"><?php echo lang('global_msg_wait'); ?></h5>
            </div>
            <div class="modal-body text-center">
                <img src="<?php echo base_url(); ?>assets/images/loading.gif" alt="Loading..." class="img-fluid mb-3">
                <p><?php echo lang('global_msg_wait_process'); ?></p>
            </div>
        </div>
    </div>
</div>

<div id="frmLinkICS" class="modal hide fade">
    <div class="modal-header">
        <h3>ICS<a href="#" onclick="$('#frmLinkICS').modal('hide');" class="close">&times;</a></h3>
    </div>
    <div class="modal-body" id="frmSelectDelegateBody">
        <div class='input-append'>
            <?php $icsUrl = base_url() . 'ics/collaborators/' . $user_id . '?token=' . $this->session->userdata('random_hash');?>
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
<div id="sendComment">
    <?php
    echo form_open("requests/", array('id' => 'frmRejectLeaveForm'))
  ?>
    <input id="comment" type="hidden" name="comment" value="">
    </form>
</div>


<link href="<?php echo base_url();?>assets/datatable/DataTables-1.10.11/css/jquery.dataTables.min.css" rel="stylesheet">
<script type="text/javascript"
    src="<?php echo base_url();?>assets/datatable/DataTables-1.10.11/js/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url();?>assets/js/bootbox.min.js"></script>

<script type="text/javascript">
var clicked = false;
var leaveTable = null;

// Return a URL parameter identified by 'name'
function getURLParameter(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null,
        ''
    ])[1].replace(/\+/g, '%20')) || null;
}

// Apply a filter on the status column
function filterStatusColumn() {
    var filter = "^(";
    if ($('#chkAccepted').prop('checked')) filter += "<?php echo lang('Accepted');?>|";
    if ($('#chkRequested').prop('checked')) filter += "<?php echo lang('Requested');?>|";
    if ($('#chkRejected').prop('checked')) filter += "<?php echo lang('Rejected');?>|";
    if ($('#chkleavebank').prop('checked')) filter += "<?php echo lang('Pending From HR');?>|";
    if ($('#chkCanceled').prop('checked')) filter += "<?php echo lang('Canceled');?>|";
    filter = filter.slice(0, -1) + ")$";
    if (filter.indexOf('(') == -1) filter = 'nothing is selected';
    leaveTable.columns(7).search(filter, true, false).draw();
}

$(document).ready(function() {
    // Transform the HTML table into a fancy DataTable
    leaveTable = $('#leaves').DataTable({
        order: [
            [3, "desc"]
        ],
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

    // Prevent double click on accept and reject buttons
    $('#leaves').on('click', '.lnkAccept, .lnkBankAccept', function(event) {
        event.preventDefault();
        if (!clicked) {
            clicked = true;

            // Show waiting modal
            $('#frmModalAjaxWait').modal('show');

            var url = $(this).hasClass('lnkBankAccept') ?
                "<?php echo base_url(); ?>requests/leavebankaccept/" :
                "<?php echo base_url(); ?>requests/accept/";
            window.location.href = url + $(this).data("id");
        }
    });

    $('#leaves').on('click', '.lnkReject', function(event) {
        event.preventDefault();
        if (!clicked) {
            clicked = true;
            var validateUrl = "<?php echo base_url();?>requests/reject/" + $(this).data("id");
            bootbox.prompt('<?php echo (($this->config->item('mandatory_comment_on_reject') === TRUE)?'<i class="mdi mdi-alert"></i>&nbsp;':'') .
                    lang('requests_comment_reject_request_title');?>',
                '<?php echo lang('requests_comment_reject_request_button_cancel');?>',
                '<?php echo lang('requests_comment_reject_request_button_reject');?>',
                function(result) {
                    if (result !== null) {
                        <?php if ($this->config->item('mandatory_comment_on_reject') === TRUE) { ?>
                        if (result === "") return false;
                        <?php } ?>
                        $("#sendComment #frmRejectLeaveForm").attr("action", validateUrl);
                        $("#sendComment #frmRejectLeaveForm input#comment").attr("value", result);
                        $("#sendComment #frmRejectLeaveForm").submit();
                        // Show waiting modal
                        $('#frmModalAjaxWait').modal('show');
                    } else {
                        clicked = false;
                    }
                });
        }
    });

    // Approve all action
    $('form[action$="requests/leavebankapproveAll"]').on('submit', function() {
        // Show waiting modal
        $('#frmModalAjaxWait').modal('show');
        return true; // Allow form submission
    });

    $('#leaves').on('click', '.lnkCancellationAccept', function(event) {
        event.preventDefault();
        if (!clicked) {
            clicked = true;
            window.location.href = "<?php echo base_url(); ?>requests/cancellation/accept/" + $(this)
                .data("id");
        }
    });

    $("#leaves").on('click', '.lnkCancellationReject', function(event) {
        event.preventDefault();
        if (!clicked) {
            clicked = true;
            var validateUrl = "<?php echo base_url(); ?>requests/cancellation/reject/" + $(this).data(
                "id");
            bootbox.prompt('<?php echo (($this->config->item('mandatory_comment_on_reject') === TRUE)?'<i class="mdi mdi-alert"></i>&nbsp;':'') .
                    lang('requests_comment_reject_request_title');?>',
                '<?php echo lang('requests_comment_reject_request_button_cancel');?>',
                '<?php echo lang('requests_comment_reject_request_button_reject');?>',
                function(result) {
                    if (result !== null) {
                        <?php if ($this->config->item('mandatory_comment_on_reject') === TRUE) { ?>
                        if (result === "") return false;
                        <?php } ?>
                        $("#sendComment #frmRejectLeaveForm").attr("action", validateUrl);
                        $("#sendComment #frmRejectLeaveForm input#comment").attr("value", result);
                        $("#sendComment #frmRejectLeaveForm").submit();
                    } else {
                        clicked = false;
                    }
                });
        }
    });

    <?php if ($this->config->item('enable_history') === TRUE) { ?>
    // Prevent to load always the same content (refreshed each time)
    $('#frmShowHistory').on('hidden', function() {
        $("#frmShowHistoryBody").html('<img src="<?php echo base_url(); ?>assets/images/loading.gif">');
    });

    // Popup show history
    $("#leaves tbody").on('click', '.show-history', function() {
        $("#frmShowHistory").modal('show');
        $("#frmShowHistoryBody").load('<?php echo base_url(); ?>leaves/' + $(this).data('id') +
            '/history');
    });
    <?php } ?>

    // Copy/Paste ICS Feed
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
            leaveTable.columns(6).search("^" + leaveType + "$", true, false).draw();
        } else {
            leaveTable.columns(6).search("", true, false).draw();
        }
    });

    // Analyze URL to get the filter on one type
    if (getURLParameter('type') != null) {
        var leaveType = $("#cboLeaveType option[value='" + getURLParameter('type') + "']").text();
        $("#cboLeaveType option[value='" + getURLParameter('type') + "']").prop("selected", true);
        leaveTable.columns(6).search("^" + leaveType + "$", true, false).draw();
    }

    // Filter on statuses is a list of inclusion
    var statuses = getURLParameter('statuses');
    if (statuses != null) {
        // Unselect all statuses and select only the statuses passed by URL
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
                case '7':
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

<style>
.table thead th {
    text-align: center;
    vertical-align: middle;
}

.table tbody td {
    text-align: center;
    vertical-align: middle;
}

.form-control {
    width: auto;
    display: inline-block;
}

.modal-header h3 {
    margin: 0;
    line-height: 1.42857143;
}

.modal-body .input-append {
    display: flex;
    align-items: center;
}

.modal-body .input-append input {
    margin-right: 10px;
}

.text-center {
    text-align: center;
}
</style>