<?php
/**
 * Email template. You can change the content of this template
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
            <td style="padding: 20px; background-color: #28a745; color: #ffffff; text-align: center; font-size: 24px; font-weight: bold;">
                {Title}
            </td>
        </tr>

        <!-- Kandungan -->
        <tr>
            <td style="padding: 20px; font-size: 14px;">
                <p>Kepada {Firstname} {Lastname},</p>
                <p"Cuti yang anda minta telah diluluskan. Lihat butiran di bawah:</p>
                <!-- Jadual Butiran Cuti -->
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td width="40%" style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Dari</td>
                        <td width="60%" style="border: 1px solid #e0e0e0; padding: 10px;">{StartDate} </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Ke</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{EndDate}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Jenis</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Type}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Sebab cuti</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Cause}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Komen terakhir</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Comments}</td>
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
