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
                <h3><a href="<?php echo base_url();?>leaves/create">Create a Leave Request</a></h3>
                <h3><a href="<?php echo base_url();?>requests">List of Leave Requests</a></h3>
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

header {
    text-align: center;
    margin-bottom: 30px;
}

.main-heading {
    font-size: 2.5em;
    color: #444;
    margin: 0;
}

.welcome-message {
    font-size: 1.2em;
    color: #444;
}

section {
    margin-bottom: 30px;
}

.quick-access ul,
.employee-section ul,
.manager-section ul {
    list-style: none;
    padding: 0;
}

.quick-access li,
.employee-section li,
manager-section li {
    margin-bottom: 10px;
}

.quick-access a,
.employee-section a,
manager-section a {
    color: #1e90ff;
    text-decoration: none;
}

quick-access a:hover,
employee-section a:hover,
manager-section a:hover {
    text-decoration: underline;
}

.dashboard-cards {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: nowrap;
    margin-bottom: 5px;
    /* Added to reduce the gap */
}

.card {
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    flex: 1 1 0;
    margin-bottom: 20px;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.card-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.card h3 {
    margin-top: 0;
    font-size: 1.5em;
    color: #333;
}

.card .value {
    font-size: 3em;
    font-weight: bold;
    color: #333;
}

.card a {
    margin-top: 10px;
    color: #1e90ff;
    text-decoration: none;
}

.card a:hover {
    text-decoration: underline;
}

.dashboard-cards-wrapper {
    overflow-x: auto;
    width: 100%;
    padding: 10px 0;
}

.dashboard-cards.d-flex {
    display: flex;
    gap: 20px;
    width: 100%;
    flex-wrap: nowrap;
}

.dashboard-card {
    background-color: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    flex: 1 1 0;
    margin-bottom: 5px;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.dashboard-card-title {
    font-size: 16px;
    color: #6c757d;
    margin-bottom: 5px;
}

.dashboard-card-metric {
    font-size: 32px;
    font-weight: bold;
    color: #343a40;
    margin-bottom: 5x;
}

.dashboard-card-subtext {
    font-size: 12px;
    color: #6c757d;
}

.navigation-links {
    text-align: center;
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 5px 0;
    /* Changed from 20px to 10px to reduce the gap */
}

.navigation-links a.btn {
    display: inline-block;
    margin: 5px;
    padding: 10px 20px;
    background-color: #1e90ff;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 16px;
}

.navigation-links a.btn:hover {
    background-color: #1c86ee;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-cards {
        flex-wrap: wrap;
    }

    .card {
        flex: 1 1 100%;
    }

    .navigation-links {
        flex-direction: column;
    }
}
</style>

</html>