<?php
/**
 * Templat E-mel. Anda boleh mengubah kandungan templat ini
 * @since 0.1.0
 */
?>
<html lang="ms">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <!-- Bekas Utama -->
    <table width="600" align="center" cellpadding="0" cellspacing="0" style="background-color: #ffffff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-collapse: collapse;">
        <!-- Tajuk -->
        <tr>
            <td style="padding: 20px; background-color: #007bff; color: #ffffff; text-align: center; font-size: 24px; font-weight: bold;">
                {Title}
            </td>
        </tr>

        <!-- Kandungan -->
        <tr>
            <td style="padding: 20px; font-size: 14px;">
                <p>Sila gunakan kredensial berikut untuk <a href="{BaseURL}" style="color: #007bff;">log masuk ke dalam sistem</a>:</p>
                <!-- Jadual Kredensial -->
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td width="40%" style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Log Masuk</td>
                        <td width="60%" style="border: 1px solid #e0e0e0; padding: 10px;">{Login}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Kata Laluan</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Password}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Kaki -->
        <tr>
            <td style="padding: 20px; background-color: #f4f4f4; color: #777; text-align: center; font-size: 12px;">
                <hr style="border-top: 1px solid #e0e0e0;">
                <h5 style="color: #ff4444;">*** Ini adalah mesej yang dijana secara automatik, sila jangan balas mesej ini ***</h5>
            </td>
        </tr>
    </table>
</body>
</html>
