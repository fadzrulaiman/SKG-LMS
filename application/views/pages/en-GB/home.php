<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKG LMS Dashboard</title>
    <link rel="stylesheet" href="<?php echo base_url();?>assets/css/home/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1 class="main-heading">Sawit Kinabalu Group Leave Management System</h1>
            <p class="welcome-message">Welcome to SKG LMS, <?php echo $fullname;?>! Easily manage your leave requests, track your balances, and stay updated on your team's leave trends, all in one place.</p>
        </header>

        <!-- Quick Access Section -->
        <section class="quick-access">
            <h2>Calendar Quick Access</h2>
            <ul>
                <li><a href="<?php echo base_url();?>calendar/individual">My Calendar</a></li>
                <li><a href="<?php echo base_url();?>calendar/year">Yearly Calendar</a></li>
                <li><a href="<?php echo base_url();?>calendar/department">Department Calendar</a></li>
            </ul>
        </section>

        <!-- Employee Section -->
        <section class="employee-section">
            <h2>For Employees</h2>
            <ul>
                <li>View your <a href="<?php echo base_url();?>leaves/counters">leave balance</a>.</li>
                <li>Check the <a href="<?php echo base_url();?>leaves">list of leave requests you have submitted</a>.</li>
                <li>Request a <a href="<?php echo base_url();?>leaves/create">new leave</a>.</li>
            </ul>
        </section>

        <!-- Manager Section -->
        <section class="manager-section">
            <h2>For Managers</h2>
            <ul>
                <li>Approve <a href="<?php echo base_url();?>requests">leave requests submitted to you</a>.</li>
                <?php if ($this->config->item('disable_overtime') == FALSE) { ?>
                <li>Approve <a href="<?php echo base_url();?>overtime">overtime requests submitted to you</a>.</li>
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
