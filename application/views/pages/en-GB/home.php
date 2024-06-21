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
            <p class="welcome-message">Welcome to SKG LMS, <?php echo $fullname;?>! Easily manage your leave requests,
                track your balances, and stay updated on your team's leave trends, all in one place.</p>
        </header>
        <section class="quick-access">
            <h2>Leave Balance</h2>
            <?php if (!empty($leave_balance)): ?>
            <section class="dashboard-cards">
                <?php foreach ($leave_balance as $balance): ?>
                <div class="card">
                    <div class="card-content">
                        <h6 class="dashboard-card-title"><?php echo $balance['type_name']; ?></h6>
                        <p class="dashboard-card-metric"><?php echo $balance['balance']; ?> Days</p>
                        <small class="dashboard-card-subtext">
                            <?php echo $balance['entitled']; ?> Entitled,
                            <?php echo $balance['taken']; ?> Taken
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php else: ?>
            <p>No Leave Balance</p>
            <?php endif; ?>
            <div class="navigation-links">
                <a class="btn" href="<?php echo base_url();?>leaves/create">Create a Leave Request</a>
                <a class="btn" href="<?php echo base_url();?>leaves/counters">Leave Balance</a>
                <a class="btn" href="<?php echo base_url();?>requests">List of Leave Requests</a>
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
        <h2>Manager Quick Access</h2>
            <section class="dashboard-cards">
                <div class="card">
                    <div class="card-content">
                        <h3>Staff Reporting to Me</h3>
                        <div class="value"><?php echo $employees_count;?></div>
                        <a href="<?php echo base_url();?>requests/collaborators">View Staff</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Leave Requests Pending for Approval</h3>
                        <div class="value"><?php echo $requested_leaves_count;?></div>
                        <a href="<?php echo base_url();?>requests">View Requests</a>
                    </div>
                </div>
                <?php if (($is_hr == TRUE)) { ?>
                <div class="card">
                    <div class="card-content">
                        <h3>Leave Bank Requests Pending for Approval</h3>
                        <div class="value"><?php echo $requested_leavebank_count;?></div>
                        <a href="<?php echo base_url();?>requests/leavebank">View Requests</a>
                    </div>
                </div>
                <?php } ?>
            </section>
        </section>
        <?php } ?>
        <section class="quick-access">
            <h2>Calendar Quick Access</h2>
            <ul>
                <li><a href="<?php echo base_url();?>calendar/individual">My Calendar</a></li>
                <li><a href="<?php echo base_url();?>calendar/year">Yearly Calendar</a></li>
                <li><a href="<?php echo base_url();?>calendar/department">Department Calendar</a></li>
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