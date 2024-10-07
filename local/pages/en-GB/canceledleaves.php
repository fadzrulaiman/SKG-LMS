<?php
// This is a sample page showing how to create a custom report
// We can get access to all the framework, so you can do anything with the instance of the current controller ($this)

// You can load a language file so as to translate the report if the strings are available
// It can be useful for date formatting
$this->lang->load('requests', $this->language);
$this->lang->load('global', $this->language);

// Retrieve the selected month and year from the form submission, defaulting to "All" for month and the current year if not provided
$selected_month = $this->input->get('cboMonth') !== null ? $this->input->get('cboMonth') : 0;
$selected_year = $this->input->get('cboYear') ? $this->input->get('cboYear') : date('Y');
?>

<h2>Canceled Leave Requests</h2>

<!-- Filter form -->
<form method="get" action="" class="d-flex justify-content-center mb-4">
    <div class="d-flex flex-column align-items-center">
        <div class="form-group mx-2">
            <label for="cboMonth">Month</label>
            <select class="form-control" name="cboMonth" id="cboMonth">
                <?php for ($ii = 1; $ii < 13; $ii++) {
                    if ($ii == $selected_month) {
                        echo "<option value='" . $ii . "' selected>" . date('F', mktime(0, 0, 0, $ii, 10)) . "</option>";
                    } else {
                        echo "<option value='" . $ii . "'>" . date('F', mktime(0, 0, 0, $ii, 10)) . "</option>";
                    }
                }?>
                <option value='0' <?php echo ($selected_month == 0) ? 'selected' : ''; ?>><?php echo lang('All');?></option>
            </select>
        </div>
        <div class="form-group mx-2">
            <label for="cboYear">Year</label>
            <select class="form-control" name="cboYear" id="cboYear">
                <?php $len = date('Y');
                for ($ii = date('Y', strtotime('-6 year')); $ii <= $len; $ii++) {
                    if ($ii == $selected_year) {
                        echo "<option value='" . $ii . "' selected>" . $ii . "</option>";
                    } else {
                        echo "<option value='" . $ii . "'>" . $ii . "</option>";
                    }
                }?>
            </select>
        </div>
        <div class="form-group mx-2">
            <button type="submit" class="btn btn-primary mt-4">Filter</button>
        </div>
    </div>
</form>

<?php
//$this is the instance of the current controller, so you can use it for direct access to the database
$this->db->select('users.firstname, users.lastname, leaves.*');
$this->db->select('status.name as status_name, types.name as type_name');
$this->db->from('leaves');
$this->db->join('status', 'leaves.status = status.id');
$this->db->join('types', 'leaves.type = types.id');
$this->db->join('users', 'leaves.employee = users.id');
$this->db->where('leaves.status', 6);  // Filter for canceled leave requests

// Add date filtering based on the selected month and year
if ($selected_month != 0) {
    $this->db->group_start();
    $this->db->where('MONTH(leaves.startdate)', $selected_month);
    $this->db->where('YEAR(leaves.startdate)', $selected_year);
    $this->db->group_end();
    $this->db->or_group_start();
    $this->db->where('MONTH(leaves.enddate)', $selected_month);
    $this->db->where('YEAR(leaves.enddate)', $selected_year);
    $this->db->group_end();
} else {
    // Filter for the entire year if "All" is selected for the month
    $start_date = $selected_year . '-01-01';
    $end_date = $selected_year . '-12-31';
    $this->db->group_start();
    $this->db->where('leaves.startdate >=', $start_date);
    $this->db->where('leaves.startdate <=', $end_date);
    $this->db->group_end();
}

$this->db->order_by('users.lastname, users.firstname, leaves.startdate', 'desc');
$rows = $this->db->get()->result_array();
?>

<div class="row-fluid">
    <div class="col-12">
        <table class="table table-bordered table-hover table-condensed">
            <thead>
                <tr>
                    <th><?php echo lang('requests_index_thead_id');?></th>
                    <th><?php echo lang('requests_index_thead_fullname');?></th>
                    <th><?php echo lang('requests_index_thead_startdate');?></th>
                    <th><?php echo lang('requests_index_thead_enddate');?></th>
                    <th><?php echo lang('requests_index_thead_duration');?></th>
                    <th><?php echo lang('requests_index_thead_type');?></th>
                    <th><?php echo lang('requests_index_thead_status');?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) {
                    $date = new DateTime($row['startdate']);
                    $startdate = $date->format(lang('global_date_format'));
                    $date = new DateTime($row['enddate']);
                    $enddate = $date->format(lang('global_date_format'));?>
                <tr>
                    <td><a href="leaves/edit/<?php echo $row['id'];?>?source=hr%2Fleaves%2F1" target="_blank"><?php echo $row['id'];?></a></td>
                    <td><a href="hr/counters/employees/<?php echo $row['employee'];?>" target="_blank"><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></a></td>
                    <td><?php echo $startdate; ?></td>
                    <td><?php echo $enddate; ?></td>
                    <td><?php echo $row['duration']; ?></td>
                    <td><?php echo $row['type_name']; ?></td>
                    <td><?php echo lang($row['status_name']); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<a href="<?php echo base_url() . 'excel-canceledleaves?cboMonth=' . $selected_month . '&cboYear=' . $selected_year; ?>" class="btn btn-primary"><i class="mdi mdi-download"></i>&nbsp; <?php echo lang('requests_index_button_export');?></a>

<div class="row">
    <div class="col-12">&nbsp;</div>
</div>
