<?php
// Load necessary libraries and language files
$this->lang->load('requests', $this->language);
$this->lang->load('global', $this->language);

// Retrieve the selected year from the form submission, defaulting to the current year if not provided
$selected_year = $this->input->get('cboYear') ? $this->input->get('cboYear') : date('Y');

// SQL query to retrieve employees with no balance for sick leave entitlements for the specified year
$sql = "
    SELECT 
        u.id AS employee_id, 
        u.firstname, 
        u.lastname, 
        'Sick Leave' AS leave_type,
        ? AS session_year
    FROM 
        users u
    LEFT JOIN 
        entitleddays ed 
    ON 
        u.id = ed.employee 
        AND ed.type = 2 
        AND YEAR(ed.startdate) = ?
    WHERE 
        ed.id IS NULL 
        AND u.active = 1
    ORDER BY 
        u.lastname, 
        u.firstname
";

// Execute the query
$query = $this->db->query($sql, array($selected_year, $selected_year));
$rows = $query->result_array();
?>

<h2>Employees with No Sick Leave Entitlements</h2>

<!-- Filter form -->
<form method="get" action="" class="d-flex justify-content-center mb-4">
    <div class="d-flex flex-column align-items-center">
        <div class="form-group mx-2">
            <label for="cboYear">Year</label>
            <select class="form-control" name="cboYear" id="cboYear">
                <?php 
                $current_year = date('Y');
                $start_year = $current_year - 3;
                $end_year = $current_year + 3;
                for ($ii = $start_year; $ii <= $end_year; $ii++) {
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


<div class="row-fluid">
    <div class="col-12">
        <table class="table table-bordered table-hover table-condensed">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Leave Type</th>
                    <th>Year</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) { ?>
                <tr>
                    <td><a href="hr/counters/employees/<?php echo $row['employee_id'];?>" target="_blank"><?php echo $row['employee_id'];?></a></td>
                    <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                    <td><?php echo $row['leave_type']; ?></td>
                    <td><?php echo $row['session_year']; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-12">&nbsp;</div>
</div>
