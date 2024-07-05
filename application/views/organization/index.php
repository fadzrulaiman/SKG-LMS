<?php
/**
 * This view displays the organization tree and allows to attach and detach employees from sub-entities.
 * @since 0.1.0
 */
?>

<h2><?php echo lang('organization_index_title'); ?> &nbsp;</h2>

<div class="row-fluid">
    <div class="span4">
        <div class="input-append">
            <input type="text" class="input-medium" placeholder="<?php echo lang('organization_index_field_search_placeholder'); ?>" id="txtSearch" />
            <button id="cmdClearSearch" class="btn btn-primary"><i class="mdi mdi-close"></i></button>
            <button id="cmdSearch" class="btn btn-primary"><i class="mdi mdi-magnify"></i>&nbsp;<?php echo lang('organization_index_button_search'); ?></button>
        </div>
        <div style="text-align: left;" id="organization"></div>
    </div>
    <div class="span8">
        <h3><?php echo lang('organization_index_title_employees'); ?></h3>
        <table id="collaborators" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th><?php echo lang('organization_index_thead_id'); ?></th>
                    <th><?php echo lang('organization_index_thead_firstname'); ?></th>
                    <th><?php echo lang('organization_index_thead_lastname'); ?></th>
                    <th><?php echo lang('organization_index_thead_email'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <br />
        <button id="cmdAddEmployee" class="btn btn-primary"><?php echo lang('organization_index_button_add_employee'); ?></button>
        <button id="cmdRemoveEmployee" class="btn btn-primary"><?php echo lang('organization_index_button_remove_employee'); ?></button>
        <br />
        <h3><?php echo lang('organization_index_title_supervisor'); ?></h3>
        <p><?php echo lang('organization_index_description_supervisor'); ?></p>
        <div class="input-append">
            <input type="text" id="txtSupervisor" />
            <button id="cmdDeleteSupervisor" class="btn btn-danger"><i class="mdi mdi-close"></i></button>
            <button id="cmdSelectSupervisor" class="btn btn-primary"><?php echo lang('organization_index_button_select_supervisor'); ?></button>
        </div>
        <br /><br />
    </div>
</div>

<!-- Modals -->
<?php echo $this->load->view('organization/modals', null, true); ?>

<!-- CSS Dependencies -->
<link href="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="<?php echo base_url(); ?>assets/datatable/Select-1.1.2/css/select.dataTables.min.css" rel="stylesheet">
<link href="<?php echo base_url(); ?>assets/css/flick/jquery-ui.custom.min.css" rel="stylesheet">

<!-- JS Dependencies -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/DataTables-1.10.11/js/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/datatable/Select-1.1.2/js/dataTables.select.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootbox.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.custom.min.js"></script>

<?php if ($language_code != 'en') { ?>
    <script src="<?php echo base_url(); ?>assets/js/i18n/jquery.ui.datepicker-<?php echo $language_code; ?>.js"></script>
<?php } ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var oTable = $('#collaborators').DataTable({
        columns: [
            { data: "id" },
            { data: "firstname" },
            { data: "lastname" },
            { data: "email" }
        ],
        select: 'single',
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

    // Initialize the organization tree
    $('#organization').jstree({
        contextmenu: {
            items: function(n) {
                var tmp = $.jstree.defaults.contextmenu.items();
                tmp.create.label = '<?php echo lang('treeview_context_menu_create'); ?>';
                tmp.rename.label = '<?php echo lang('treeview_context_menu_rename'); ?>';
                tmp.remove.label = '<?php echo lang('treeview_context_menu_remove'); ?>';
                tmp.ccp.label = '<?php echo lang('treeview_context_menu_edit'); ?>';
                tmp.ccp.submenu.copy.label = '<?php echo lang('treeview_context_menu_copy'); ?>';
                tmp.ccp.submenu.cut.label = '<?php echo lang('treeview_context_menu_cut'); ?>';
                tmp.ccp.submenu.paste.label = '<?php echo lang('treeview_context_menu_paste'); ?>';
                return tmp;
            }
        },
        core: {
            multiple: false,
            data: {
                url: function (node) {
                    return node.id === '#' ?
                        '<?php echo base_url(); ?>organization/root' :
                        '<?php echo base_url(); ?>organization/children';
                },
                data: function (node) {
                    return { 'id': node.id };
                }
            },
            check_callback: true
        },
        plugins: ["contextmenu", "dnd", "search", "state", "sort", "unique"]
    })
    .on('delete_node.jstree', function (e, data) {
        var id = data.node.id;
        if (id == 0) {
            $("#lblError").text("<?php echo lang('organization_index_error_msg_delete_root'); ?>");
            $("#frmError").modal('show');
            $("#organization").jstree("refresh");
        } else {
            $('#frmConfirmDelete').data('id', id).modal('show');
        }
    })
    .on('create_node.jstree', function (e, data) {
        createMtx = true;
        bootbox.prompt("<?php echo lang('organization_index_prompt_entity_name'); ?>",
            "<?php echo lang('organization_index_popup_node_button_cancel'); ?>",
            "<?php echo lang('organization_index_popup_node_button_ok'); ?>", function(result) {
            if ((result === null) || (result == '')) {  // NULL or empty string has no effect
                data.instance.refresh();
            } else {
                $.get('organization/create', { 'id': data.node.parent, 'position': data.position, 'text': result })
                .done(function (d) {
                    data.instance.set_id(data.node, d.id);
                    createMtx = false;
                })
                .fail(function() {
                    data.instance.refresh();
                    createMtx = false;
                });
            }
        });
    })
    .on('rename_node.jstree', function(e, data) {
        if (!createMtx) {
            $.get('organization/rename', {'id': data.node.id, 'text': data.text})
                .fail(function() {
                    data.instance.refresh();
                });
        }
    })
    .on('move_node.jstree', function(e, data) {
        e.preventDefault();
        $.get('organization/move', {'id': data.node.id, 'parent': data.parent, 'position': data.position})
            .fail(function() {
                data.instance.refresh();
            });
    })
    .on('copy_node.jstree', function(e, data) {
        e.preventDefault();
        $.get('organization/copy', {'id': data.original.id, 'parent': data.parent, 'position': data.position})
            .always(function() {
                data.instance.refresh();
            });
    })
    .on('changed.jstree', function(e, data) {
        if (data && data.selected && data.selected.length) {
            $('#frmModalAjaxWait').modal('show');
            var isTableLoaded = false;
            oTable.ajax.url("<?php echo base_url(); ?>organization/employees?id=" + data.selected.join(':'))
                .load(function() {
                        isTableLoaded = true;
                    }, true);
            $.ajax({
                type: "GET",
                url: "<?php echo base_url(); ?>organization/getsupervisor",
                    data: { 'entity': data.selected.join(':') }
                  })
                .done(function(data) {
                    // Update field with the name of employee (the supervisor)
                    if (data != null && typeof data === 'object') {
                        $('#txtSupervisor').val(data.username);
                    } else {
                        $('#txtSupervisor').val("");
                    }
                    $.when(isTableLoaded, isTableLoaded).done(function() {
                        $("#frmModalAjaxWait").modal('hide');
                    });
              });
        }
    });

    // Handle adding an employee
    $('#cmdAddEmployee').click(function() {
        if ($('#organization').jstree('get_selected').length == 1) {
            $('#frmAddEmployee').modal('show');
            $('#frmAddEmployeeBody').load('<?php echo base_url(); ?>users/employees');
        } else {
            $('#lblError').text("<?php echo lang('organization_index_error_msg_select_entity'); ?>");
            $('#frmError').modal('show');
        }
    });

    // Handle selecting a supervisor
    $('#cmdSelectSupervisor').click(function() {
        if ($('#organization').jstree('get_selected').length == 1) {
            $('#frmSelectSupervisor').modal('show');
            $('#frmSelectSupervisorBody').load('<?php echo base_url(); ?>users/employees');
        } else {
            $('#lblError').text("<?php echo lang('organization_index_error_msg_select_entity'); ?>");
            $('#frmError').modal('show');
        }
    });

    // Handle deleting a supervisor
    $('#cmdDeleteSupervisor').click(function() {
        if ($('#organization').jstree('get_selected').length == 1) {
            delete_supervisor();
        } else {
            $('#lblError').text("<?php echo lang('organization_index_error_msg_select_entity'); ?>");
            $('#frmError').modal('show');
        }
    });

    // Handle removing an employee
    $('#cmdRemoveEmployee').click(function() {
        var id = oTable.rows({selected: true}).data()[0][0];
        if (id != "") {
            if ($('#organization').jstree('get_selected').length == 1) {
                var entity = $('#organization').jstree('get_selected')[0];
                $.ajax({
                    type: "GET",
                    url: "<?php echo base_url(); ?>organization/delemployee",
                    data: { 'user': id }
                  })
                  .done(function( msg ) {
                    // Update table of users
                    $('#frmModalAjaxWait').modal('show');
                    oTable.ajax.url("<?php echo base_url(); ?>organization/employees?id=" + entity)
                    .load(function() {
                            $("#frmModalAjaxWait").modal('hide');
                        }, true);
                });
            } else {
                $('#lblError').text("<?php echo lang('organization_index_error_msg_select_entity'); ?>");
                $('#frmError').modal('show');
            }
        } else {
            $('#lblError').text("<?php echo lang('organization_index_error_msg_select_employee'); ?>");
            $('#frmError').modal('show');
            $('#frmErrorEmployee').modal('show');
        }
    });

    // Handle search in the tree view
    $('#cmdSearch').click(function () {
        $('#organization').jstree('search', $('#txtSearch').val(), true, true);
    });

    $('#txtSearch').keyup(function(e) {
        if (e.keyCode == 13) { $('#organization').jstree('search', $('#txtSearch').val(), true, true); }   // Enter key
    });

    // Handle clearing the search option in the tree view
    $('#cmdClearSearch').click(function () {
        $('#organization').jstree('clear_search');
    });

    $(document).keyup(function(e) {
        if (e.keyCode == 27) { $('#organization').jstree('clear_search'); }   // Escape key
    });

    // Global Ajax error handling mainly used for session expiration
    $(document).ajaxError(function(event, jqXHR, settings, errorThrown) {
        $('#frmModalAjaxWait').modal('hide');
        if (jqXHR.status == 401) {
            bootbox.alert("<?php echo lang('global_ajax_timeout'); ?>", function() {
                // After the login page, we'll be redirected to the current page
               location.reload();
            });
        } else { // Deal with other errors
            if (jqXHR.status != 200) {
                bootbox.alert("<?php echo lang('global_ajax_error'); ?>");
            }
        }
    });

    // Prevent loading the same content in modal (refreshed each time)
    $('#frmAddEmployee').on('hidden', function() {
        $('#employees').remove();
        $(this).removeData('modal');
    });

    $('#frmSelectSupervisor').on('hidden', function() {
        $('#employees').remove();
        $(this).removeData('modal');
    });
});

// Function to add an employee to an entity
function add_employee() {
    var employees = $('#employees').DataTable();
    var id = employees.rows({selected: true}).data()[0][0];
    var entity = $('#organization').jstree('get_selected')[0];
    $.ajax({
        type: "GET",
        url: "<?php echo base_url(); ?>organization/addemployee",
        data: { 'user': id, 'entity': entity }
    })
    .done(function(msg) {
        // Update table of users
        $('#frmModalAjaxWait').modal('show');
        oTable.ajax.url("<?php echo base_url(); ?>organization/employees?id=" + entity)
        .load(function() {
                $("#frmModalAjaxWait").modal('hide');
            }, true);
        $("#frmAddEmployee").modal('hide');
    });
}

// Function to select a supervisor for an entity
function select_supervisor() {
    $("#frmSelectSupervisor").modal('hide');
    $('#frmModalAjaxWait').modal('show');
    var employees = $('#employees').DataTable();
    var id = employees.rows({selected: true}).data()[0][0];
    var text = employees.rows({selected: true}).data()[0][1] + ' ' + employees.rows({selected: true}).data()[0][2];
    var entity = $('#organization').jstree('get_selected')[0];
    $.ajax({
        type: "GET",
        url: "<?php echo base_url(); ?>organization/setsupervisor",
        data: { 'user': id, 'entity': entity }
    })
    .done(function(msg) {
        // Update field with the name of employee (the supervisor)
        $('#txtSupervisor').val(text);
        $('#frmModalAjaxWait').modal('hide');
    });
}

// Function to delete a supervisor from an entity
function delete_supervisor() {
    $('#frmModalAjaxWait').modal('show');
    var entity = $('#organization').jstree('get_selected')[0];
    $.ajax({
        type: "GET",
        url: "<?php echo base_url(); ?>organization/setsupervisor",
        data: { 'user': null, 'entity': entity }
    })
    .done(function(msg) {
        // Update field with the name of employee (the supervisor)
        $('#txtSupervisor').val("");
        $('#frmModalAjaxWait').modal('hide');
    });
}
</script>
