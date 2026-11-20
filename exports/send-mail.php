<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reports.php';

function mail_json_response(int $status, bool $success, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mail_json_response(405, false, 'Use the send-mail form to submit a report.');
}
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    mail_json_response(401, false, 'Your session has expired. Sign in again before sending a report.');
}
$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || !hash_equals(csrf_token(), $submittedToken)) {
    mail_json_response(400, false, 'The request could not be verified. Reload the page and try again.');
}

$module = input_string($_POST, 'module');
if (!in_array($module, ['students', 'courses', 'instructors', 'enrollments', 'payments'], true)) {
    mail_json_response(400, false, 'Choose a valid report.');
}
$definition = module_definition($module);

$recipient = trim(input_string($_POST, 'to'));
$cc = trim(input_string($_POST, 'cc'));
$subject = trim(input_string($_POST, 'subject'));
$message = trim(input_string($_POST, 'message'));
if (strlen($recipient) > 254 || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    mail_json_response(422, false, 'Enter a valid recipient email address.');
}
if ($cc !== '' && (strlen($cc) > 254 || !filter_var($cc, FILTER_VALIDATE_EMAIL))) {
    mail_json_response(422, false, 'Enter a valid CC email address or leave the field blank.');
}
if ($subject === '' || strlen($subject) > 200 || preg_match('/[\r\n]/', $subject)) {
    mail_json_response(422, false, 'Enter a subject of up to 200 characters.');
}
if ($message === '' || strlen($message) > 10000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $message)) {
    mail_json_response(422, false, 'Enter a message of up to 10,000 characters.');
}

$reportInput = [];
$query = trim(input_string($_POST, 'q'));
if (strlen($query) > 200) {
    mail_json_response(422, false, 'The report search text is too long.');
}
if ($query !== '') {
    $reportInput['q'] = $query;
}

$sortKeys = [];
foreach ($definition['sort'] as $name => $sqlColumn) {
    $sortKeys[] = is_int($name) ? $sqlColumn : $name;
}
$sort = input_string($_POST, 'sort');
if ($sort !== '') {
    if (!in_array($sort, $sortKeys, true)) {
        mail_json_response(422, false, 'The selected report sort is invalid.');
    }
    $reportInput['sort'] = $sort;
}
$direction = strtolower(input_string($_POST, 'dir', 'asc'));
if (!in_array($direction, ['asc', 'desc'], true)) {
    mail_json_response(422, false, 'The selected report sort direction is invalid.');
}
$reportInput['dir'] = $direction;

foreach ($definition['filters'] ?? [] as $field => $filter) {
    $value = trim(input_string($_POST, $field));
    if ($value === '') {
        continue;
    }
    if ($filter['type'] === 'select' && !in_array($value, $filter['options'], true)) {
        mail_json_response(422, false, 'The selected ' . strtolower($filter['label']) . ' filter is invalid.');
    }
    if ($filter['type'] === 'date' && !valid_date($value)) {
        mail_json_response(422, false, 'Enter a valid date for the ' . strtolower($filter['label']) . ' filter.');
    }
    $reportInput[$field] = $value;
}

require_once __DIR__ . '/../config/mail.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    mail_json_response(503, false, 'Email delivery and PDF generation require Composer dependencies. Run composer install.');
}
require_once $autoload;
if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    mail_json_response(503, false, 'The email library is unavailable. Run composer install to install project dependencies.');
}

$mailStage = 'smtp_configuration';
$mailer = null;
try {
    $host = trim((string) getenv('MAIL_HOST'));
    $username = trim((string) getenv('MAIL_USERNAME'));
    $password = (string) getenv('MAIL_PASSWORD');
    if (strtolower($host) === 'smtp.gmail.com') {
        $normalizedPassword = preg_replace('/\s+/', '', $password);
        if ($normalizedPassword === null) {
            mail_json_response(503, false, 'Email authentication is unavailable due to invalid server configuration.');
        }
        $password = $normalizedPassword;
    }
    $fromAddress = trim((string) getenv('MAIL_FROM_ADDRESS'));
    $fromName = trim((string) (getenv('MAIL_FROM_NAME') ?: APP_NAME));
    $portValue = getenv('MAIL_PORT');
    $port = $portValue === false || $portValue === '' ? 587 : filter_var($portValue, FILTER_VALIDATE_INT);
    $encryption = strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls')));

    if ($host === '' || $fromAddress === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
        mail_json_response(503, false, 'Email delivery is not configured. Set MAIL_HOST and a valid MAIL_FROM_ADDRESS on the server.');
    }
    if ($port === false || $port < 1 || $port > 65535) {
        mail_json_response(503, false, 'Email delivery has an invalid MAIL_PORT setting.');
    }
    if (($username === '') !== ($password === '')) {
        mail_json_response(503, false, 'Email authentication is incomplete. Configure both MAIL_USERNAME and MAIL_PASSWORD on the server.');
    }
    if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
        mail_json_response(503, false, 'Email delivery has an invalid MAIL_ENCRYPTION setting.');
    }

    $mailStage = 'report_generation';
    $report = module_report($module, $reportInput);
    $mailStage = 'pdf_generation';
    $pdf = report_pdf_bytes($report['html']);
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->SMTPDebug = 2;
    $mailer->Debugoutput = static function (string $message, int $level): void {
        if (preg_match('/SERVER -> CLIENT:\s*(\d{3})/', $message, $matches) && (int) $matches[1] >= 400) {
            error_log('PHPMailer SMTP server response code=' . $matches[1]);
        }
    };
    $mailer->Host = $host;
    $mailer->Port = $port;
    $mailer->SMTPAuth = $username !== '';
    $mailer->SMTPAutoTLS = $encryption === 'tls';
    if ($mailer->SMTPAuth) {
        $mailer->Username = $username;
        $mailer->Password = $password;
    }
    $mailer->Timeout = 15;
    $mailer->SMTPSecure = match ($encryption) {
        'tls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'ssl' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
        default => '',
    };
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($fromAddress, $fromName);
    $mailer->addAddress($recipient);
    if ($cc !== '') {
        $mailer->addCC($cc);
    }
    $mailer->Subject = $subject;
    $mailer->Body = $message;
    $mailStage = 'pdf_attachment';
    $mailer->addStringAttachment($pdf, $report['filename'] . '.pdf', 'base64', 'application/pdf');
    $mailStage = 'smtp_delivery';
    $mailer->send();
} catch (Throwable $exception) {
    $errorDetails = $mailer instanceof \PHPMailer\PHPMailer\PHPMailer && $mailer->ErrorInfo !== ''
        ? $mailer->ErrorInfo
        : $exception->getMessage();
    foreach (['MAIL_USERNAME', 'MAIL_PASSWORD'] as $credentialName) {
        $credential = (string) getenv($credentialName);
        $variants = [$credential, trim($credential), preg_replace('/\s+/', '', $credential)];
        foreach (array_unique($variants) as $variant) {
            if ($variant !== '') {
                $errorDetails = str_replace($variant, '[redacted]', $errorDetails);
                $errorDetails = str_replace(base64_encode($variant), '[redacted]', $errorDetails);
            }
        }
    }
    error_log(sprintf(
        'Report email delivery failed (module=%s, stage=%s, exception=%s): %s',
        $module,
        $mailStage,
        get_class($exception),
        $errorDetails
    ));
    mail_json_response(502, false, 'The report email could not be sent. Verify the recipient and server email configuration, then try again.');
}

mail_json_response(200, true, 'The report email was sent successfully.');
