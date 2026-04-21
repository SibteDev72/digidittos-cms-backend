<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Demo Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a0a; font-family: 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a0a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    {{-- Header with Logo --}}
                    <tr>
                        <td align="center" style="padding: 30px 0 20px;">
                            <img src="{{ $message->embed($logoPath) }}" alt="DigiDittos" width="180" style="display: block; height: auto; max-width: 180px;" />
                        </td>
                    </tr>

                    {{-- Red accent line --}}
                    <tr>
                        <td style="padding: 0 0 30px;">
                            <div style="height: 3px; background: linear-gradient(90deg, transparent, #ED1C24, transparent);"></div>
                        </td>
                    </tr>

                    {{-- Main Content Card --}}
                    <tr>
                        <td style="background-color: #111111; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); padding: 40px 36px;">

                            {{-- Badge --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: rgba(237,28,36,0.1); border-radius: 20px; padding: 6px 16px;">
                                        <span style="color: #ED1C24; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">New Demo Request</span>
                                    </td>
                                </tr>
                            </table>
                            
                            {{-- Details Table --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #666666; font-weight: 600;">Full Name</span><br>
                                        <span style="font-size: 15px; color: #ffffff; margin-top: 4px; display: inline-block;">{{ $data['first_name'] }} {{ $data['last_name'] }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #666666; font-weight: 600;">Email Address</span><br>
                                        <a href="mailto:{{ $data['email'] }}" style="font-size: 15px; color: #ED1C24; text-decoration: none; margin-top: 4px; display: inline-block;">{{ $data['email'] }}</a>
                                    </td>
                                </tr>
                                @if(!empty($data['message']))
                                <tr>
                                    <td style="padding: 14px 0;">
                                        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #666666; font-weight: 600;">Message</span><br>
                                        <span style="font-size: 15px; color: #cccccc; line-height: 1.6; margin-top: 4px; display: inline-block;">{{ $data['message'] }}</span>
                                    </td>
                                </tr>
                                @endif
                            </table>

                            {{-- CTA Button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="mailto:{{ $data['email'] }}" style="display: inline-block; background-color: #ED1C24; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 8px;">
                                            Reply to {{ $data['first_name'] }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 30px 0 10px;">
                            <p style="font-size: 11px; color: #444444; margin: 0; letter-spacing: 1px;">
                                &copy; DIGIDITTOS. ALL RIGHTS RESERVED
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
