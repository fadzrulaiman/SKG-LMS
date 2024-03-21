<h1> Sistem Pengurusan Cuti Kumpulan Sawit Kinabalu</h1>

<p>Selamat datang ke SKG LMS. Jangan ragu untuk mengklik ikon bantuan dalam talian untuk setiap skrin (<i class="mdi mdi-help-circle-outline"></i>).
  Ini akan memberi anda akses kepada dokumentasi ciri yang anda gunakan.</p>

<p>Jika anda seorang pekerja, anda boleh:</p>
<ul>
    <li>Melihat <a href="<?php echo base_url();?>leaves/counters">baki cuti</a> anda.</li>
    <li>Melihat <a href="<?php echo base_url();?>leaves">senarai permintaan cuti yang anda hantar</a>.</li>
    <li>Membuat permintaan <a href="<?php echo base_url();?>leaves/create">cuti baru</a>.</li>
</ul>

<br />

<p>Jika anda adalah pengurus barisan pekerja lain, anda boleh:</p>
<ul>
    <li>Mengesahkan <a href="<?php echo base_url();?>requests">permintaan cuti yang dihantar kepada anda</a>.</li>
    <?php if ($this->config->item('disable_overtime') == FALSE) { ?>
    <li>Mengesahkan <a href="<?php echo base_url();?>overtime">permintaan waktu bekerja lebih masa yang dihantar kepada anda</a>.</li>
    <?php } ?>
</ul>
