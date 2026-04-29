<?phpheader('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/mail-config.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!empty($_POST['website'])){
    echo json_encode(['status' => 'success']);
    exit;
}

$name    = trim(htmlspecialchars($_POST['visitor_name']    ?? '', ENT_QUOTES, 'UTF-8'));
$email   = trim(filter_var($_POST['visitor_email']         ?? '', FILTER_SANITIZE_EMAIL));
$message = trim(htmlspecialchars($_POST['visitor_message'] ?? '', ENT_QUOTES, 'UTF-8'));

$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
} elseif (strlen($name) > 100) {
    $errors[] = 'Name must be under 100 characters.';
}

if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email address is not valid.';
} elseif (strlen($email) > 254) {
    $errors[] = 'Email address is too long.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Message must be under 5000 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;

    // Recipients
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_TO);
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Website Enquiry from ' . $name;

    $mail->Body = "
        <div style='font-family: Georgia, serif; color: #2c3642; max-width: 600px; padding: 24px;'>
            <h2 style='border-bottom: 2px solid #3fb9eb; padding-bottom: 10px; margin-bottom: 20px;'>
                New Website Enquiry — Lagatoi Cyberspace Consultancy
            </h2>
            <p style='margin-bottom: 8px;'><strong>Name:</strong> {$name}</p>
            <p style='margin-bottom: 8px;'><strong>Email:</strong> {$email}</p>
            <p style='margin-bottom: 12px;'><strong>Message:</strong></p>
            <div style='background: #e6e7e8; padding: 16px; border-left: 4px solid #3fb9eb;'>
                " . nl2br($message) . "
            </div>
            <p style='font-size: 0.82em; color: #888; margin-top: 24px;'>
                Submitted via the Lagatoi Cyberspace Consultancy website contact form.
            </p>
        </div>
    ";

    $mail->AltBody = "New enquiry from: {$name}\nReply to: {$email}\n\nMessage:\n{$message}";

    $mail->send();

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    error_log('PHPMailer error — ' . date('Y-m-d H:i:s') . ' — ' . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Your message could not be sent at this time. Please try again or contact us directly by email.'
    ]);
}