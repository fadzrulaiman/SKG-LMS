<?php
/**
 * Email template. Anda boleh mengubah kandungan template ini
 * @since 0.1.0
 */
?>
<html lang="ms">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <!-- Main Container -->
    <table width="600" align="center" cellpadding="0" cellspacing="0" style="background-color: #ffffff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-collapse: collapse;">
        <!-- Header -->
        <tr>
            <td style="padding: 20px; background-color: #dc3545; color: #ffffff; text-align: center; font-size: 24px; font-weight: bold;">
                {Tajuk}
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 20px; font-size: 14px;">
                <p>Salam sejahtera {NamaPertama} {NamaAkhir},</p>
                <p>Malangnya, cuti yang anda minta telah ditolak. Sila hubungi pengurus anda untuk membincangkan perkara ini. Lihat butiran di bawah:</p>
                <!-- Leave Details Table -->
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td width="40%" style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Dari</td>
                        <td width="60%" style="border: 1px solid #e0e0e0; padding: 10px;">{TarikhMula} ({JenisTarikhMula})</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Hingga</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{TarikhAkhir}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Jenis</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Jenis}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Sebab cuti</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Sebab}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Komen terakhir</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Komen}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px; background-color: #f4f4f4; color: #777; text-align: center; font-size: 12px;">
                <hr style="border-top: 1px solid #e0e0e0;">
                <h5 style="color: #ff4444;">*** Ini adalah mesej yang dijana secara automatik, sila jangan balas mesej ini ***</h5>
            </td>
        </tr>
    </table>
</body>
</html>
<?php
/**
 * Email template. Anda boleh mengubah kandungan template ini
 * @since 0.1.0
 */
?>
<html lang="ms">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <!-- Main Container -->
    <table width="600" align="center" cellpadding="0" cellspacing="0" style="background-color: #ffffff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-collapse: collapse;">
        <!-- Header -->
        <tr>
            <td style="padding: 20px; background-color: #dc3545; color: #ffffff; text-align: center; font-size: 24px; font-weight: bold;">
                {Tajuk}
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 20px; font-size: 14px;">
                <p>Salam sejahtera {NamaPertama} {NamaAkhir},</p>
                <p>Malangnya, cuti yang anda minta telah ditolak. Sila hubungi pengurus anda untuk membincangkan perkara ini. Lihat butiran di bawah:</p>
                <!-- Leave Details Table -->
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td width="40%" style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Dari</td>
                        <td width="60%" style="border: 1px solid #e0e0e0; padding: 10px;">{TarikhMula} ({JenisTarikhMula})</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Hingga</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{TarikhAkhir}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Jenis</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Jenis}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Sebab cuti</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Sebab}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Komen terakhir</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Komen}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px; background-color: #f4f4f4; color: #777; text-align: center; font-size: 12px;">
                <hr style="border-top: 1px solid #e0e0e0;">
                <h5 style="color: #ff4444;">*** Ini adalah mesej yang dijana secara automatik, sila jangan balas mesej ini ***</h5>
            </td>
        </tr>
    </table>
</body>
</html>
