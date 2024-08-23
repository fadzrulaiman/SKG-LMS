<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKG LMS 仪表板</title>
    <link rel="stylesheet" href="<?php echo base_url();?>assets/css/home/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1 class="main-heading">沙巴油棕集团请假管理系统</h1>
            <p class="welcome-message">欢迎来到 SKG LMS，<?php echo $fullname;?>！在这里您可以轻松管理请假请求，追踪您的余额，并随时了解团队的请假趋势，一切尽在掌握。</p>
        </header>
        <section class="quick-access">
            <h2>请假余额</h2>
            <?php if (!empty($leave_balance)): ?>
            <section class="dashboard-cards">
                <?php foreach ($leave_balance as $balance): ?>
                <div class="card">
                    <div class="card-content">
                        <h6 class="dashboard-card-title"><?php echo $balance['type_name']; ?></h6>
                        <p class="dashboard-card-metric"><?php echo $balance['balance']; ?> 天</p>
                        <small class="dashboard-card-subtext">
                            <?php echo $balance['entitled']; ?> 应得,
                            <?php echo $balance['taken']; ?> 已使用
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php else: ?>
            <p>没有请假余额</p>
            <?php endif; ?>
            <div class="navigation-links">
                <a class="btn" href="<?php echo base_url();?>leaves/create">创建请假请求</a>
                <a class="btn" href="<?php echo base_url();?>leaves/counters">Leave Balance</a>
                <a class="btn" href="<?php echo base_url();?>leaves">请假请求列表</a>
            </div>
        </section>
        <?php
        $requests_count = isset($requests_count) ? $requests_count : 0;
        $requested_leaves_count = isset($requested_leaves_count) ? $requested_leaves_count : 0;
        $requested_extra_count = isset($requested_extra_count) ? $requested_extra_count : 0;
        $requested_leavebank_count = isset($requested_leavebank_count) ? $requested_leavebank_count : 0;
        $employees_count = isset($employees_count) ? $employees_count : 0;
        $leave_balance = isset($leave_balance) ? $leave_balance : [];
        $combined_count = $requests_count + $requested_leavebank_count;

        if (($is_manager == TRUE) || ($is_hr == TRUE)) { ?>
        <section class="quick-access">
        <h2>经理快速访问</h2>
            <section class="dashboard-cards">
                <div class="card">
                    <div class="card-content">
                        <h3>向我汇报的员工</h3>
                        <div class="value"><?php echo $employees_count;?></div>
                        <a href="<?php echo base_url();?>requests/collaborators">查看员工</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>待审批的请假请求</h3>
                        <div class="value"><?php echo $requested_leaves_count;?></div>
                        <a href="<?php echo base_url();?>requests">查看请求</a>
                    </div>
                </div>
                <?php if (($is_hr == TRUE)) { ?>
                <div class="card">
                    <div class="card-content">
                        <h3>待审批的假期银行请求</h3>
                        <div class="value"><?php echo $requested_leavebank_count;?></div>
                        <a href="<?php echo base_url();?>requests/leavebank">查看请求</a>
                    </div>
                </div>
                <?php } ?>
            </section>
        </section>
        <?php } ?>
        <section class="quick-access">
            <h2>日历快速访问</h2>
            <ul>
                <li><a href="<?php echo base_url();?>calendar/individual">我的日历</a></li>
                <li><a href="<?php echo base_url();?>calendar/year">年度日历</a></li>
                <li><a href="<?php echo base_url();?>calendar/workmates">我队友的日历</a></li>
                <li><a href="<?php echo base_url();?>calendar/department">部门日历</a></li>
            </ul>
        </section>
    </div>
</body>

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Roboto', sans-serif;
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.6)), url('<?php echo base_url();?>assets/images/login-bg2.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #444;
}
</style>

</html>
