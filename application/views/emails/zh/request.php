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
                <p>{Firstname} {Lastname} has requested time off. See the <a href="{BaseUrl}leaves/requests/{LeaveId}">details</a> below:</p>
                <!-- Leave Details Table -->
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td width="40%" style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">From</td>
                        <td width="60%" style="border: 1px solid #e0e0e0; padding: 10px;">{StartDate}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">To</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{EndDate}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Type</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Type}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Duration</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Duration} Days</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Balance</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Balance} Days</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Reason of leave</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Reason}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #e0e0e0; padding: 10px; font-weight: bold;">Last Comment</td>
                        <td style="border: 1px solid #e0e0e0; padding: 10px;">{Comments}</td>
                    </tr>
                </table>

                <!-- Action Links 
                <div style="margin-top: 20px; text-align: center;">
                    <a href="{BaseUrl}requests/accept/{LeaveId}" style="padding: 10px 20px; margin-right: 10px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 5px;">Accept</a>
                    <a href="{BaseUrl}requests?rejected={LeaveId}" style="padding: 10px 20px; background-color: #dc3545; color: #ffffff; text-decoration: none; border-radius: 5px;">Reject</a>
                </div>
                -->
            </td>
        </tr>
        <tr>
            <td style="padding: 20px; background-color: #f4f4f4; color: #777; text-align: center; font-size: 12px;">
                <hr style="border-top: 1px solid #e0e0e0;">
                <h5 style="color: #ff4444;">*** This is an automatically generated message, please do not reply to this message ***</h5>
            </td>
        </tr>
    </table>
</body>
</html>
