@php
  // Normalise fields — the form can skip company/budget.
  $name    = $data['name']    ?? 'Visitor';
  $email   = $data['email']   ?? '';
  $company = $data['company'] ?? '';
  $service = $data['service'] ?? '';
  $budget  = $data['budget']  ?? '';
  $message = $data['message'] ?? '';
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>New Contact Form Submission — DigiDittos</title>
</head>
<body style="margin:0;padding:0;background:#05090f;font-family:'Segoe UI',Roboto,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#05090f;padding:40px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#0b1220;border:1px solid #1f2937;border-radius:16px;overflow:hidden;">

          {{-- Header / Logo --}}
          <tr>
            <td align="center" style="padding:36px 24px 24px 24px;background:linear-gradient(135deg,#0b2430 0%,#006878 100%);">
              <a href="{{ $siteUrl }}" style="display:inline-block;text-decoration:none;">
                <img src="{{ $logoUrl }}" alt="DigiDittos" width="160" style="display:block;border:0;outline:none;max-width:160px;height:auto;" />
              </a>
            </td>
          </tr>

          {{-- Title --}}
          <tr>
            <td style="padding:32px 24px 8px 24px;text-align:center;">
              <div style="display:inline-block;padding:6px 14px;background:rgba(100,168,184,0.12);border:1px solid rgba(100,168,184,0.3);border-radius:999px;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;color:#64a8b8;font-weight:600;">
                New Lead
              </div>
              <h1 style="margin:18px 0 8px 0;font-family:'Segoe UI',Roboto,Arial,sans-serif;font-size:26px;font-weight:700;color:#ffffff;line-height:1.25;">
                New Contact Form Submission
              </h1>
              <p style="margin:0;font-size:14px;color:#94a3b8;line-height:1.55;">
                {{ $submittedAt }}
              </p>
            </td>
          </tr>

          {{-- Details --}}
          <tr>
            <td style="padding:24px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0b1220;border:1px solid #1f2937;border-radius:12px;overflow:hidden;">
                @foreach ([
                  ['Full Name', e($name)],
                  ['Email', '<a href="mailto:' . e($email) . '" style="color:#64a8b8;text-decoration:none;">' . e($email) . '</a>'],
                  ['Company', e($company ?: '—')],
                  ['Service', e($service ?: '—')],
                  ['Budget', e($budget ?: '—')],
                  ['Message', nl2br(e($message))],
                ] as $row)
                  <tr>
                    <td style="padding:14px 24px;border-bottom:1px solid #1f2937;background:#0b1220;">
                      <div style="font-family:'Segoe UI',Roboto,Arial,sans-serif;font-size:11px;letter-spacing:1.4px;text-transform:uppercase;color:#64a8b8;font-weight:600;margin-bottom:6px;">{{ $row[0] }}</div>
                      <div style="font-family:'Segoe UI',Roboto,Arial,sans-serif;font-size:15px;color:#f3f4f6;line-height:1.55;">{!! $row[1] !!}</div>
                    </td>
                  </tr>
                @endforeach
              </table>
            </td>
          </tr>

          {{-- CTA --}}
          @if ($email)
          <tr>
            <td align="center" style="padding:8px 24px 32px 24px;">
              <a href="mailto:{{ $email }}" style="display:inline-block;padding:14px 28px;background:#06aabf;color:#ffffff;text-decoration:none;border-radius:999px;font-size:14px;font-weight:600;letter-spacing:0.3px;">
                Reply to {{ $name }}
              </a>
            </td>
          </tr>
          @endif

          {{-- Footer --}}
          <tr>
            <td style="padding:24px;background:#05090f;border-top:1px solid #1f2937;text-align:center;">
              <p style="margin:0 0 6px 0;font-size:12px;color:#64748b;">
                This message was sent from the contact form on
                <a href="{{ $siteUrl }}" style="color:#64a8b8;text-decoration:none;">digidittos.com</a>
              </p>
              <p style="margin:0;font-size:11px;color:#475569;">
                &copy; {{ date('Y') }} DigiDittos. All rights reserved.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
