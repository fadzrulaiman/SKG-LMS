<?php
/**
 * Email template. You can change the content of this template
 * @since 0.1.0
 */
?>
<html lang="en">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <!-- Main Container -->
    <table width="600" align="center" cellpadding="0" cellspacing="0" style="background-color: #ffffff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-collapse: collapse;">
        <!-- Header -->
        <tr>
            <td style="padding: 20px; background-color: #007bff; color: #ffffff; text-align: center; font-size: 24px; font-weight: bold;">
                {Title}
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 20px; font-size: 14px;">
                <p>Welcome to Sawit Kinabalu Group Leave Management System, {Firstname} {Lastname}. Please use the credentials below to <a href="{BaseURL}" style="color: #007bff;">login to the system</a>:</p>
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td width="40%" style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Login</td>
                        <td width="60%" style="border: 1px solid #e0e0e0; padding: 10px;">{Login}</td>
                    </tr>
                    <tr>
                        <?php if ($this->config->item('ldap_enabled') == FALSE) { ?>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Password</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Password}</td>
                        <?php } else { ?>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Password</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;"><i>The same password you use to sign in to your operating system (Windows, Linux, etc.)</i></td>
                        <?php } ?>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px; background-color: #f4f4f4; color: #777; text-align: center; font-size: 12px;">
                <hr style="border-top: 1px solid #e0e0e0;">
                <h5 style="color: #ff4444;">*** This is an automatically generated message, please do not reply to this message ***</h5>
            </td>
        </tr>
    </table>
</body>
</html>
