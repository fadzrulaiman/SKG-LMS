<?php
/**
 * Fail terjemahan
 * @copyright  Hak cipta (c) 2014-2023 Benjamin BALET
 * @license      http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 * @link            https://github.com/bbalet/jorani
 * @since         0.4.6
 */

$lang['admin_diagnostic_title'] = 'Pemeriksaan Data & Konfigurasi';
$lang['admin_diagnostic_description'] = 'Pendeteksian masalah konfigurasi dan data';
$lang['admin_diagnostic_no_error'] = 'Tiada ralat';

$lang['admin_diagnostic_requests_tab'] = 'Permintaan Cuti';
$lang['admin_diagnostic_requests_description'] = 'Permintaan Cuti diterima tetapi diduplikasi';
$lang['admin_diagnostic_requests_thead_id'] = 'ID';
$lang['admin_diagnostic_requests_thead_employee'] = 'Pekerja';
$lang['admin_diagnostic_requests_thead_start_date'] = 'Tarikh Mula';
$lang['admin_diagnostic_requests_thead_status'] = 'Status';
$lang['admin_diagnostic_requests_thead_type'] = 'Jenis';

$lang['admin_diagnostic_datetype_tab'] = 'Petang/Pagi';
$lang['admin_diagnostic_datetype_description'] = 'Permintaan Cuti dengan jenis mula/akhir yang salah.';
$lang['admin_diagnostic_datetype_thead_id'] = 'ID';
$lang['admin_diagnostic_datetype_thead_employee'] = 'Pekerja';
$lang['admin_diagnostic_datetype_thead_start_date'] = 'Tarikh';
$lang['admin_diagnostic_datetype_thead_start_type'] = 'Mula';
$lang['admin_diagnostic_datetype_thead_end_type'] = 'Akhir';
$lang['admin_diagnostic_datetype_thead_status'] = 'Status';

$lang['admin_diagnostic_entitlements_tab'] = 'Hari Yang Dibenarkan';
$lang['admin_diagnostic_entitlements_description'] = 'Senarai kontrak dan pekerja yang memiliki hak cuti lebih dari satu tahun.';
$lang['admin_diagnostic_entitlements_thead_id'] = 'ID';
$lang['admin_diagnostic_entitlements_thead_type'] = 'Jenis';
$lang['admin_diagnostic_entitlements_thead_name'] = 'Nama';
$lang['admin_diagnostic_entitlements_thead_start_date'] = 'Tarikh Mula';
$lang['admin_diagnostic_entitlements_thead_end_date'] = 'Tarikh Akhir';
$lang['admin_diagnostic_entitlements_type_contract'] = 'Kontrak';
$lang['admin_diagnostic_entitlements_type_employee'] = 'Pekerja';
$lang['admin_diagnostic_entitlements_deletion_problem'] = 'Penghapusan tidak lengkap dalam pangkalan data.' ;

$lang['admin_diagnostic_daysoff_tab'] = 'Hari Tidak Bekerja';
$lang['admin_diagnostic_daysoff_description'] = 'Bilangan hari (setiap kontrak) di mana tempoh tidak bekerja telah ditakrifkan.';
$lang['admin_diagnostic_daysoff_thead_id'] = 'ID';
$lang['admin_diagnostic_daysoff_thead_name'] = 'Nama';
$lang['admin_diagnostic_daysoff_thead_ym1'] = 'Tahun Lalu';
$lang['admin_diagnostic_daysoff_thead_y'] = 'Tahun Ini';
$lang['admin_diagnostic_daysoff_thead_yp1'] = 'Tahun Depan';

$lang['admin_diagnostic_overtime_tab'] = 'Masa Tambahan';
$lang['admin_diagnostic_overtime_description'] = 'Permintaan Masa Tambahan dengan tempoh negatif';
$lang['admin_diagnostic_overtime_thead_id'] = 'ID';
$lang['admin_diagnostic_overtime_thead_employee'] = 'Pekerja';
$lang['admin_diagnostic_overtime_thead_date'] = 'Tarikh';
$lang['admin_diagnostic_overtime_thead_duration'] = 'Tempoh';
$lang['admin_diagnostic_overtime_thead_status'] = 'Status';

$lang['admin_diagnostic_contract_tab'] = 'Kontrak';
$lang['admin_diagnostic_contract_description'] = 'Kontrak tidak digunakan (semak jika kontrak tidak diduplikasi).';
$lang['admin_diagnostic_contract_thead_id'] = 'ID';
$lang['admin_diagnostic_contract_thead_name'] = 'Nama';

$lang['admin_diagnostic_balance_tab'] = 'Baki';
$lang['admin_diagnostic_balance_description'] = 'Permintaan Cuti yang tiada hak cuti.';
$lang['admin_diagnostic_balance_thead_id'] = 'ID';
$lang['admin_diagnostic_balance_thead_employee'] = 'Pekerja';
$lang['admin_diagnostic_balance_thead_contract'] = 'Kontrak';
$lang['admin_diagnostic_balance_thead_start_date'] = 'Tarikh Mula';
$lang['admin_diagnostic_balance_thead_status'] = 'Status';

$lang['admin_diagnostic_overlapping_tab'] = 'Bertindih';
$lang['admin_diagnostic_overlapping_description'] = 'Permintaan Cuti yang bertindih dalam dua tempoh tahunan.';
$lang['admin_diagnostic_overlapping_thead_id'] = 'ID';
$lang['admin_diagnostic_overlapping_thead_employee'] = 'Pekerja';
$lang['admin_diagnostic_overlapping_thead_contract'] = 'Kontrak';
$lang['admin_diagnostic_overlapping_thead_start_date'] = 'Tarikh Mula';
$lang['admin_diagnostic_overlapping_thead_end_date'] = 'Tarikh Akhir';
$lang['admin_diagnostic_overlapping_thead_status'] = 'Status';

$lang['admin_oauthclients_title'] = 'Klien OAuth dan Sesi';
$lang['admin_oauthclients_tab_clients'] = 'Klien';
$lang['admin_oauthclients_tab_clients_description'] = 'Senarai klien yang dibenarkan menggunakan API REST';
$lang['admin_oauthclients_thead_tip_edit'] = 'Edit klien';
$lang['admin_oauthclients_thead_tip_delete'] = 'Padam klien';
$lang['admin_oauthclients_button_add'] = 'Tambah';
$lang['admin_oauthclients_popup_add_title'] = 'Tambah Klien OAuth';
$lang['admin_oauthclients_popup_select_user_title'] = 'Bersama kepada pengguna sebenar';
$lang['admin_oauthclients_error_exists'] = 'client_id ini sudah wujud';
$lang['admin_oauthclients_confirm_delete'] = 'Adakah anda pasti ingin meneruskan?';
$lang['admin_oauthclients_tab_sessions'] = 'Sesi';
$lang['admin_oauthclients_tab_sessions_description'] = 'Senarai Sesi OAuth API REST aktif';
$lang['admin_oauthclients_button_purge'] = 'Pembersihan';
?>
