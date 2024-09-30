<?php
/**
 * This view allows a manager (if the option is activated) or HR admin to a leave request in lieu of an employee.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since         0.2.0
 */
?>

<h2><?php echo lang('hr_leaves_create_title');?></h2>

<div class="row-fluid">
    <div class="span8">

<?php echo validation_errors(); ?>

<?php 
$attributes = array('id' => 'frmLeaveForm', 'enctype' => 'multipart/form-data', 'onsubmit' => 'disableSubmitButton()');
echo form_open($form_action, $attributes) ?>
    <label for="type" required>
        <?php echo lang('hr_leaves_create_field_type');?>
        &nbsp;<span class="muted" id="lblCredit"><?php echo '(' . $credit . ')'; ?></span>
    </label>
    <select class="input-xxlarge" name="type" id="type">
    <?php foreach ($types as $typeId => $TypeName): ?>
        <option value="<?php echo $typeId; ?>" <?php if ($typeId == $defaultType) echo "selected"; ?>><?php echo $TypeName; ?></option>
    <?php endforeach ?>
    </select>

    <label for="viz_startdate" required><?php echo lang('hr_leaves_create_field_start');?></label>
    <input type="text" name="viz_startdate" id="viz_startdate" value="<?php echo set_value('startdate'); ?>" />
    <input type="hidden" name="startdate" id="startdate" />
    <input type="hidden" value="Morning" name="startdatetype" id="startdatetype" />

    <label for="viz_enddate" required><?php echo lang('hr_leaves_create_field_end');?></label>
    <input type="text" name="viz_enddate" id="viz_enddate" value="<?php echo set_value('enddate'); ?>" />
    <input type="hidden" name="enddate" id="enddate" />
    <input type="hidden" value="Afternoon" name="enddatetype" id="enddatetype" />

    <label for="duration" required><?php echo lang('hr_leaves_create_field_duration');?> <span id="tooltipDayOff"></span></label>
    <input type="text" name="duration" id="duration" value="<?php echo set_value('duration'); ?>" />

    <div class="alert hide alert-error" id="lblCreditAlert" onclick="$('#lblCreditAlert').hide();">
        <button type="button" class="close">&times;</button>
        <?php echo lang('hr_leaves_create_field_duration_message');?>
    </div>

    <div class="alert hide alert-error" id="lblOverlappingAlert" onclick="$('#lblOverlappingAlert').hide();">
        <button type="button" class="close">&times;</button>
        <?php echo lang('hr_leaves_create_field_overlapping_message');?>
    </div>

    <div class="alert hide alert-error" id="lblOverlappingDayOffAlert" onclick="$('#lblOverlappingDayOffAlert').hide();">
        <button type="button" class="close">&times;</button>
        <?php echo lang('hr_leaves_flash_msg_overlap_dayoff');?>
    </div>

    <label for="cause"><?php echo lang('hr_leaves_create_field_cause');?></label>
    <textarea name="cause"><?php echo set_value('cause'); ?></textarea>

    <label for="attachment">Attachment</label>
    <input type="file" name="attachment" id="attachment" accept="image/*, .pdf">

    <label for="status" required><?php echo lang('hr_leaves_create_field_status');?></label>
    <select name="status">
        <option value="2" <?php if ($this->config->item('leave_status_requested') == TRUE) echo 'selected'; ?>><?php echo lang('Requested');?></option>
        <option value="3"><?php echo lang('Accepted');?></option>
        <option value="4"><?php echo lang('Rejected');?></option>
        <option value="5"><?php echo lang('Cancellation');?></option>
        <option value="6"><?php echo lang('Canceled');?></option>
    </select><br />

    <button name="request" type="submit" class="btn btn-primary"><i class="mdi mdi-check"></i>&nbsp; <?php echo lang('hr_leaves_create_button_create');?></button>
    &nbsp;
    <a href="<?php echo base_url() . $source; ?>" class="btn btn-danger"><i class="mdi mdi-close"></i>&nbsp; <?php echo lang('hr_leaves_create_button_cancel');?></a>
</form>

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

<!-- Custom JavaScript -->
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/lms/leave.edit-0.7.0.js"></script>
<script src="<?php echo base_url();?>assets/js/jquery-ui.custom.min.js"></script>
<link rel="stylesheet" href="<?php echo base_url();?>assets/css/flick/jquery-ui.custom.min.css">

<script>
$(document).ready(function() {
    // Initialize date pickers
    $("#viz_startdate").datepicker({
        dateFormat: '<?php echo lang('global_date_js_format');?>',
        altFormat: "yy-mm-dd",
        altField: "#startdate",
        onClose: function(selectedDate) {
            $("#viz_enddate").datepicker("option", "minDate", selectedDate);
        }
    });
    $("#viz_enddate").datepicker({
        dateFormat: '<?php echo lang('global_date_js_format');?>',
        altFormat: "yy-mm-dd",
        altField: "#enddate",
        onClose: function(selectedDate) {
            $("#viz_startdate").datepicker("option", "maxDate", selectedDate);
        }
    });
});

$(document).on("click", "#showNoneWorkedDay", function(e) {
    showListDayOffHTML();
});

<?php if ($this->config->item('csrf_protection') == TRUE) {?>
$(function () {
    $.ajaxSetup({
        data: {
            <?php echo $this->security->get_csrf_token_name();?>: "<?php echo $this->security->get_csrf_hash();?>",
        }
    });
});
<?php }?>
var baseURL = '<?php echo base_url();?>';
var userId = <?php echo $employee; ?>;
var leaveId = null;
var languageCode = '<?php echo $language_code;?>';
var dateJsFormat = '<?php echo lang('global_date_js_format');?>';
var dateMomentJsFormat = '<?php echo lang('global_date_momentjs_format');?>';

var noContractMsg = "<?php echo lang('hr_leaves_validate_flash_msg_no_contract');?>";
var noTwoPeriodsMsg = "<?php echo lang('hr_leaves_validate_flash_msg_overlap_period');?>";

var overlappingWithDayOff = "<?php echo lang('hr_leaves_flash_msg_overlap_dayoff');?>";
var listOfDaysOffTitle = "<?php echo lang('hr_leaves_flash_spn_list_days_off');?>";

function validate_form() {
    var fieldname = "";

    //Call custom trigger defined into local/triggers/leave.js
    if (typeof triggerValidateCreateForm == 'function') {
       if (triggerValidateCreateForm() == false) return false;
    }

    if ($('#viz_startdate').val() == "") fieldname = "<?php echo lang('hr_leaves_create_field_start');?>";
    if ($('#viz_enddate').val() == "") fieldname = "<?php echo lang('hr_leaves_create_field_end');?>";
    if ($('#duration').val() == "" || $('#duration').val() == 0) fieldname = "<?php echo lang('hr_leaves_create_field_duration');?>";
    if (fieldname == "") {
        return true;
    } else {
        bootbox.alert(<?php echo lang('hr_leaves_validate_mandatory_js_msg');?>);
        return false;
    }
}

$(function () {
    //Selectize the leave type combo
    $('#type').select2();
});

function disableSubmitButton() {
    document.getElementById('submitButton').disabled = true;
}

</script>
<style>
.dashboard-cards-wrapper {
    overflow-x: auto;
    width: 100%;
    padding: 10px 0;
}

.dashboard-cards {
    display: flex;
    gap: 15px;
    width: max-content;
    margin-bottom: 20px;
    padding: 10px 20px;
    border-radius: 10px;
}

.dashboard-card {
    background-color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    border: none;
    width: 250px;
    padding: 15px;
}

.dashboard-card-title {
    font-size: 16px;
    color: #6c757d;
    margin-bottom: 5px;
}

.dashboard-card-metric {
    font-size: 32px;
    font-weight: bold;
    color: #343a40;
    margin-bottom: 10px;
}

.dashboard-card-subtext {
    font-size: 12px;
    color: #6c757d;
}
</style>
