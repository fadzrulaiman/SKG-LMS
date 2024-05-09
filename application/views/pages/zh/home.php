<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKG 人事管理系统仪表板</title>
    <link rel="stylesheet" href="<?php echo base_url();?>assets/css/home/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1 class="main-heading">Sawit Kinabalu Group 假期管理系统</h1>
            <p class="welcome-message">欢迎使用 SKG LMS，<?php echo $fullname;?>！轻松管理您的请假请求、跟踪您的假期余额，并了解您团队的请假趋势，一切尽在一处。</p>
        </header>

        <!-- 快速访问部分 -->
        <section class="quick-access">
            <h2>日历快速访问</h2>
            <ul>
                <li><a href="<?php echo base_url();?>calendar/individual">我的日历</a></li>
                <li><a href="<?php echo base_url();?>calendar/year">年度日历</a></li>
                <li><a href="<?php echo base_url();?>calendar/department">部门日历</a></li>
            </ul>
        </section>

        <!-- 员工部分 -->
        <section class="employee-section">
            <h2>员工使用</h2>
            <ul>
                <li>查看您的 <a href="<?php echo base_url();?>leaves/counters">假期余额</a>。</li>
                <li>检查您提交的 <a href="<?php echo base_url();?>leaves">请假请求列表</a>。</li>
                <li>申请一个 <a href="<?php echo base_url();?>leaves/create">新的假期</a>。</li>
            </ul>
        </section>

        <!-- 管理员部分 -->
        <section class="manager-section">
            <h2>管理员使用</h2>
            <ul>
                <li>批准提交给您的 <a href="<?php echo base_url();?>requests">请假请求</a>。</li>
                <?php if ($this->config->item('disable_overtime') == FALSE) { ?>
                <li>批准提交给您的 <a href="<?php echo base_url();?>overtime">加班请求</a>。</li>
                <?php } ?>
            </ul>
        </section>
    </div>
</body>

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Roboto', sans-serif;
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo base_url();?>assets/images/login-bg.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #444;
}
</style>
</html>
