<?php
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Entitled_Days.xls");
?>

<table border="1">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Days</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($entitleddays as $row): ?>
        <tr>
            <td><?php echo $row['employee_name']; ?></td>
            <td><?php echo $row['startdate']; ?></td>
            <td><?php echo $row['enddate']; ?></td>
            <td><?php echo $row['days']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
