<!-- Modal: Confirm Delete -->
<div id="frmConfirmDelete" class="modal hide fade">
    <div class="modal-header">
        <a href="#" onclick="$('#frmConfirmDelete').modal('hide');" class="close">&times;</a>
        <h3><?php echo lang('organization_index_popup_delete_title'); ?></h3>
    </div>
    <div class="modal-body">
        <p><?php echo lang('organization_index_popup_delete_description'); ?></p>
        <p><?php echo lang('organization_index_popup_delete_confirm'); ?></p>
    </div>
    <div class="modal-footer">
        <a href="#" class="btn btn-danger" id="lnkDeleteEntity"><?php echo lang('organization_index_popup_delete_button_yes'); ?></a>
        <a href="#" onclick="$('#organization').jstree('refresh'); $('#frmConfirmDelete').modal('hide');" class="btn"><?php echo lang('organization_index_popup_delete_button_no'); ?></a>
    </div>
</div>

<!-- Modal: Add Employee -->
<div id="frmAddEmployee" class="modal hide fade">
    <div class="modal-header">
        <a href="#" onclick="$('#frmAddEmployee').modal('hide');" class="close">&times;</a>
        <h3><?php echo lang('organization_index_popup_add_title'); ?></h3>
    </div>
    <div class="modal-body" id="frmAddEmployeeBody">
        <img src="<?php echo base_url(); ?>assets/images/loading.gif">
    </div>
    <div class="modal-footer">
        <a href="#" onclick="add_employee();" class="btn"><?php echo lang('organization_index_popup_add_button_ok'); ?></a>
        <a href="#" onclick="$('#frmAddEmployee').modal('hide');" class="btn"><?php echo lang('organization_index_popup_add_button_cancel'); ?></a>
    </div>
</div>

<!-- Modal: Select Supervisor -->
<div id="frmSelectSupervisor" class="modal hide fade">
    <div class="modal-header">
        <a href="#" onclick="$('#frmSelectSupervisor').modal('hide');" class="close">&times;</a>
        <h3><?php echo lang('organization_index_popup_supervisor_title'); ?></h3>
    </div>
    <div class="modal-body" id="frmSelectSupervisorBody">
        <img src="<?php echo base_url(); ?>assets/images/loading.gif">
    </div>
    <div class="modal-footer">
        <a href="#" onclick="select_supervisor();" class="btn"><?php echo lang('organization_index_popup_supervisor_button_ok'); ?></a>
        <a href="#" onclick="$('#frmSelectSupervisor').modal('hide');" class="btn"><?php echo lang('organization_index_popup_supervisor_button_cancel'); ?></a>
    </div>
</div>

<!-- Modal: Error -->
<div id="frmError" class="modal hide fade">
    <div class="modal-header">
        <a href="#" onclick="$('#frmError').modal('hide');" class="close">&times;</a>
        <h3><?php echo lang('organization_index_popup_error_title'); ?></h3>
    </div>
    <div class="modal-body" id="lblError"></div>
    <div class="modal-footer">
        <a href="#" onclick="$('#frmError').modal('hide');" class="btn"><?php echo lang('organization_index_popup_error_button_ok'); ?></a>
    </div>
</div>

<!-- Modal: Ajax Wait -->
<div class="modal hide" id="frmModalAjaxWait" data-backdrop="static" data-keyboard="false">
    <div class="modal-header">
        <h1><?php echo lang('global_msg_wait'); ?></h1>
    </div>
    <div class="modal-body">
        <img src="<?php echo base_url(); ?>assets/images/loading.gif" align="middle">
    </div>
</div>
