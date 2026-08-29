<?php
declare(strict_types=1);

$domainRoot = dirname(__DIR__, 2);

require $domainRoot . '/vendor/autoload.php';
require $domainRoot . '/private/src/Common.php';
require $domainRoot . '/private/src/RateLimiter.php';
require $domainRoot . '/private/src/MailerService.php';

$config = require $domainRoot . '/private/form_config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(405, [
        'ok' => false,
        'message' => 'Niedozwolona metoda.',
    ]);
}

$security = (array) ($config['security'] ?? []);

if (!checkRequestOrigin(
    (array) ($security['allowed_hosts'] ?? []),
    (bool) ($security['check_origin'] ?? true)
)) {
    jsonResponse(403, [
        'ok' => false,
        'message' => 'Żądanie zostało odrzucone.',
    ]);
}

startFormSession();

/* Honeypot: normalny użytkownik nigdy nie widzi ani nie wypełnia tego pola. */
if (trim((string) ($_POST['website_url'] ?? '')) !== '') {
    // Nie zdradzamy botowi, że został wykryty.
    jsonResponse(200, [
        'ok' => true,
        'message' => 'Dziękujemy. Wiadomość została wysłana.',
    ]);
}

$submittedToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

if (
    $submittedToken === '' ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    jsonResponse(403, [
        'ok' => false,
        'message' => 'Sesja formularza wygasła. Odśwież stronę i spróbuj ponownie.',
    ]);
}

$startedAt = (int) ($_SESSION['form_started_at'] ?? 0);
$elapsed = time() - $startedAt;

$minSeconds = (int) ($security['min_form_seconds'] ?? 2);
$maxSeconds = (int) ($security['max_form_seconds'] ?? 7200);

if ($startedAt <= 0 || $elapsed < $minSeconds || $elapsed > $maxSeconds) {
    jsonResponse(403, [
        'ok' => false,
        'message' => 'Formularz wygasł lub został wysłany zbyt szybko. Odśwież stronę i spróbuj ponownie.',
    ]);
}

$data = validateFormData($_POST);
$attachment = validateAttachment($_FILES, $security);

$limiter = new RateLimiter(
    $domainRoot . '/private/rate_limit',
    (array) $config['rate_limit']
);

$ip = getClientIp();

try {
    $reservation = $limiter->reserve($ip);
} catch (RateLimitExceededException $e) {
    header('Retry-After: ' . $e->retryAfter);

    jsonResponse(429, [
        'ok' => false,
        'message' => 'Przekroczono limit wysyłki formularza z tego adresu IP. Spróbuj ponownie za ' .
            max(1, (int) ceil($e->retryAfter / 60)) . ' min.',
        'retry_after' => $e->retryAfter,
    ]);
} catch (Throwable $e) {
    error_log('[FORM RATE LIMIT] ' . $e->getMessage());

    jsonResponse(500, [
        'ok' => false,
        'message' => 'Wystąpił błąd zabezpieczenia formularza. Spróbuj ponownie później.',
    ]);
}

try {
    /* Najpierw wysyłamy właściwe zapytanie do biura. */
    sendInvestmentInquiry($config, $data, $attachment);

    /*
     * Następnie wysyłamy klientowi automatyczne potwierdzenie.
     * Jeśli sama zwrotka się nie powiedzie, zapytanie do biura nadal jest ważne
     * i nie pokazujemy klientowi fałszywego komunikatu, że formularz nie dotarł.
     */
    $confirmationSent = true;

    try {
        sendInquiryConfirmation($config, $data, $attachment);
    } catch (Throwable $confirmationError) {
        $confirmationSent = false;
        error_log('[FORM CONFIRMATION] ' . $confirmationError->getMessage());
    }

    /*
     * Unieważniamy token po poprawnej wysyłce zapytania do biura.
     * JS pobierze nowy token, jeśli użytkownik chce wysłać kolejne zapytanie.
     */
    unset($_SESSION['csrf_token'], $_SESSION['form_started_at']);

    jsonResponse(200, [
        'ok' => true,
        'message' => $confirmationSent
            ? 'Dziękujemy. Zapytanie zostało wysłane. Potwierdzenie wysłaliśmy na podany adres e-mail.'
            : 'Dziękujemy. Zapytanie zostało wysłane, ale nie udało się dostarczyć automatycznego potwierdzenia e-mail.',
        'confirmation_sent' => $confirmationSent,
    ]);
} catch (Throwable $e) {
    // Błąd SMTP nie powinien zużywać limitu użytkownikowi.
    $limiter->release($reservation);

    error_log('[FORM SMTP] ' . $e->getMessage());

    jsonResponse(500, [
        'ok' => false,
        'message' => 'Nie udało się wysłać wiadomości. Spróbuj ponownie później lub skontaktuj się z nami telefonicznie.',
    ]);
}
