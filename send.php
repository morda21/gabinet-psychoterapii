<?php
/**
 * Skrypt wysyłający e-mail z formularza kontaktowego
 * Konfiguracja SMTP dla home.pl
 */

// Załaduj konfigurację (hasło SMTP)
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd konfiguracji serwera.']);
    exit;
}
require_once $configFile;

// Konfiguracja
$smtpHost = 'serwer2663362.home.pl';
$smtpPort = 587;
$smtpUser = 'kontakt@annamordawska.pl';
$smtpPass = SMTP_PASSWORD; // Z pliku config.php
$toEmail = 'anmordawska@gmail.com';
$fromEmail = 'kontakt@annamordawska.pl';
$fromName = 'Gabinet Moment - Formularz';

// Nagłówki CORS i JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Tylko metoda POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Dozwolona tylko metoda POST.']);
    exit;
}

// Pobierz dane z formularza
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$honeypot = isset($_POST['website']) ? trim($_POST['website']) : '';

// Sprawdź honeypot (pole antyspamowe - powinno być puste)
if (!empty($honeypot)) {
    // Bot wykryty - udajemy sukces, ale nic nie wysyłamy
    echo json_encode(['success' => true, 'message' => 'Wiadomość została wysłana.']);
    exit;
}

// Walidacja
if (empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proszę wypełnić wszystkie pola.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy adres e-mail.']);
    exit;
}

// Sanityzacja
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Przygotuj treść e-maila
$subject = 'Nowa wiadomość z formularza - Gabinet Moment';
$body = "Nowa wiadomość z formularza kontaktowego:\n\n";
$body .= "Od: " . $email . "\n\n";
$body .= "Wiadomość:\n" . strip_tags(html_entity_decode($message, ENT_QUOTES, 'UTF-8')) . "\n";
$body .= "\n---\nWiadomość wysłana z formularza na stronie annamordawska.pl";

// Nagłówki e-maila
$headers = array(
    'From: ' . $fromName . ' <' . $fromEmail . '>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8'
);

// Wyślij e-mail
// Dla home.pl najlepiej użyć funkcji mail() z parametrem -f
$additionalParams = '-f' . $fromEmail;

$sent = @mail($toEmail, $subject, $body, implode("\r\n", $headers), $additionalParams);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Dziękuję! Wiadomość została wysłana. Odpowiem najszybciej jak to możliwe.']);
} else {
    // Loguj błąd (opcjonalnie)
    error_log('Błąd wysyłania e-maila z formularza kontaktowego');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Przepraszam, wystąpił błąd podczas wysyłania wiadomości. Proszę spróbować ponownie lub skontaktować się telefonicznie.']);
}
?>
