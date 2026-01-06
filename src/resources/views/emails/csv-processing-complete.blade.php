<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CSV Processing Complete</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .success-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CSV Processing Complete</h1>
    </div>

    <div class="content">
        <p>Hello,</p>

        <p>Your CSV dataset has been successfully processed and imported into the database.</p>

        <p><strong>File:</strong> {{ basename($filePath) }}</p>

        <p>All data has been imported successfully.</p>

        <p>If you have any questions or concerns, please don't hesitate to contact us.</p>

        <p>Best regards,<br>Woven Application Team</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
