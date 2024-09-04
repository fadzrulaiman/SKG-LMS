<?php
/**
 * This view displays the counters (number of available leave) for an employee.
 * @copyright  Copyright (c) Fadzrul Aiman
 * @license    http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 * @link       https://github.com/fadzrulaiman/SKG-LMS
 * @since      0.2.0
 */
?>

<style>
#chartContainer {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-top: 20px;
}

#pieChartContainer,
#barChartContainer {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    height: 100%;
}

#pieChartContainer {
    flex: 1;
    max-width: 30%;
}

#barChartContainer {
    flex: 2;
    max-width: 70%;
}

canvas {
    max-width: 100%;
    height: auto;
}
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><?php echo lang('leaves_summary_title');?></h2>

            <div id="chartContainer">
                <div id="pieChartContainer">
                    <h3>Remaining Leave Balance</h3>
                    <canvas id="leaveBalancePieChart"></canvas>
                </div>
                <div id="barChartContainer">
                    <h3>Leave Taken by Type</h3>
                    <canvas id="leaveTakenBarChart"></canvas>
                </div>
            </div>

            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th rowspan="2"><?php echo lang('leaves_summary_thead_type');?>
                            <i class="mdi mdi-help-circle" data-toggle="tooltip" title="Type of Leave"></i>
                        </th>
                        <th colspan="2" class="text-center"><?php echo lang('leaves_summary_thead_available');?>
                            <i class="mdi mdi-help-circle" data-toggle="tooltip" title="Available leave balances"></i>
                        </th>
                        <th rowspan="2"><i class="mdi mdi-plus-circle"
                                aria-hidden="true"></i>&nbsp;<?php echo lang('leaves_summary_thead_entitled');?>
                            <i class="mdi mdi-help-circle" data-toggle="tooltip" title="Total Entitled Leave"></i>
                        </th>
                        <th rowspan="2"><i class="mdi mdi-minus-circle"
                                aria-hidden="true"></i>&nbsp;<?php echo lang('leaves_summary_thead_taken');?>
                            <i class="mdi mdi-help-circle" data-toggle="tooltip" title="Total Approved Leave"></i>
                        </th>
                        <th rowspan="2"><i class="mdi mdi-information" aria-hidden="true"></i>&nbsp;<span
                                class="label label-warning"><?php echo lang('leaves_summary_thead_leaveapplied');?></span>
                            <i class="mdi mdi-help-circle" data-toggle="tooltip" title="Total Requested Leave"></i>
                        </th>
                    </tr>
                    <tr>
                        <th><?php echo lang('leaves_summary_thead_actual');?>&nbsp;<i class="mdi mdi-help-circle"
                                data-toggle="tooltip" title="Leave Entitlement - Leave Taken"></i></th>
                        <th><span style="background-color: #d4edbc; color: #000; padding: 5px 10px; border-radius: 5px; display: inline-block;">
                                <?php echo lang('leaves_summary_thead_simulated');?><i class="mdi mdi-help-circle" data-toggle="tooltip" title="Leave Entitlement - (Leave Taken + Leave Applied)"></i>
                            </span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                $leave_types = [];
                $simulated_data = [];
                $taken_data = [];
                $colors = ['#39956b', '#ffa600', '#2f4b7c'];
                if (count($summary) > 0) {
                    foreach ($summary as $key => $value) {
                        if (($value[2] == '') || ($value[2] == 'x')) {
                            $estimated = round(((float) $value[1] - (float) $value[0]), 3, PHP_ROUND_HALF_DOWN);
                            $simulated = $estimated;
                            if (!empty($value[4])) $simulated -= (float) $value[4];
                            if (!empty($value[5])) $simulated -= (float) $value[5];
                            $leave_types[] = $key;
                            $simulated_data[] = $simulated;
                            $taken_data[] = (float) $value[0];
                            ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td><?php echo $estimated; ?></td>
                        <td><?php echo $simulated; ?></td>
                        <td><?php echo ((float) $value[1]); ?></td>
                        <td><a href="<?php echo base_url();?>leaves?statuses=3&type=<?php echo $value[3]; ?>"
                                target="_blank"><?php echo ((float) $value[0]); ?></a></td>
                        <td>
                            <?php if (empty($value[5])) { ?>
                            &nbsp;
                            <?php } else { ?>
                            <a href="<?php echo base_url();?>leaves?statuses=2|5&type=<?php echo $value[3]; ?>"
                                target="_blank"><?php echo ((float) $value[5]); ?></a>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php }
                    }
                } else {?>
                    <tr>
                        <td colspan="6"><?php echo lang('leaves_summary_tbody_empty');; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if ($language_code != 'en') { ?>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.<?php echo strtolower($language_code); ?>.min.js">
</script>
<?php } ?>

<script type="text/javascript">
function toISODateLocal(d) {
    var z = n => (n < 10 ? '0' : '') + n;
    return d.getFullYear() + '-' + z(d.getMonth() + 1) + '-' + z(d.getDate());
}

$(function() {
    var isDefault = <?php echo $isDefault;?>;
    var reportDate = '<?php $date = new DateTime($refDate); echo $date->format(lang('global_date_format'));?>';
    var dateFormat = {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric'
    };
    var now = new Date();
    var todayDate = now.toLocaleDateString('<?php echo $language_code;?>', dateFormat);
    if (isDefault == 1) {
        $("#refdate").val(todayDate);
    } else {
        $("#refdate").val(reportDate);
    }

    $("#refdate").datepicker({
        language: "<?php echo $language_code;?>",
        autoclose: true
    }).on('changeDate', function(e) {
        isoDate = toISODateLocal(e.date);
        url = "<?php echo base_url();?>leaves/counters/" + isoDate;
        window.location = url;
    });

    $("[data-toggle=tooltip]").tooltip({
        placement: 'top'
    });

    // Leave Balance Pie Chart
    var ctx1 = document.getElementById('leaveBalancePieChart').getContext('2d');
    var leaveBalancePieChart = new Chart(ctx1, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($leave_types); ?>,
            datasets: [{
                data: <?php echo json_encode($simulated_data); ?>,
                backgroundColor: ['#39956b', '#ffa600', '#2f4b7c']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });

    // Calculate the sum of taken and requested for each leave type
    var takenAndRequestedData = [];
    <?php foreach ($summary as $key => $value) { ?>
    var taken = parseFloat(<?php echo $value[0]; ?>);
    var requested = parseFloat(<?php echo !empty($value[5]) ? $value[5] : 0; ?>);
    takenAndRequestedData.push(taken + requested);
    <?php } ?>

    // Leave Taken by Type Chart
    var ctx2 = document.getElementById('leaveTakenBarChart').getContext('2d');
    var leaveTakenBarChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($leave_types); ?>,
            datasets: [{
                data: takenAndRequestedData,
                backgroundColor: ['#39956b', '#ffa600', '#2f4b7c']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>