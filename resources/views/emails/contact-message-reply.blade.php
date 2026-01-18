<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .reply-box {
            background: white;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .original-message {
            background: #e5e7eb;
            padding: 15px;
            margin-top: 30px;
            border-radius: 4px;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">Khan Invoice Support</h1>
    </div>

    <div class="content">
        <p>Hi {{ $contactMessage->name }},</p>

        <p>Thank you for reaching out to us. Here's our response to your message:</p>

        <div class="reply-box">
            {!! nl2br(e($replyMessage)) !!}
        </div>

        <p>If you have any further questions, feel free to reply to this email or contact us through our website.</p>

        <p>
            Best regards,<br>
            <strong>Khan Invoice Support Team</strong>
        </p>

        <div class="original-message">
            <strong>Your Original Message:</strong><br>
            <strong>Subject:</strong> {{ $contactMessage->subject }}<br>
            <strong>Sent:</strong> {{ $contactMessage->created_at->format('M d, Y g:i A') }}<br><br>
            {{ $contactMessage->message }}
        </div>

        <div class="footer">
            <p>
                This email was sent from Khan Invoice<br>
                <a href="{{ url('/') }}" style="color: #667eea;">{{ url('/') }}</a>
            </p>
        </div>
    </div>
</body>
</html>
