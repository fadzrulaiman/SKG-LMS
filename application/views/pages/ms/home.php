<!DOCTYPE html>
<html lang="ms">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka SKG LMS</title>
    <link rel="stylesheet" href="<?php echo base_url();?>assets/css/home/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1 class="main-heading">Sistem Pengurusan Cuti Kumpulan Sawit Kinabalu</h1>
            <p class="welcome-message">Selamat datang ke SKG LMS, <?php echo $fullname;?>! Uruskan Permohonan cuti anda dengan mudah, jejak baki cuti anda, dan kekal dikemaskini dengan trend cuti pasukan anda, semuanya di satu tempat.</p>
        </header>
        <section class="quick-access">
            <h2>Baki Cuti</h2>
            <?php if (!empty($leave_balance)): ?>
            <section class="dashboard-cards">
                <?php foreach ($leave_balance as $balance): ?>
                <div class="card">
                    <div class="card-content">
                        <h6 class="dashboard-card-title"><?php echo $balance['type_name']; ?></h6>
                        <p class="dashboard-card-metric"><?php echo $balance['balance']; ?> Hari</p>
                        <small class="dashboard-card-subtext">
                            <?php echo $balance['entitled']; ?> Layak,
                            <?php echo $balance['taken']; ?> Diambil
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php else: ?>
            <p>Tiada Baki Cuti</p>
            <?php endif; ?>
            <div class="navigation-links">
                <a class="btn" href="<?php echo base_url();?>leaves/create">Buat Permohonan Cuti</a>
                <a class="btn" href="<?php echo base_url();?>leaves/counters">Baki Cuti</a>
                <a class="btn" href="<?php echo base_url();?>requests">Senarai Permohonan Cuti</a>
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
        <h2>Akses Pantas Pengurus</h2>
            <section class="dashboard-cards">
                <div class="card">
                    <div class="card-content">
                        <h3>Kakitangan di Bawah Seliaan Saya</h3>
                        <div class="value"><?php echo $employees_count;?></div>
                        <a href="<?php echo base_url();?>requests/collaborators">Lihat Kakitangan</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Permohonan Cuti yang Menunggu Kelulusan</h3>
                        <div class="value"><?php echo $requested_leaves_count;?></div>
                        <a href="<?php echo base_url();?>requests">Lihat Permohonan</a>
                    </div>
                </div>
                <?php if (($is_hr == TRUE)) { ?>
                <div class="card">
                    <div class="card-content">
                        <h3>Permohonan Bank Cuti yang Menunggu Kelulusan</h3>
                        <div class="value"><?php echo $requested_leavebank_count;?></div>
                        <a href="<?php echo base_url();?>requests/leavebank">Lihat Permohonan</a>
                    </div>
                </div>
                <?php } ?>
            </section>
        </section>
        <?php } ?>
        <section class="quick-access">
            <h2>Akses Pantas Kalendar</h2>
            <ul>
                <li><a href="<?php echo base_url();?>calendar/individual">Kalendar Saya</a></li>
                <li><a href="<?php echo base_url();?>calendar/year">Kalendar Tahunan</a></li>
                <li><a href="<?php echo base_url();?>calendar/department">Kalendar Jabatan</a></li>
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
