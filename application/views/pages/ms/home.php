<!DOCTYPE html>
<html lang="ms">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Pemuka Sistem Pengurusan Cuti SKG</title>
    <link rel="stylesheet" href="<?php echo base_url();?>assets/css/home/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>

<body>
    <div class="dashboard-container">
        <header>
            <h1 class="main-heading">Sistem Pengurusan Cuti Kumpulan Sawit Kinabalu</h1>
            <p class="welcome-message">Selamat datang ke SKG LMS, <?php echo $fullname;?>! Mudah mengurus permintaan cuti anda, jejak baki cuti anda, dan kekalan permintaan cuti pasukan anda, semua dalam satu tempat.</p>
        </header>

        <!-- Bahagian Akses Cepat -->
        <section class="quick-access">
            <h2>Akses Pantas Kalendar</h2>
            <ul>
                <li><a href="<?php echo base_url();?>calendar/individual">Kalendar Saya</a></li>
                <li><a href="<?php echo base_url();?>calendar/year">Kalendar Tahunan</a></li>
                <li><a href="<?php echo base_url();?>calendar/department">Kalendar Jabatan</a></li>
            </ul>
        </section>

        <!-- Bahagian Pekerja -->
        <section class="employee-section">
            <h2>Untuk Pekerja</h2>
            <ul>
                <li>Lihat <a href="<?php echo base_url();?>leaves/counters">baki cuti anda</a>.</li>
                <li>Periksa senarai <a href="<?php echo base_url();?>leaves">permintaan cuti yang anda hantar</a>.</li>
                <li>Buat permintaan <a href="<?php echo base_url();?>leaves/create">cuti baru</a>.</li>
            </ul>
        </section>

        <!-- Bahagian Pengurus -->
        <section class="manager-section">
            <h2>Untuk Pengurus</h2>
            <ul>
                <li>Luluskan <a href="<?php echo base_url();?>requests">permintaan cuti yang dihantar kepada anda</a>.</li>
                <?php if ($this->config->item('disable_overtime') == FALSE) { ?>
                <li>Luluskan <a href="<?php echo base_url();?>overtime">permintaan kerja lebih masa yang dihantar kepada anda</a>.</li>
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
