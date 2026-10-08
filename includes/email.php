
<?php



require_once __DIR__ . '/../config/email.php';


/*
|--------------------------------------------------------------------------
| EMAIL LOG
|--------------------------------------------------------------------------
*/

function email_log(string $message): void
{
    $dir = __DIR__ . '/../storage';

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    @file_put_contents(
        $dir . '/email.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}


/*
|--------------------------------------------------------------------------
| SMTP READ
|--------------------------------------------------------------------------
*/

function smtp_read($socket): string
{
    $data = '';

    while (!feof($socket)) {

        $line = fgets($socket, 515);

        if ($line === false) {
            break;
        }

        $data .= $line;

        /*
         * SMTP multiline response ends when character 4 is a space.
         */
        if (
            strlen($line) >= 4 &&
            isset($line[3]) &&
            $line[3] === ' '
        ) {
            break;
        }
    }

    return $data;
}


/*
|--------------------------------------------------------------------------
| SMTP EXPECT
|--------------------------------------------------------------------------
*/

function smtp_expect($socket, array $codes): void
{
    $reply = smtp_read($socket);

    $trimmed = trim($reply);

    $code = (int) substr($trimmed, 0, 3);

    if (!in_array($code, $codes, true)) {

        throw new RuntimeException(
            'SMTP server rejected command: ' . $trimmed
        );
    }
}


/*
|--------------------------------------------------------------------------
| SMTP COMMAND
|--------------------------------------------------------------------------
*/

function smtp_cmd($socket, string $command, array $codes): void
{
    fwrite($socket, $command . "\r\n");

    smtp_expect($socket, $codes);
}


/*
|--------------------------------------------------------------------------
| HEADER CLEANING
|--------------------------------------------------------------------------
*/

function normalize_header_value(string $value): string
{
    return trim(
        str_replace(
            ["\r", "\n"],
            '',
            $value
        )
    );
}


/*
|--------------------------------------------------------------------------
| HTML EMAIL TEMPLATE
|--------------------------------------------------------------------------
*/

function email_html_template(
    string $title,
    string $bodyHtml
): string {

    $church = htmlspecialchars(
        defined('FGCK_RUNTIME_EMAIL_FROM_NAME')
            ? FGCK_RUNTIME_EMAIL_FROM_NAME
            : 'FGCK Joyland',
        ENT_QUOTES,
        'UTF-8'
    );

    $safeTitle = htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    /*
     * Central church identity/signature.
     * Keeping this here means every email sent through send_system_email()
     * receives the same professional FGCK Joyland message automatically.
     */
    $churchSignature = '
<div style="
    margin:30px 0 0;
    padding:20px 18px;
    text-align:center;
    background:#fff8f8;
    border:1px solid #f1d6d6;
    border-radius:12px;
">
    <div style="
        margin:0 0 8px;
        font-size:11px;
        line-height:1.4;
        font-weight:700;
        letter-spacing:1.4px;
        text-transform:uppercase;
        color:#8b0000;
    ">
        FGCK JOYLAND
    </div>

    <div style="
        margin:0;
        font-size:15px;
        line-height:1.6;
        font-weight:700;
        font-style:italic;
        color:#b71c1c;
    ">
        As members of FGCK Joyland, we are
        <br>
        <span style="
            font-size:16px;
            font-weight:800;
            letter-spacing:.25px;
        ">
            Perfected To Influence The World
        </span>
    </div>
</div>';

    return '
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>' . $safeTitle . '</title>
</head>

<body style="
margin:0;
padding:0;
background:#f5f8fb;
font-family:Arial,Helvetica,sans-serif;
color:#172b3a;
">

<div style="
width:100%;
max-width:680px;
margin:30px auto;
background:#ffffff;
border:1px solid #e5ebf0;
border-radius:18px;
overflow:hidden;
">

<!-- Email Header -->
<div style="
background:#102a43;
color:#ffffff;
padding:25px 28px;
">

<div style="
font-size:20px;
line-height:1.35;
font-weight:700;
">
' . $safeTitle . '
</div>

<div style="
font-size:12px;
line-height:1.5;
color:#d7e6f1;
margin-top:6px;
">
' . $church . '
</div>

</div>

<!-- Email Content -->
<div style="
padding:28px;
line-height:1.65;
font-size:14px;
">

' . $bodyHtml . '

' . $churchSignature . '

</div>

<!-- Email Footer -->
<div style="
padding:18px 28px;
background:#f7fafc;
color:#718096;
font-size:11px;
line-height:1.6;
text-align:center;
">
This is an automated message from the<br>
FGCK Joyland Appointment System.<br>
Please do not reply to this email.
</div>

</div>

</body>
</html>';
}

/*
|--------------------------------------------------------------------------
| HOST MAIL() FALLBACK
|--------------------------------------------------------------------------
*/

function send_native_email(string $to, string $toName, string $subject, string $html): bool
{
    $to = normalize_header_value($to);
    $toName = normalize_header_value($toName);
    $subject = normalize_header_value($subject);
    $from = normalize_header_value((string) FGCK_RUNTIME_EMAIL_FROM);
    $replyTo = normalize_header_value((string) FGCK_RUNTIME_EMAIL_REPLY_TO);
    $fromName = normalize_header_value((string) FGCK_RUNTIME_EMAIL_FROM_NAME);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        email_log('NATIVE MAIL ERROR: invalid recipient or sender.');
        return false;
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $plain = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html) ?: $html), ENT_QUOTES, 'UTF-8'));

    $headers = [
        'MIME-Version: 1.0',
        'From: ' . $encodedFromName . ' <' . $from . '>',
        'Reply-To: <' . $replyTo . '>',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: FGCK-Joyland-Pastor-Portal',
    ];

    $body = '<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
    $sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));

    if ($sent) {
        email_log('HOST MAIL() ACCEPTED message for ' . $to . ' | Subject: ' . $subject);
        return true;
    }

    email_log('HOST MAIL() FAILED for ' . $to . ' | Subject: ' . $subject);
    return false;
}


/*
|--------------------------------------------------------------------------
| SEND SMTP EMAIL
|--------------------------------------------------------------------------
*/

function send_smtp_email(
    string $to,
    string $toName,
    string $subject,
    string $html
): bool {

    /*
     * Email can be delivered through configured SMTP or, when SMTP credentials
     * are absent, through the hosting provider's PHP mail() service.
     * Explicitly setting FGCK_EMAIL_ENABLED=false disables both methods.
     */
    if (!defined('FGCK_RUNTIME_EMAIL_ENABLED') || !FGCK_RUNTIME_EMAIL_ENABLED) {
        email_log('EMAIL DISABLED. Recipient: ' . $to);
        return false;
    }

    $to = normalize_header_value($to);
    $toName = normalize_header_value($toName);
    $subject = normalize_header_value($subject);


    /*
     * Validate recipient.
     */

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {

        email_log(
            'INVALID RECIPIENT: ' . $to
        );

        return false;
    }

    // If no SMTP username/password has been supplied, use the hosting mail
    // service. This is the normal zero-configuration path on many Plesk hosts.
    if (trim((string) FGCK_RUNTIME_SMTP_USERNAME) === '' || trim((string) FGCK_RUNTIME_SMTP_PASSWORD) === '') {
        return send_native_email($to, $toName, $subject, $html);
    }


    /*
     * Make sure required SMTP settings exist.
     */

    $required = [
        'FGCK_RUNTIME_SMTP_HOST',
        'FGCK_RUNTIME_SMTP_PORT',
        'FGCK_RUNTIME_SMTP_ENCRYPTION',
        'FGCK_RUNTIME_SMTP_USERNAME',
        'FGCK_RUNTIME_SMTP_PASSWORD',
        'FGCK_RUNTIME_EMAIL_FROM',
        'FGCK_RUNTIME_EMAIL_FROM_NAME',
        'FGCK_RUNTIME_EMAIL_REPLY_TO'
    ];

    foreach ($required as $constant) {

        if (!defined($constant)) {

            email_log(
                'CONFIGURATION ERROR: Missing constant ' . $constant
            );

            return false;
        }
    }


    $errno = 0;
    $errstr = '';
    $socket = null;


    try {

        $encryption = strtolower(
            trim((string) FGCK_RUNTIME_SMTP_ENCRYPTION)
        );


        /*
         * Gmail:
         *
         * TLS  -> smtp.gmail.com:587
         * SSL  -> smtp.gmail.com:465
         */

        if ($encryption === 'ssl') {

            $transport =
                'ssl://' .
                FGCK_RUNTIME_SMTP_HOST;

        } else {

            $transport =
                FGCK_RUNTIME_SMTP_HOST;
        }


        /*
         * Connect to SMTP server.
         */

        email_log(
            'SMTP CONNECT: ' .
            FGCK_RUNTIME_SMTP_HOST .
            ':' .
            FGCK_RUNTIME_SMTP_PORT .
            ' encryption=' .
            $encryption
        );


        $socket = @fsockopen(
            $transport,
            (int) FGCK_RUNTIME_SMTP_PORT,
            $errno,
            $errstr,
            8
        );


        if (!$socket) {

            throw new RuntimeException(
                'Connection failed: ' .
                $errstr .
                ' (' .
                $errno .
                ')'
            );
        }


        stream_set_timeout(
            $socket,
            8
        );


        /*
         * Server greeting.
         */

        smtp_expect(
            $socket,
            [220]
        );


        /*
         * EHLO.
         */

        smtp_cmd(
            $socket,
            'EHLO fgckjoyland.local',
            [250]
        );


        /*
         * STARTTLS.
         */

        if ($encryption === 'tls') {

            smtp_cmd(
                $socket,
                'STARTTLS',
                [220]
            );


            $crypto = @stream_socket_enable_crypto(
                $socket,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );


            if ($crypto !== true) {

                throw new RuntimeException(
                    'TLS negotiation failed.'
                );
            }


            /*
             * EHLO again after TLS.
             */

            smtp_cmd(
                $socket,
                'EHLO fgckjoyland.local',
                [250]
            );
        }


        /*
         * AUTH LOGIN.
         */

        smtp_cmd(
            $socket,
            'AUTH LOGIN',
            [334]
        );


        smtp_cmd(
            $socket,
            base64_encode(
                (string) FGCK_RUNTIME_SMTP_USERNAME
            ),
            [334]
        );


        smtp_cmd(
            $socket,
            base64_encode(
                (string) FGCK_RUNTIME_SMTP_PASSWORD
            ),
            [235]
        );


        /*
         * MAIL FROM.
         */

        $fromEmail =
            normalize_header_value(
                (string) FGCK_RUNTIME_EMAIL_FROM
            );

        smtp_cmd(
            $socket,
            'MAIL FROM:<' . $fromEmail . '>',
            [250]
        );


        /*
         * RECIPIENT.
         */

        smtp_cmd(
            $socket,
            'RCPT TO:<' . $to . '>',
            [250, 251]
        );


        /*
         * DATA.
         */

        smtp_cmd(
            $socket,
            'DATA',
            [354]
        );


        /*
         * Email headers.
         */

        $safeSubject =
            '=?UTF-8?B?' .
            base64_encode($subject) .
            '?=';


        $safeName =
            '=?UTF-8?B?' .
            base64_encode(
                (string) FGCK_RUNTIME_EMAIL_FROM_NAME
            ) .
            '?=';


        $replyTo =
            normalize_header_value(
                (string) FGCK_RUNTIME_EMAIL_REPLY_TO
            );


        $boundary =
            '=_FGCK_' .
            bin2hex(
                random_bytes(12)
            );


        $headers = [

            'Date: ' .
            date(DATE_RFC2822),

            'From: ' .
            $safeName .
            ' <' .
            $fromEmail .
            '>',

            'To: ' .
            (
                $toName !== ''
                    ? '=?UTF-8?B?' .
                      base64_encode($toName) .
                      '?= <' .
                      $to .
                      '>'
                    : '<' . $to . '>'
            ),

            'Reply-To: <' .
            $replyTo .
            '>',

            'Subject: ' .
            $safeSubject,

            'MIME-Version: 1.0',

            'Content-Type: multipart/alternative; boundary="' .
            $boundary .
            '"',

            'X-Mailer: FGCK-Makutano-West-Joyland'
        ];


        /*
         * Plain-text version.
         */

        $plainHtml =
            preg_replace(
                '/<br\s*\/?>/i',
                "\n",
                $html
            );


        $plain =
            trim(
                html_entity_decode(
                    strip_tags(
                        (string) $plainHtml
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );


        /*
         * Build message.
         */

        $message =
            implode(
                "\r\n",
                $headers
            ) .
            "\r\n\r\n";


        /*
         * Plain text part.
         */

        $message .=
            '--' .
            $boundary .
            "\r\n";

        $message .=
            "Content-Type: text/plain; charset=UTF-8\r\n";

        $message .=
            "Content-Transfer-Encoding: 8bit\r\n\r\n";

        $message .=
            $plain .
            "\r\n\r\n";


        /*
         * HTML part.
         */

        $message .=
            '--' .
            $boundary .
            "\r\n";

        $message .=
            "Content-Type: text/html; charset=UTF-8\r\n";

        $message .=
            "Content-Transfer-Encoding: 8bit\r\n\r\n";

        $message .=
            $html .
            "\r\n\r\n";


        /*
         * End multipart message.
         */

        $message .=
            '--' .
            $boundary .
            "--\r\n";


        /*
         * Dot-stuffing.
         */

        $message =
            preg_replace(
                '/^\./m',
                '..',
                $message
            );


        /*
         * SMTP DATA terminator.
         */

        fwrite(
            $socket,
            $message .
            "\r\n.\r\n"
        );


        smtp_expect(
            $socket,
            [250]
        );


        /*
         * Quit.
         */

        fwrite(
            $socket,
            "QUIT\r\n"
        );


        fclose($socket);


        email_log(
            'EMAIL SENT SUCCESSFULLY to ' .
            $to .
            ' | Subject: ' .
            $subject
        );


        return true;


    } catch (Throwable $e) {

        if (
            $socket !== null &&
            is_resource($socket)
        ) {
            @fclose($socket);
        }


        $smtpError = $e->getMessage();
        email_log(
            'SMTP ERROR for ' .
            $to .
            ': ' .
            $smtpError
        );

        // Shared hosting may block outbound SMTP or a provider may temporarily
        // reject authentication. Fall back to the host's configured mail()
        // service so ordinary notifications still have a second delivery path.
        $fallback = send_native_email($to, $toName, $subject, $html);
        if ($fallback) {
            email_log('HOST MAIL() FALLBACK ACCEPTED after SMTP failure for ' . $to . ' | Subject: ' . $subject);
            return true;
        }

        email_log('EMAIL DELIVERY FAILED on both SMTP and HOST MAIL() for ' . $to . ' | Subject: ' . $subject);
        return false;
    }
}


/*
|--------------------------------------------------------------------------
| GENERIC SYSTEM EMAIL
|--------------------------------------------------------------------------
*/

function send_system_email(
    string $to,
    string $toName,
    string $subject,
    string $bodyHtml
): bool {

    $html =
        email_html_template(
            $subject,
            $bodyHtml
        );

    return send_smtp_email(
        $to,
        $toName,
        $subject,
        $html
    );
}


/*
|--------------------------------------------------------------------------
| PASTOR NOTIFICATION
|--------------------------------------------------------------------------
|
| Called after a member successfully creates an appointment.
|
*/

function email_pastors_about_booking(
    PDO $p,
    int $appointmentId
): bool {

    email_log(
        'START PASTOR NOTIFICATION. Appointment ID=' .
        $appointmentId
    );


    try {

        /*
         * Get appointment information.
         */

        $q = $p->prepare("
            SELECT
                a.id,
                a.appointment_no,
                a.purpose,
                a.status,

                m.full_name AS member_name,
                m.phone AS member_phone,
                m.email AS member_email,

                s.appointment_date,

                COALESCE(
                    a.adjusted_start_time,
                    s.start_time
                ) AS start_time,

                COALESCE(
                    a.adjusted_end_time,
                    s.end_time
                ) AS end_time

            FROM appointments a

            INNER JOIN members m
                ON m.id = a.member_id

            INNER JOIN appointment_slots s
                ON s.id = a.slot_id

            WHERE a.id = ?

            LIMIT 1
        ");


        $q->execute([
            $appointmentId
        ]);


        $a =
            $q->fetch(PDO::FETCH_ASSOC);


        if (!$a) {

            email_log(
                'PASTOR NOTIFICATION ERROR: Appointment not found. ID=' .
                $appointmentId
            );

            return false;
        }


        email_log(
            'Appointment found: ' .
            $a['appointment_no'] .
            ' | Member=' .
            $a['member_name']
        );


        /*
         * Find active pastor accounts.
         */

        $q = $p->prepare("
            SELECT
                id,
                full_name,
                email

            FROM users

            WHERE role = 'pastor'

              AND status = 'active'

              AND email IS NOT NULL

              AND TRIM(email) <> ''

            ORDER BY id ASC
        ");


        $q->execute();


        $staff =
            $q->fetchAll(PDO::FETCH_ASSOC);


        /*
         * If pastor account is not found,
         * use the known church pastor email.
         */

        if (!$staff) {

            email_log(
                'No active pastor email found in users table. Using fallback email.'
            );


            $staff = [
                [
                    'id' => 1,
                    'full_name' =>
                        'Rev Benson Ririmpoi',
                    'email' =>
                        'fgckjoyland@gmail.com'
                ]
            ];
        }


        /*
         * Build email body.
         */

        $body = '

<p>Hello Pastor,</p>

<p>
A new appointment request has been submitted
through the
<b>FGCK Joyland Appointment System</b>.
</p>

<table style="
width:100%;
border-collapse:collapse;
">

<tr>
<td style="padding:8px 0">
<b>Member</b>
</td>

<td style="padding:8px 0">
' .
e((string)$a['member_name']) .
'
</td>
</tr>


<tr>
<td style="padding:8px 0">
<b>Phone</b>
</td>

<td style="padding:8px 0">
' .
e(
    (string)(
        $a['member_phone']
        ?? 'Not provided'
    )
) .
'
</td>
</tr>


<tr>
<td style="padding:8px 0">
<b>Email</b>
</td>

<td style="padding:8px 0">
' .
e(
    (string)(
        $a['member_email']
        ?? 'Not provided'
    )
) .
'
</td>
</tr>


<tr>
<td style="padding:8px 0">
<b>Date</b>
</td>

<td style="padding:8px 0">
' .
e(
    fmt_date(
        (string)$a['appointment_date']
    )
) .
'
</td>
</tr>


<tr>
<td style="padding:8px 0">
<b>Time</b>
</td>

<td style="padding:8px 0">
' .
e(
    fmt_time(
        (string)$a['start_time']
    )
) .
'
 –
' .
e(
    fmt_time(
        (string)$a['end_time']
    )
) .
'
</td>
</tr>


<tr>
<td style="padding:8px 0">
<b>Purpose</b>
</td>

<td style="padding:8px 0">
' .
e(
    (string)$a['purpose']
) .
'
</td>
</tr>


<tr>
<td style="padding:8px 0">
<b>Reference</b>
</td>

<td style="padding:8px 0">
<b>' .
e(
    (string)$a['appointment_no']
) .
'</b>
</td>
</tr>

</table>


<p style="margin-top:22px">

Please log in to the
<b><a href="https://pastorconnect.live" ">Pastor Portal</a></b> 
to review and manage this appointment.

</p>


';


        $sent = false;


        /*
         * Send to all active pastors.
         */

        foreach ($staff as $s) {

            $email =
                trim(
                    (string)$s['email']
                );

            $name =
                trim(
                    (string)$s['full_name']
                );


            if (!filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )) {

                email_log(
                    'INVALID PASTOR EMAIL: ' .
                    $email
                );

                continue;
            }


            email_log(
                'SENDING PASTOR EMAIL TO: ' .
                $email
            );


            $result =
                send_system_email(
                    $email,
                    $name,
                    'New Appointment Request – ' .
                    $a['appointment_no'],
                    $body
                );


            if ($result) {

                $sent = true;

                email_log(
                    'PASTOR EMAIL SUCCESS: ' .
                    $email
                );

            } else {

                email_log(
                    'PASTOR EMAIL FAILED: ' .
                    $email
                );
            }
        }


        return $sent;


    } catch (Throwable $e) {

        email_log(
            'PASTOR NOTIFICATION EXCEPTION: ' .
            $e->getMessage()
        );

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| MEMBER STATUS UPDATE
|--------------------------------------------------------------------------
*/

function email_member_status_update(
    PDO $p,
    int $appointmentId,
    string $status
): void {

    try {

        $q = $p->prepare("
            SELECT
                a.*,
                m.full_name,
                m.email,
                s.appointment_date,

                COALESCE(
                    a.adjusted_start_time,
                    s.start_time
                ) AS start_time,

                COALESCE(
                    a.adjusted_end_time,
                    s.end_time
                ) AS end_time

            FROM appointments a

            INNER JOIN members m
                ON m.id = a.member_id

            INNER JOIN appointment_slots s
                ON s.id = a.slot_id

            WHERE a.id = ?

            LIMIT 1
        ");


        $q->execute([
            $appointmentId
        ]);


        $a =
            $q->fetch(PDO::FETCH_ASSOC);


        if (
            !$a ||
            !filter_var(
                $a['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return;
        }


        $statusText = [

            'confirmed' => [
                'Appointment confirmed',
                'Your appointment has been confirmed by the pastor.'
            ],

            'declined' => [
                'Appointment declined',
                'The pastor was unable to confirm this appointment. Please log in to the portal to choose another available slot.'
            ],

            'cancelled' => [
                'Appointment cancelled',
                'This appointment has been cancelled. Please log in to the portal for more information.'
            ],

            'completed' => [
                'Appointment completed',
                'Your appointment has been marked as completed. Thank you for using the FGCK Joyland appointment service.'
            ],

            'no_show' => [
                'Appointment marked as no-show',
                'The system has recorded that the appointment was not attended. Please contact the church if you need assistance.'
            ]
        ];


        if (!isset(
            $statusText[$status]
        )) {
            return;
        }


        [
            $title,
            $intro
        ] =
            $statusText[$status];


        $body = '

<p>
Dear ' .
e((string)$a['full_name']) .
',
</p>

<p>' .
$intro .
'</p>


<table style="
width:100%;
border-collapse:collapse;
">

<tr>
<td style="padding:7px 0">
<b>Reference</b>
</td>

<td style="padding:7px 0">
' .
e((string)$a['appointment_no']) .
'
</td>
</tr>


<tr>
<td style="padding:7px 0">
<b>Date</b>
</td>

<td style="padding:7px 0">
' .
e(
    fmt_date(
        (string)$a['appointment_date']
    )
) .
'
</td>
</tr>


<tr>
<td style="padding:7px 0">
<b>Time</b>
</td>

<td style="padding:7px 0">
' .
e(
    fmt_time(
        (string)$a['start_time']
    )
) .
'
 –
' .
e(
    fmt_time(
        (string)$a['end_time']
    )
) .
'
</td>
</tr>


<tr>
<td style="padding:7px 0">
<b>Purpose</b>
</td>

<td style="padding:7px 0">
' .
e(
    (string)$a['purpose']
) .
'
</td>
</tr>

</table>


<p style="margin-top:20px">

Please sign in to the
<b>
<a href="https://pastorconnect.live">FGCK Joyland Member Portal</a>
</b>
to view your appointment.

</p> 

';



        send_system_email(
            (string)$a['email'],
            (string)$a['full_name'],
            $title .
            ' – ' .
            $a['appointment_no'],
            $body
        );


    } catch (Throwable $e) {

        email_log(
            'MEMBER STATUS EMAIL ERROR: ' .
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| MEMBER TIME CHANGE
|--------------------------------------------------------------------------
*/

function email_member_time_change(
    PDO $p,
    int $appointmentId,
    string $oldStart,
    string $oldEnd
): void {

    try {

        $q = $p->prepare("
            SELECT
                a.*,
                m.full_name,
                m.email,
                s.appointment_date,

                COALESCE(
                    a.adjusted_start_time,
                    s.start_time
                ) AS start_time,

                COALESCE(
                    a.adjusted_end_time,
                    s.end_time
                ) AS end_time

            FROM appointments a

            INNER JOIN members m
                ON m.id = a.member_id

            INNER JOIN appointment_slots s
                ON s.id = a.slot_id

            WHERE a.id = ?

            LIMIT 1
        ");


        $q->execute([
            $appointmentId
        ]);


        $a =
            $q->fetch(PDO::FETCH_ASSOC);


        if (
            !$a ||
            !filter_var(
                $a['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return;
        }


        $body = '

<p>
Dear ' .
e((string)$a['full_name']) .
',
</p>


<p>
The pastor has adjusted your appointment time.
</p>


<p>

<b>Previous:</b>
' .
e(
    fmt_time($oldStart)
) .
'
 –
' .
e(
    fmt_time($oldEnd)
) .
'


<br>


<b>New:</b>
' .
e(
    fmt_time(
        (string)$a['start_time']
    )
) .
'
 –
' .
e(
    fmt_time(
        (string)$a['end_time']
    )
) .
'


<br>


<b>Date:</b>
' .
e(
    fmt_date(
        (string)$a['appointment_date']
    )
) .
'


<br>


<b>Reference:</b>
' .
e(
    (string)$a['appointment_no']
) .
'

</p>


<p>

Please check your
<b><a href="https://pastorconnect.live">FGCK Joyland Member Portal</a></b>
for the latest schedule.

</p>

';


        send_system_email(
            (string)$a['email'],
            (string)$a['full_name'],
            'Appointment Time Changed – ' .
            $a['appointment_no'],
            $body
        );


    } catch (Throwable $e) {

        email_log(
            'TIME CHANGE EMAIL ERROR: ' .
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| ANNOUNCEMENT EMAIL
|--------------------------------------------------------------------------
*/

function email_members_about_announcement(
    PDO $p,
    int $announcementId
): int {

    try {

        $q = $p->prepare("
            SELECT
                title,
                message,
                type,
                priority,
                audience

            FROM announcements

            WHERE id = ?
              AND status = 'published'

            LIMIT 1
        ");


        $q->execute([
            $announcementId
        ]);


        $a =
            $q->fetch(PDO::FETCH_ASSOC);


        if (!$a) {
            return 0;
        }


        $sql = "
            SELECT
                full_name,
                email

            FROM members

            WHERE status = 'active'
              AND email IS NOT NULL
              AND TRIM(email) <> ''
        ";


        if (
            $a['audience'] ===
            'appointment_members'
        ) {

            $sql .= "

                AND EXISTS (

                    SELECT 1

                    FROM appointments ap

                    INNER JOIN appointment_slots aps
                        ON aps.id = ap.slot_id

                    WHERE ap.member_id = members.id

                      AND ap.status IN
                        ('pending','confirmed')

                      AND aps.appointment_date
                        >= CURDATE()
                )
            ";
        }


        $members =
            $p->query($sql)
             ->fetchAll(PDO::FETCH_ASSOC);


        $sent = 0;


        $body = '

<p>
Dear member,
</p>

<p>
' .
nl2br(
    e(
        (string)$a['message']
    )
) .
'
</p>


<p>

Please sign in to the
<b><a href="https://pastorconnect.live">FGCK Joyland Member Portal</b>
to read the full message and stay up to date with church information.

</p>
';

        foreach ($members as $m) {

            if (
                send_system_email(
                    (string)$m['email'],
                    (string)$m['full_name'],
                    'FGCK Joyland: ' .
                    $a['title'],
                    $body
                )
            ) {

                $sent++;
            }
        }


        return $sent;


    } catch (Throwable $e) {

        email_log(
            'ANNOUNCEMENT EMAIL ERROR: ' .
            $e->getMessage()
        );

        return 0;
    }
}


/*
|--------------------------------------------------------------------------
| CHURCH EVENT EMAIL
|--------------------------------------------------------------------------
*/

function email_members_about_event(
    PDO $p,
    int $eventId,
    string $action = 'published'
): int {

    try {

        $q = $p->prepare("
            SELECT *
            FROM church_events
            WHERE id = ?
            LIMIT 1
        ");


        $q->execute([
            $eventId
        ]);


        $ev =
            $q->fetch(PDO::FETCH_ASSOC);


        if (!$ev) {
            return 0;
        }


        $members =
            $p->query("
                SELECT
                    full_name,
                    email

                FROM members

                WHERE status = 'active'
                  AND email IS NOT NULL
                  AND TRIM(email) <> ''
            ")
            ->fetchAll(PDO::FETCH_ASSOC);


        $sent = 0;


        $title =
            (string)$ev['title'];


        if (
            $action ===
            'cancelled'
        ) {

            $subject =
                'Church Event Cancelled – ' .
                $title;


            $body = '

<p>
Dear member,
</p>

<p>
The following church event has been cancelled:
</p>

<p>

<b>' .
e($title) .
'</b>

<br>

Date:
' .
e(
    fmt_date(
        (string)$ev['event_date']
    )
) .

'</p>


<p>
Please log in to the <a href="https://pastorconnect.live">Member Portal</a> for the latest church updates.
</p>
';
        } else {

            $subject =
                'New Church Event – ' .
                $title;


            $time = '';


            if (
                !empty(
                    $ev['start_time']
                )
            ) {

                $time =
                    fmt_time(
                        (string)$ev['start_time']
                    );


                if (
                    !empty(
                        $ev['end_time']
                    )
                ) {

                    $time .=
                        ' – ' .
                        fmt_time(
                            (string)$ev['end_time']
                        );
                }
            }


            $body = '

<p>
Dear member,
</p>

<p>
A new church event has been published:
</p>


<table style="
width:100%;
border-collapse:collapse;
">


<tr>

<td style="padding:6px 0">
<b>Event</b>
</td>

<td style="padding:6px 0">
' .
e($title) .
'
</td>

</tr>


<tr>

<td style="padding:6px 0">
<b>Date</b>
</td>

<td style="padding:6px 0">
' .
e(
    fmt_date(
        (string)$ev['event_date']
    )
) .
'
</td>

</tr>


<tr>

<td style="padding:6px 0">
<b>Time</b>
</td>

<td style="padding:6px 0">
' .
e(
    $time ?: 'As announced'
) .
'
</td>

</tr>


<tr>

<td style="padding:6px 0">
<b>Venue</b>
</td>

<td style="padding:6px 0">
' .
e(
    (string)(
        $ev['venue']
        ?: 'To be announced'
    )
) .
'
</td>

</tr>


</table>


<p>

' .
nl2br(
    e(
        (string)(
            $ev['description']
            ?? ''
        )
    )
) .
'

</p>


<p>

Please log in to the
<b><a href="https://pastorconnect.live">Member Portal</a></b>
to view the event and RSVP if required.

</p>
';
        }


        foreach ($members as $m) {

            if (
                send_system_email(
                    (string)$m['email'],
                    (string)$m['full_name'],
                    $subject,
                    $body
                )
            ) {

                $sent++;
            }
        }


        return $sent;


    } catch (Throwable $e) {

        email_log(
            'EVENT EMAIL ERROR: ' .
            $e->getMessage()
        );

        return 0;
    }
}
// -----------------------------------------------------------------------------
// Church Leader workflow email helpers
// -----------------------------------------------------------------------------
function email_pastors_about_leader_booking(PDO $p, int $appointmentId): int
{
    try {
        $q=$p->prepare("SELECT la.*,u.full_name AS leader_name,u.email AS leader_email,u.phone AS leader_phone,s.appointment_date,COALESCE(la.adjusted_start_time,s.start_time) start_time,COALESCE(la.adjusted_end_time,s.end_time) end_time FROM leader_appointments la JOIN users u ON u.id=la.user_id JOIN appointment_slots s ON s.id=la.slot_id WHERE la.id=? LIMIT 1");
        $q->execute([$appointmentId]);$a=$q->fetch(PDO::FETCH_ASSOC);if(!$a)return 0;
        $pastors=$p->query("SELECT full_name,email FROM users WHERE role='pastor' AND status='active' AND email<>''")->fetchAll(PDO::FETCH_ASSOC);$sent=0;
        $body='<p>Hello Pastor,</p><p>A Church Leader has requested a pastoral appointment.</p><table style="width:100%;border-collapse:collapse"><tr><td><b>Leader</b></td><td>'.e($a['leader_name']).'</td></tr><tr><td><b>Email</b></td><td>'.e($a['leader_email']).'</td></tr><tr><td><b>Phone</b></td><td>'.e($a['leader_phone']?:'Not provided').'</td></tr><tr><td><b>Date</b></td><td>'.e(fmt_date($a['appointment_date'])).'</td></tr><tr><td><b>Time</b></td><td>'.e(fmt_time($a['start_time'])).' – '.e(fmt_time($a['end_time'])).'</td></tr><tr><td><b>Purpose</b></td><td>'.e($a['purpose']).'</td></tr><tr><td><b>Reference</b></td><td><b>'.e($a['appointment_no']).'</b></td></tr></table><p>Please open the Pastor Portal to review and respond.</p>';
        foreach($pastors as $pastor){if(filter_var($pastor['email'],FILTER_VALIDATE_EMAIL)&&send_system_email($pastor['email'],$pastor['full_name'],'New Church Leader Appointment – '.$a['appointment_no'],$body))$sent++;}
        email_log('LEADER APPOINTMENT PASTOR EMAILS SENT: '.$sent.' for '.$a['appointment_no']);return $sent;
    }catch(Throwable $e){email_log('LEADER APPOINTMENT PASTOR EMAIL ERROR: '.$e->getMessage());return 0;}
}

function email_leader_appointment_status(PDO $p, int $appointmentId, string $status): void
{
    try {
        $q=$p->prepare("SELECT la.*,u.full_name,u.email,s.appointment_date,COALESCE(la.adjusted_start_time,s.start_time) start_time,COALESCE(la.adjusted_end_time,s.end_time) end_time FROM leader_appointments la JOIN users u ON u.id=la.user_id JOIN appointment_slots s ON s.id=la.slot_id WHERE la.id=? LIMIT 1");$q->execute([$appointmentId]);$a=$q->fetch(PDO::FETCH_ASSOC);if(!$a||!filter_var($a['email'],FILTER_VALIDATE_EMAIL))return;
        $label=ucwords(str_replace('_',' ',$status));$body='<p>Hello '.e($a['full_name']).',</p><p>Your appointment request with the Pastor has been <b>'.e($label).'</b>.</p><p><b>Date:</b> '.e(fmt_date($a['appointment_date'])).'<br><b>Time:</b> '.e(fmt_time($a['start_time'])).' – '.e(fmt_time($a['end_time'])).'<br><b>Purpose:</b> '.e($a['purpose']).'<br><b>Reference:</b> '.e($a['appointment_no']).'</p><p>Please sign in to the Church Leader Portal for the latest details.</p>';
        send_system_email($a['email'],$a['full_name'],'Pastor Appointment '.$label.' – '.$a['appointment_no'],$body);
    }catch(Throwable $e){email_log('LEADER APPOINTMENT STATUS EMAIL ERROR: '.$e->getMessage());}
}

function email_pastors_about_event(PDO $p, int $eventId, string $status='published'): int
{
    try {
        $q=$p->prepare("SELECT e.*,u.full_name AS author FROM church_events e LEFT JOIN users u ON u.id=e.user_id WHERE e.id=? LIMIT 1");$q->execute([$eventId]);$e=$q->fetch(PDO::FETCH_ASSOC);if(!$e)return 0;
        $pastors=$p->query("SELECT full_name,email FROM users WHERE role='pastor' AND status='active' AND email<>''")->fetchAll(PDO::FETCH_ASSOC);$sent=0;$label=ucfirst($status);
        $body='<p>Hello Pastor,</p><p>'.e($e['author']?:'A Church Leader').' has '.e(strtolower($status)).' the church event <b>'.e($e['title']).'</b>.</p><p><b>Date:</b> '.e(fmt_date($e['event_date'])).'<br><b>Time:</b> '.e($e['start_time']?fmt_time($e['start_time']):'Not specified').' – '.e($e['end_time']?fmt_time($e['end_time']):'Not specified').'<br><b>Venue:</b> '.e($e['venue']?:'To be announced').'</p><p>Please open the Pastor Portal for the full event record.</p>';
        foreach($pastors as $pastor){if(filter_var($pastor['email'],FILTER_VALIDATE_EMAIL)&&send_system_email($pastor['email'],$pastor['full_name'],'Church Event '.$label.' – '.$e['title'],$body))$sent++;}
        return $sent;
    }catch(Throwable $ex){email_log('PASTOR EVENT EMAIL ERROR: '.$ex->getMessage());return 0;}
}

function email_leaders_about_announcement(PDO $p, int $announcementId): int
{
    try {
        $q=$p->prepare("SELECT a.*,u.full_name AS pastor_name FROM announcements a JOIN users u ON u.id=a.user_id WHERE a.id=? LIMIT 1");$q->execute([$announcementId]);$a=$q->fetch(PDO::FETCH_ASSOC);if(!$a)return 0;
        $leaders=$p->query("SELECT full_name,email FROM users WHERE role='church_leader' AND status='active' AND email<>''")->fetchAll(PDO::FETCH_ASSOC);$sent=0;
        $body='<p>Hello Church Leader,</p><p>'.e($a['pastor_name']).' has published a pastoral communication.</p><h3>'.e($a['title']).'</h3><p style="white-space:pre-wrap">'.e($a['message']).'</p><p>Please sign in to the Church Leader Portal to read and act on church communication.</p>';
        foreach($leaders as $leader){if(filter_var($leader['email'],FILTER_VALIDATE_EMAIL)&&send_system_email($leader['email'],$leader['full_name'],'Pastor Message – '.$a['title'],$body))$sent++;}
        email_log('PASTOR MESSAGE LEADER EMAILS SENT: '.$sent.' for announcement '.$announcementId);return $sent;
    }catch(Throwable $e){email_log('PASTOR MESSAGE LEADER EMAIL ERROR: '.$e->getMessage());return 0;}
}
