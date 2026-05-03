<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f7f9;">
    <div
        style="font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; border: 1px solid #e0e0e0; border-top: 8px solid #6a00b0; padding: 40px; max-width: 600px; margin: 20px auto; color: #3c4043; background-color: #ffffff;">

        <div style="text-align: center; margin-bottom: 20px;">
            <img src="https://senco.accsangkaychatbot.com/header_img.png" alt="SENCO Header"
                style="max-width: 100%; height: auto;">
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="color: #6a00b0; margin: 0;">Official Payment Receipt</h2>
            <p style="color: #70757a; font-size: 12px; text-transform: uppercase; font-weight: bold;">
                SENCO Graduating Class of 2026
            </p>
        </div>

        <p>Hello <strong>{{ $name }}</strong>,</p>
        <p style="line-height: 1.6;">{{ $introMessage }}</p>

        <div
            style="background-color: #f8f9fa; border-radius: 8px; padding: 25px; margin: 20px 0; border: 1px solid #f1f3f4;">
            <table style="width: 100%; font-size: 15px; border-collapse: collapse;">
                <tr>
                    <td style="color: #70757a; padding: 5px 0;">Receipt ID:</td>
                    <td style="text-align: right; font-family: monospace;">{{ $receiptId }}</td>
                </tr>
                <tr>
                    <td style="color: #70757a; padding: 5px 0;">Name:</td>
                    <td style="text-align: right; font-family: monospace;">{{ $name }}</td>
                </tr>
                <tr>
                    <td style="color: #70757a; padding: 5px 0;">Payment Date:</td>
                    <td style="text-align: right;">{{ $formattedDate }}</td>
                </tr>
                <tr>
                    <td style="color: #70757a; padding: 5px 0;">Status:</td>
                    <td style="text-align: right;">{{ $statusDisplay }}</td>
                </tr>
                <tr>
                    <td style="color: #70757a; padding: 15px 0 5px 0; border-top: 1px solid #e8eaed;">Amount Paid:</td>
                    <td
                        style="text-align: right; font-weight: bold; color: #6a00b0; font-size: 18px; border-top: 1px solid #6a00b0; padding-top: 15px;">
                        ₱{{ number_format($amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div>
            {!! $noteContent !!}
        </div>

        <div style="margin-top: 10px">
            <p>Use the provided credentials to view your payment history on our payment tracker web portal.</p>
        </div>

        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:12px 16px; margin:20px 0;">
            <div style="font-size:12px; color:#6b7280; margin-bottom: 4px;">
                Student ID: <span style="font-size:14px; font-weight:600; color:#111827;">{{ $id }}</span>
            </div>
            <div style="font-size:12px; color:#6b7280;">
                Portal Code: <span
                    style="font-size:14px; font-weight:600; color:#111827;">{{ $portalCode ?? 'N/A' }}</span>
            </div>
            <div style="font-size:11px; color:#9ca3af; margin-top:8px;">
                Do not share your credentials.
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="https://senco.accsangkaychatbot.com/"
                style="background-color: #6a00b0; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                Access Payment Tracking Portal
            </a>
        </div>

        <div style="padding-top: 20px; font-size: 11px; color: #9aa0a6; text-align: center; line-height: 1.5;">
            <p style="margin: 0;">This is an automated receipt from the <strong>SENCO Finance Committee</strong>.</p>
            <p style="margin: 4px 0;">For discrepancies, please contact the Treasury directly.</p>

            {{-- Copyright Section --}}
            <div style="margin-top: 20px; color: #bdc1c6; text-transform: uppercase;">
                &copy; {{ date('Y') }} SENCO Finance Committee. All rights reserved.
                <br>
            </div>
        </div>
    </div>
</body>

</html>
