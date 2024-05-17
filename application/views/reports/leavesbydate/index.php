<?php
/**
 * This view displays the native report listing the approved leave requests of employees attached to an entity.
 * User can change the month and year of execution (set by default to the previous month).
 * The content of this page is partially loaded by Ajax.
 * @copyright  Copyright (c) Fadzrul Aiman

 * @since         0.4.3
 */
?>

<h2><?php echo lang('reports_leaves_title');?> &nbsp;</h2>

<div class="row-fluid">
    <div class="span4">
        <label for="startDate">Start Date
            <input type="date" name="startDate" id="startDate">
        </label>
        <label for="endDate">End Date
            <input type="date" name="endDate" id="endDate">
        </label>
        <br />
    </div>
    <div class="span4">
        <label for="txtEntity"><?php echo lang('reports_leaves_field_entity');?></label>
        <div class="input-append">
            <input type="text" id="txtEntity" name="txtEntity" readonly />
            <button id="cmdSelectEntity"
                class="btn btn-primary"><?php echo lang('reports_leaves_button_entity');?></button>
        </div>
        <label for="chkIncludeChildren">
            <input type="checkbox" id="chkIncludeChildren" name="chkIncludeChildren" checked />
            <?php echo lang('reports_leaves_field_subdepts');?>
        </label>
    </div>
    <div class="span4">
        <div class="pull-right">
            <label for="chkLeaveDetails">
                <input type="checkbox" id="chkLeaveDetails" name="chkLeaveDetails" />
                <?php echo lang('reports_leaves_field_leave_requests');?>
            </label>
            &nbsp;
            <button class="btn btn-primary" id="cmdLaunchReport"><i class="mdi mdi-file-chart"></i>&nbsp;
                <?php echo lang('reports_leaves_button_launch');?></button>
            <button class="btn btn-primary" id="cmdExportReport"><i class="mdi mdi-download"></i>&nbsp;
                <?php echo lang('reports_leaves_button_export');?></button>
        </div>
    </div>
</div>

<div class="row-fluid">
    <div class="span12">&nbsp;</div>
</div>

<div id="reportResult"></div>

<div class="row-fluid">
    <div class="span12">&nbsp;</div>
</div>

<div id="frmSelectEntity" class="modal hide fade">
    <div class="modal-header">
        <a href="#" onclick="$('#frmSelectEntity').modal('hide');" class="close">&times;</a>
        <h3><?php echo lang('reports_leaves_popup_entity_title');?></h3>
    </div>
    <div class="modal-body" id="frmSelectEntityBody">
        <img src="<?php echo base_url();?>assets/images/loading.gif">
    </div>
    <div class="modal-footer">
        <a href="#" onclick="select_entity();"
            class="btn"><?php echo lang('reports_leaves_popup_entity_button_ok');?></a>
        <a href="#" onclick="$('#frmSelectEntity').modal('hide');"
            class="btn"><?php echo lang('reports_leaves_popup_entity_button_cancel');?></a>
    </div>
</div>

<link rel="stylesheet" href="<?php echo base_url();?>assets/css/flick/jquery-ui.custom.min.css">
<script src="<?php echo base_url();?>assets/js/jquery-ui.custom.min.js"></script>
<?php //Prevent HTTP-404 when localization isn't needed
if ($language_code != 'en') { ?>
<script src="<?php echo base_url();?>assets/js/i18n/jquery.ui.datepicker-<?php echo $language_code;?>.js"></script>
<?php } ?>
<script type="text/javascript">
var entity = -1; //Id of the selected entity
var entityName = ''; //Label of the selected entity
var includeChildren = true;
var leaveDetails = false;
var month = <?php echo date('m');?>;
var year = <?php echo date('Y');?>;

function select_entity() {
    entity = $('#organization').jstree('get_selected')[0];
    entityName = $('#organization').jstree().get_text(entity);
    $('#txtEntity').val(entityName);
    $("#frmSelectEntity").modal('hide');
    Cookies.set('rep_entity', entity);
    Cookies.set('rep_entityName', entityName);
    Cookies.set('rep_includeChildren', includeChildren);
}

$(document).ready(function() {
    //Init datepicker widget
    moment.locale('<?php echo $language_code;?>');
    $("#refdate").val(moment().format('L'));
    $('#refdate').datepicker();

    $("#frmSelectEntity").alert();

    $("#cmdSelectEntity").click(function() {
        $("#frmSelectEntity").modal('show');
        $("#frmSelectEntityBody").load('<?php echo base_url(); ?>organization/select');
    });

    $('#cmdExportReport').click(function() {
    var rtpQuery = '<?php echo base_url();?>reports/leavesbydate/export';
    var startDate = $('#startDate').val();
    var endDate = $('#endDate').val();
    if (entity != -1) {
        rtpQuery += '?entity=' + entity;
    } else {
        rtpQuery += '?entity=0';
    }
    rtpQuery += '&start_date=' + startDate; // Start with '&' after the first parameter
    rtpQuery += '&end_date=' + endDate;
    if ($('#chkIncludeChildren').prop('checked')) {
        rtpQuery += '&children=true';
    } else {
        rtpQuery += '&children=false';
    }
    if ($('#chkLeaveDetails').prop('checked')) {
        rtpQuery += '&requests=true';
    } else {
        rtpQuery += '&requests=false';
    }
    document.location.href = rtpQuery;
});

    $('#cmdLaunchReport').click(function() {
    var ajaxQuery = '<?php echo base_url();?>reports/leavesbydate/execute';
    var startDate = $('#startDate').val();
    var endDate = $('#endDate').val();
    if (entity != -1) {
        ajaxQuery += '?entity=' + entity;
    } else {
        ajaxQuery += '?entity=0';
    }
    ajaxQuery += '&start_date=' + startDate; // Start with '&' after the first parameter
    ajaxQuery += '&end_date=' + endDate;
    if ($('#chkIncludeChildren').prop('checked')) {
        ajaxQuery += '&children=true';
    } else {
        ajaxQuery += '&children=false';
    }
    if ($('#chkLeaveDetails').prop('checked')) {
        ajaxQuery += '&requests=true';
    } else {
        ajaxQuery += '&requests=false';
    }
    $('#reportResult').html("<img src='<?php echo base_url();?>assets/images/loading.gif' />");

    $.ajax({
        url: ajaxQuery
    })
    .done(function(data) {
        $('#reportResult').html(data);
    });
});

    //Toggle include sub-entities option
    $('#chkIncludeChildren').on('change', function() {
        includeChildren = $('#chkIncludeChildren').prop('checked');
        Cookies.set('rep_includeChildren', includeChildren);
    });

    //Toggle include leave requests
    $('#chkLeaveDetails').on('change', function() {
        leaveDetails = $('#chkLeaveDetails').prop('checked');
        Cookies.set('rep_leaveDetails', leaveDetails);
    });

    //Cookie has value ? take -1 by default
    if (Cookies.get('rep_entity') !== undefined) {
        entity = Cookies.get('rep_entity');
        entityName = Cookies.get('rep_entityName');
        includeChildren = Cookies.get('rep_includeChildren');
        leaveDetails = (Cookies.get('rep_leaveDetails') === undefined) ? "false" : Cookies.get(
            'rep_leaveDetails');
        //Parse boolean values
        includeChildren = $.parseJSON(includeChildren.toLowerCase());
        leaveDetails = $.parseJSON(leaveDetails.toLowerCase());
        $('#txtEntity').val(entityName);
        $('#chkIncludeChildren').prop('checked', includeChildren);
        $('#chkLeaveDetails').prop('checked', leaveDetails);
    } else { //Set default value
        Cookies.set('rep_entity', entity);
        Cookies.set('rep_entityName', entityName);
        Cookies.set('rep_includeChildren', includeChildren);
        Cookies.set('rep_leaveDetails', leaveDetails);
    }
});
</script>