<?php
/**
 * This view allows an employees (or HR admin/Manager) to create a new leave request
 * @copyright  Copyright (c) Fadzrul Aiman
 * @since         0.1.0
 */
?>
<h2><?php echo lang('leaves_create_title');?> &nbsp;<?php echo $help;?></h2>

<div class="row-fluid">
    <div class="span8">

        <?php echo validation_errors(); ?>

        <?php
        $attributes = array('id' => 'frmLeaveForm', 'enctype' => 'multipart/form-data', 'onsubmit' => 'disableSubmitButton()');
        echo form_open('leaves/create', $attributes) ?>
        <h3>Leave Balance</h3>
        <?php if (isset($leaveBalances) && !empty($leaveBalances)): ?>
        <div class="dashboard-cards-wrapper">
            <div class="dashboard-cards d-flex justify-content-start flex-nowrap">
                <?php foreach ($leaveBalances as $balance): ?>
                <div class="dashboard-card mb-3">
                    <div class="card-body text-center">
                        <h6 class="dashboard-card-title"><?php echo $balance['type_name']; ?></h6>
                        <p class="dashboard-card-metric"><?php echo $balance['balance']; ?> Days</p>
                        <small class="dashboard-card-subtext">
                            <?php echo $balance['entitled']; ?> Entitled,
                            <?php echo $balance['taken']; ?> Taken
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <p>No Leave Balance</p>
        <?php endif; ?>
        <label for="type">
            <?php echo lang('leaves_create_field_type');?>
            &nbsp;<span class="muted"
                id="lblCredit"><?php if (!is_null($credit)) { ?>(<?php echo $credit; ?>)<?php } ?></span>
        </label>
        <select class="input-xxlarge" name="type" id="type">
            <option value="1" <?php if ($defaultType == 1) echo "selected"; ?>>Annual Leave</option>
            <option value="2" <?php if ($defaultType == 2) echo "selected"; ?>>Sick Leave</option>
            <!-- Check the condition and disable if necessary -->
            <option value="3" <?php if ($defaultType == 3) echo "selected"; ?> <?php
            // Loop through balances to find 'Annual Leave' and check its balance
            foreach ($leaveBalances as $balance) {
                if ($balance['type_name'] == 'Annual Leave' && $balance['balance'] != 0) {
                    echo 'disabled';
                }
            }
        ?>>Leave Bank</option>
        </select>

        <label for="viz_startdate"><?php echo lang('leaves_create_field_start');?></label>
        <input type="text" name="viz_startdate" id="viz_startdate" value="<?php echo set_value('startdate'); ?>"
            autocomplete="off" />
        <input type="hidden" name="startdate" id="startdate" />
        <input type="hidden" value="Morning" name="startdatetype" id="startdatetype" />
        <label for="viz_enddate"><?php echo lang('leaves_create_field_end');?></label>
        <input type="text" name="viz_enddate" id="viz_enddate" value="<?php echo set_value('enddate'); ?>"
            autocomplete="off" />
        <input type="hidden" name="enddate" id="enddate" />
        <input type="hidden" value="Afternoon" name="enddatetype" id="enddatetype" />
        <label for="duration"><?php echo lang('leaves_create_field_duration');?> <span
                id="tooltipDayOff"></span></label>
        <?php if ($this->config->item('disable_edit_leave_duration') == TRUE) { ?>
        <input type="text" name="duration" id="duration" value="<?php echo set_value('duration'); ?>" readonly />
        <?php } else { ?>
        <input type="text" name="duration" id="duration" value="<?php echo set_value('duration'); ?>" />
        <?php } ?>
        <div class="alert hide alert-error" id="lblCreditAlert">
            <?php echo lang('leaves_create_field_duration_message');?>
        </div>

        <div class="alert hide alert-error" id="lblOverlappingAlert">
            <?php echo lang('leaves_create_field_overlapping_message');?>
        </div>

        <div class="alert hide alert-error" id="lblOverlappingDayOffAlert"
            onclick="$('#lblOverlappingDayOffAlert').hide();">
            <?php echo lang('leaves_flash_msg_overlap_dayoff');?>
        </div>

        <label for="attachment">Attachment</label>
        <input type="file" name="attachment" id="attachment" accept="image/*, .pdf">

        <label for="cause"><?php echo lang('leaves_create_field_cause');?></label>
        <textarea name="cause"><?php echo set_value('cause'); ?></textarea>
        <input type="hidden" value="2" name="status" id="status" />
        <br>
        <button id="submitButton" name="submit" type="submit" class="btn btn-primary">
        <i class="mdi mdi-check"></i>&nbsp; <?php echo lang('Requested'); ?>
        </button>


        <br /><br />
        <a href="<?php echo base_url(); ?>leaves" class="btn btn-danger"><i class="mdi mdi-close"></i>&nbsp;
            <?php echo lang('leaves_create_button_cancel');?></a>
        </form>

    </div>
</div>

<div class="modal hide" id="frmModalAjaxWait" data-backdrop="static" data-keyboard="false">
    <div class="modal-header">
        <h1><?php echo lang('global_msg_wait');?></h1>
    </div>
    <div class="modal-body">
        <img src="<?php echo base_url();?>assets/images/loading.gif" align="middle">
    </div>
</div>
<!-- Old Styling
<link rel="stylesheet" href="<?php echo base_url();?>assets/css/flick/jquery-ui.custom.min.css">
<script src="<?php echo base_url();?>assets/js/jquery-ui.custom.min.js"></script>
<?php //Prevent HTTP-404 when localization isn't needed
if ($language_code != 'en') { ?>
<script src="<?php echo base_url();?>assets/js/i18n/jquery.ui.datepicker-<?php echo $language_code;?>.js"></script>
<?php } ?>
<script src="<?php echo base_url();?>assets/js/bootbox.min.js"></script>
-->
<!-- jQuery UI (flick theme) -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/flick/jquery-ui.min.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Conditional jQuery UI Datepicker Localization -->
<?php if ($language_code != 'en') { ?>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-i18n/1.12.1/i18n/jquery.ui.datepicker-<?php echo strtolower($language_code); ?>.js">
</script>
<?php } ?>

<!-- Bootbox -->
<script src="https://cdn.jsdelivr.net/npm/bootbox@5.5.2/bootbox.min.js"></script>

<?php require_once dirname(BASEPATH) . "/local/triggers/leave_view.php"; ?>
<script>
$(document).on("click", "#showNoneWorkedDay", function(e) {
    showListDayOffHTML();
});
</script>
<script type="text/javascript">
var baseURL = '<?php echo base_url();?>';
var userId = <?php echo $user_id; ?>;
var leaveId = null;
var languageCode = '<?php echo $language_code;?>';
var dateJsFormat = '<?php echo lang('global_date_js_format');?>';
var dateMomentJsFormat = '<?php echo lang('global_date_momentjs_format');?>';

var noContractMsg = "<?php echo lang('leaves_validate_flash_msg_no_contract');?>";
var noTwoPeriodsMsg = "<?php echo lang('leaves_validate_flash_msg_overlap_period');?>";

var overlappingWithDayOff = "<?php echo lang('leaves_flash_msg_overlap_dayoff');?>";
var listOfDaysOffTitle = "<?php echo lang('leaves_flash_spn_list_days_off');?>";

function validate_form() {
    var fieldname = "";

    //Call custom trigger defined into local/triggers/leave.js
    if (typeof triggerValidateCreateForm == 'function') {
        if (triggerValidateCreateForm() == false) return false;
    }

    if ($('#viz_startdate').val() == "") fieldname = "<?php echo lang('leaves_create_field_start');?>";
    if ($('#viz_enddate').val() == "") fieldname = "<?php echo lang('leaves_create_field_end');?>";
    if ($('#duration').val() == "" || $('#duration').val() == 0) fieldname =
        "<?php echo lang('leaves_create_field_duration');?>";
    if (fieldname == "") {
        return true;
    } else {
        bootbox.alert(<?php echo lang('leaves_validate_mandatory_js_msg');?>);
        return false;
    }
}

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

    <?php if ($this->config->item('disallow_requests_without_credit') == TRUE) {?>
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
    <?php }?>
});

<?php if ($this->config->item('csrf_protection') == TRUE) {?>
$(function() {
    $.ajaxSetup({
        data: {
            <?php echo $this->security->get_csrf_token_name();?>: "<?php echo $this->security->get_csrf_hash();?>",
        }
    });
});
<?php }?>
</script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/lms/leave.edit-0.7.0.js" type="text/javascript">
</script>
<script type="text/javascript">
function disableSubmitButton() {
    document.getElementById('submitButton').disabled = true;
}
</script>
<style>
.dashboard-cards-wrapper {
    overflow-x: auto;
    /* Allows horizontal scrolling if cards overflow the container */
    width: 100%;
    /* Takes full width of the parent element */
    padding: 10px 0;
    /* Adjusts padding around the cards */
}

.dashboard-cards {
    display: flex;
    gap: 15px;
    /* Space between cards */
    width: max-content;
    /* Allows the width to adjust according to the number of cards */
    margin-bottom: 20px;
    padding: 10px 20px;
    background-color: #f7f8fa;
    border-radius: 10px;
}

.dashboard-card {
    background-color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    border: none;
    width: 250px;
    /* Adjust the width as needed */
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
