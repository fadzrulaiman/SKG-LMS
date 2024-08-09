<?php
/**
 * This view allows an employees (or HR admin) to modify a leave request
 * @since 0.1.0
 */
?>
<script type="text/javascript">
var existingAttachment = <?php echo !empty($leave['attachment']) ? 'true' : 'false'; ?>;
</script>

<h2><?php echo lang('leaves_edit_title'); ?><?php echo $leave['id']; ?> &nbsp;<span
        class="muted">(<?php echo $name ?>)</span></h2>
<?php
$attributes = array('id' => 'frmLeaveForm', 'enctype' => 'multipart/form-data');
if (isset($_GET['source'])) {
    echo form_open('leaves/edit/' . $id . '?source=' . $_GET['source'], $attributes);
} else {
    echo form_open('leaves/edit/' . $id, $attributes);
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <?php echo validation_errors(); ?>
            <label for="type">
                <?php echo lang('leaves_edit_field_type'); ?>
                &nbsp;<span class="muted"
                    id="lblCredit"><?php if (!is_null($credit)) { ?>(<?php echo $credit; ?>)<?php } ?></span>
            </label>
            <select class="form-control" name="type" id="type">
                <?php foreach ($types as $typeId => $TypeName): ?>
                <option value="<?php echo $typeId; ?>" <?php if ($typeId == $leave['type'])
                                       echo "selected"; ?>><?php echo $TypeName; ?></option>
                <?php endforeach ?>
            </select>

            <label for="viz_startdate"><?php echo lang('leaves_edit_field_start'); ?></label>
            <input type="text" name="viz_startdate" id="viz_startdate" value="<?php $date = new DateTime($leave['startdate']);
            echo $date->format(lang('global_date_format')); ?>" autocomplete="off" />
            <input type="hidden" name="startdate" id="startdate" value="<?php echo $leave['startdate']; ?>" />

            <input type="hidden" name="startdatetype" id="startdatetype"
                value="<?php echo $leave['startdatetype']; ?>" />

            <label for="viz_enddate"><?php echo lang('leaves_edit_field_end'); ?></label>
            <input type="text" name="viz_enddate" id="viz_enddate" value="<?php $date = new DateTime($leave['enddate']);
            echo $date->format(lang('global_date_format')); ?>" autocomplete="off" />
            <input type="hidden" name="enddate" id="enddate" value="<?php echo $leave['enddate']; ?>" />
            <input type="hidden" name="enddatetype" id="enddatetype" value="<?php echo $leave['enddatetype']; ?>" />

            <label for="duration"><?php echo lang('leaves_edit_field_duration'); ?> <span
                    id="tooltipDayOff"></span></label>
            <?php if ($this->config->item('disable_edit_leave_duration') == TRUE) { ?>
            <input type="text" name="duration" id="duration" value="<?php echo $leave['duration']; ?>" readonly />
            <?php } else { ?>
            <input type="text" name="duration" id="duration" value="<?php echo $leave['duration']; ?>" />
            <?php } ?>
            <div class="alert hide alert-error" id="lblCreditAlert">
                <button type="button" class="close">&times;</button>
                <?php echo lang('leaves_edit_field_duration_message'); ?>
            </div>

            <div class="alert hide alert-error" id="lblOverlappingAlert" onclick="$('#lblOverlappingAlert').hide();">
                <button type="button" class="close">&times;</button>
                <?php echo lang('leaves_create_field_overlapping_message'); ?>
            </div>

            <div class="alert hide alert-error" id="lblOverlappingDayOffAlert"
                onclick="$('#lblOverlappingDayOffAlert').hide();">
                <button type="button" class="close">&times;</button>
                <?php echo lang('leaves_flash_msg_overlap_dayoff'); ?>
            </div>

            <label for="editattachment">Edit Attachment</label>
            <?php if (!empty($leave['attachment'])): ?>
            <div class="attachment">
                <h3>Current Attachment:</h3>
                <a href="<?php echo base_url($leave['attachment']); ?>" target="_blank">View Attachment</a>
            </div>
            <?php endif; ?>
            <input type="file" name="editattachment" id="editattachment" accept="image/*, .pdf">

            <label for="cause"><?php echo lang('leaves_edit_field_cause'); ?></label>
            <textarea name="cause"><?php echo $leave['cause']; ?></textarea>
            <br />
            <?php $style = "dropdown-rejected";
            switch ($leave['status']) {
                case LMS_PLANNED:
                    $style = "dropdown-planned";
                    break;
                case LMS_REQUESTED:
                    $style = "dropdown-requested";
                    break;
                case LMS_ACCEPTED:
                    $style = "dropdown-accepted";
                    break;
                default:
                    $style = "dropdown-rejected";
                    break;
            } ?>
            <?php if ($is_hr) { ?>
            <label for="status"><?php echo lang('leaves_edit_field_status'); ?></label>
            <select name="status" class="form-control <?php echo $style; ?>">
                <option value="2" <?php if (($leave['status'] == LMS_REQUESTED) || $this->config->item('leave_status_requested'))
                                    echo 'selected'; ?>><?php echo lang('Requested'); ?></option>
                <option value="3" <?php if ($leave['status'] == LMS_ACCEPTED)
                                    echo 'selected'; ?>><?php echo lang('Accepted'); ?></option>
                <option value="4" <?php if ($leave['status'] == LMS_REJECTED)
                                    echo 'selected'; ?>><?php echo lang('Rejected'); ?></option>
                <option value="5" <?php if ($leave['status'] == LMS_CANCELLATION)
                                    echo 'selected'; ?>><?php echo lang('Cancellation'); ?></option>
                <option value="6" <?php if ($leave['status'] == LMS_CANCELED)
                                    echo 'selected'; ?>><?php echo lang('Canceled'); ?></option>
            </select>
            <?php } else { ?>
            <label for="status"><?php echo lang('leaves_edit_field_status'); ?></label>
            <select name="status" class="form-control <?php echo $style; ?>">
                <option value="2" <?php if (($leave['status'] == LMS_REQUESTED) || $this->config->item('leave_status_requested'))
                                    echo 'selected'; ?>><?php echo lang('Requested'); ?></option>
            </select>
            <br />
            <button name="status" value="1" type="submit" class="btn btn-primary"><i class="mdi mdi-calendar-question"
                    aria-hidden="true"></i>&nbsp; <?php echo lang('Planned'); ?></button>
            &nbsp;&nbsp;
            <button name="status" value="2" type="submit" class="btn btn-primary "><i class="mdi mdi-check"></i>&nbsp;
                <?php echo lang('Requested'); ?></button>
            <br />
            <?php } ?>
            <br />

            <?php if ($is_hr) { ?>
            <button type="submit" class="btn btn-primary"><i
                    class="mdi mdi-check"></i>&nbsp;<?php echo lang('leaves_edit_button_update'); ?></button>&nbsp;
            <?php } ?>

            <?php if (isset($_GET['source'])) { ?>
            <a href="<?php echo base_url() . $_GET['source']; ?>" class="btn btn-danger"><i
                    class="mdi mdi-close"></i>&nbsp;<?php echo lang('leaves_edit_button_cancel'); ?></a>
            <?php } else { ?>
            <a href="<?php echo base_url(); ?>leaves" class="btn btn-danger"><i
                    class="mdi mdi-close"></i>&nbsp;<?php echo lang('leaves_edit_button_cancel'); ?></a>
            <?php } ?>

        </div>
    </div>
</div>
</form>
<div class="modal hide" id="frmModalAjaxWait" data-backdrop="static" data-keyboard="false">
    <div class="modal-header">
        <h1><?php echo lang('global_msg_wait'); ?></h1>
    </div>
    <div class="modal-body">
        <img src="<?php echo base_url(); ?>assets/images/loading.gif" align="middle">
    </div>
</div>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/flick/jquery-ui.custom.min.css">
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.custom.min.js"></script>
<?php //Prevent HTTP-404 when localization isn't needed
if ($language_code != 'en') { ?>
<script src="<?php echo base_url(); ?>assets/js/i18n/jquery.ui.datepicker-<?php echo $language_code; ?>.js"></script>
<?php } ?>
<script src="<?php echo base_url(); ?>assets/js/bootbox.min.js"></script>

<?php require_once dirname(BASEPATH) . "/local/triggers/leave_view.php"; ?>

<script>
$(document).on("click", "#showNoneWorkedDay", function(e) {
    showListDayOffHTML();
});
</script>
<script type="text/javascript">
var baseURL = '<?php echo base_url(); ?>';
var userId = <?php echo $leave['employee']; ?>;
var leaveId = <?php echo $leave['id']; ?>;
var languageCode = '<?php echo $language_code; ?>';
var dateJsFormat = '<?php echo lang('global_date_js_format'); ?>';
var dateMomentJsFormat = '<?php echo lang('global_date_momentjs_format'); ?>';

var noContractMsg = "<?php echo lang('leaves_validate_flash_msg_no_contract'); ?>";
var noTwoPeriodsMsg = "<?php echo lang('leaves_validate_flash_msg_overlap_period'); ?>";

var overlappingWithDayOff = "<?php echo lang('leaves_flash_msg_overlap_dayoff'); ?>";
var listOfDaysOffTitle = "<?php echo lang('leaves_flash_spn_list_days_off'); ?>";

function validate_form() {
    var fieldname = "";

    //Call custom trigger defined into local/triggers/leave.js
    if (typeof triggerValidateEditForm == 'function') {
        if (triggerValidateEditForm() == false) return false;
    }

    if ($('#viz_startdate').val() == "") fieldname = "<?php echo lang('leaves_edit_field_start'); ?>";
    if ($('#viz_enddate').val() == "") fieldname = "<?php echo lang('leaves_edit_field_end'); ?>";
    if ($('#duration').val() == "" || $('#duration').val() == 0) fieldname =
        "<?php echo lang('leaves_edit_field_duration'); ?>";
    if (fieldname == "") {
        return true;
    } else {
        bootbox.alert(<?php echo lang('leaves_validate_mandatory_js_msg'); ?>);
        return false;
    }
}

<?php if ($this->config->item('csrf_protection') == TRUE) { ?>
$(function() {
    $.ajaxSetup({
        data: {
            <?php echo $this->security->get_csrf_token_name(); ?>: "<?php echo $this->security->get_csrf_hash(); ?>",
        }
    });
});
<?php } ?>

//Disallow the use of negative symbols (through a whitelist of symbols)
function keyAllowed(key) {
    var keys = [8, 9, 13, 16, 17, 18, 19, 20, 27, 46, 48, 49, 50,
        51, 52, 53, 54, 55, 56, 57, 91, 92, 93
    ];
    if (key && keys.indexOf(key) === -1)
        return false;
    else
        return true;
}

$(function() {
    //Selectize the leave type combo
    $('#type').select2();

    //On opening, refresh leave request information
    refreshLeaveInfo();

    <?php if ($this->config->item('disallow_requests_without_credit') == TRUE) { ?>
    var durationField = document.getElementById("duration");
    durationField.setAttribute("min", "0");
    durationField.addEventListener('keypress', function(e) {
        var key = !isNaN(e.charCode) ? e.charCode : e.keyCode;
        if (!keyAllowed(key))
            e.preventDefault();
    }, false);

    // Disable pasting of non-numbers
    durationField.addEventListener('paste', function(e) {
        var pasteData = e.clipboardData.getData('text/plain');
        if (pasteData.match(/[^0-9]/))
            e.preventDefault();
    }, false);
    <?php } ?>
});

$(document).on("click", "#showNoneWorkedDay", function(e) {
    showListDayOffHTML();
});

function toggleAttachmentRequired() {
    const leaveType = $('#type').val();
    const attachmentField = $('#editattachment');

    if (leaveType === '2' && !existingAttachment) { // Sick Leave and no existing attachment
        attachmentField.prop('required', true);
    } else {
        attachmentField.prop('required', false);
    }

    if (leaveType === '2') { // Sick Leave
        const currentDate = moment();
        $("#viz_startdate").datepicker("option", {
            maxDate: currentDate.clone().add(7, 'days').toDate(),
            minDate: null // No limit for past dates
        });
    } else {
        $("#viz_startdate").datepicker("option", {
            maxDate: null,
            minDate: null
        });
    }
}

$('#type').change(toggleAttachmentRequired);
$(document).ready(toggleAttachmentRequired);

function refreshLeaveInfo() {
    $('#frmModalAjaxWait').modal('show');
    $.ajax({
            type: "POST",
            url: baseURL + "leaves/validate",
            data: {
                id: userId,
                type: $("#type option:selected").text(),
                startdate: $('#startdate').val(),
                enddate: $('#enddate').val(),
                startdatetype: $('#startdatetype').val(),
                enddatetype: $('#enddatetype').val(),
                leave_id: leaveId
            }
        })
        .done(function(leaveInfo) {
            showOverlappingMessage(leaveInfo);
            showOverlappingDayOffMessage(leaveInfo);
            showListDayOff(leaveInfo);
            $('#frmModalAjaxWait').modal('hide');
        });
}

function showListDayOff(leaveInfo) {
    if (leaveInfo.listDaysOff !== undefined) {
        const daysOffHTML = generateDaysOffHTML(leaveInfo.listDaysOff);
        $("#spnDaysOffList").html(daysOffHTML.tooltip);
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    }
}

function generateDaysOffHTML(daysOff) {
    const htmlTable = `
        <a href='#divDaysOff' data-toggle='collapse' class='btn btn-primary input-block-level'>
            ${listOfDaysOffTitle.replace("%s", daysOff.length)}
            &nbsp;<i class='icon-chevron-down icon-white'></i>
        </a>
        <div id='divDaysOff' class='collapse'>
            <table class='table table-bordered table-hover table-condensed'>
                <tbody>${daysOff.map(day => `
                    <tr>
                        <td>${moment(day.date, 'YYYY-MM-DD').format(dateMomentJsFormat)} / <b>${day.title}</b></td>
                        <td>${day.length}</td>
                    </tr>`).join('')}
                </tbody>
            </table>
        </div>`;
    const tooltip =
        `<a href='#' id='showNoneWorkedDay' data-toggle='tooltip' data-placement='right' title='${listOfDaysOffTitle.replace("%s", daysOff.length)}'><i class='icon-info-sign'></i></a>`;
    return {
        htmlTable,
        tooltip
    };
}

function showOverlappingMessage(leaveInfo) {
    $("#lblOverlappingAlert").toggle(Boolean(leaveInfo.overlap));
}

function showOverlappingDayOffMessage(leaveInfo) {
    $("#lblOverlappingDayOffAlert").toggle(Boolean(leaveInfo.overlapDayOff));
}

$(function() {
    getLeaveLength(false);

    const datePickerOptions = {
        changeMonth: true,
        changeYear: true,
        dateFormat: dateJsFormat,
        altFormat: "yy-mm-dd",
        numberOfMonths: 1,
    };

    $("#viz_startdate").datepicker({
        ...datePickerOptions,
        altField: "#startdate",
        onClose: function(selectedDate) {
            $("#viz_enddate").datepicker("option", "minDate", selectedDate);
        }
    });

    $("#viz_enddate").datepicker({
        ...datePickerOptions,
        altField: "#enddate",
        onClose: function(selectedDate) {
            $("#viz_startdate").datepicker("option", "maxDate", selectedDate);
        }
    });

    $("#days").keyup(function() {
        const value = $("#days").val().replace(",", ".");
        $("#days").val(value);
    });

    $('#viz_startdate, #viz_enddate, #startdatetype, #enddatetype, #type').change(getLeaveInfos.bind(null,
        false));
    $("#duration").keyup(getLeaveInfos.bind(null, true));

    $("#frmLeaveForm").submit(function(e) {
        if (!validate_form()) {
            e.preventDefault();
        }
    });
});
</script>