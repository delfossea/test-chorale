<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php'; require_once __DIR__ . '/db.php';

function send_mail(string $to, string $subject, string $body, string $type): bool {
    $ok = false; $error = null;
    try {
        $socket = @stream_socket_client('tcp://' . env('SMTP_HOST', SMTP_HOST) . ':' . env('SMTP_PORT', (string) SMTP_PORT), $errno, $errstr, 3);
        if (!$socket) throw new RuntimeException($errstr ?: 'Connexion SMTP impossible');
        $read = static fn() => fgets($socket, 512);
        $read(); fwrite($socket, "HELO localhost\r\n"); $read(); fwrite($socket, "MAIL FROM:<noreply@chorale.test>\r\n"); $read(); fwrite($socket, "RCPT TO:<$to>\r\n"); $read(); fwrite($socket, "DATA\r\n"); $read();
        $message = "From: Covoiturage Chorale <noreply@chorale.test>\r\nTo: <$to>\r\nSubject: $subject\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . str_replace("\n.", "\n..", $body) . "\r\n.\r\n";
        fwrite($socket, $message); $response = $read(); fwrite($socket, "QUIT\r\n"); fclose($socket);
        if (!str_starts_with((string)$response, '250')) throw new RuntimeException('SMTP a refusé le message');
        $ok = true;
    } catch (Throwable $e) { $error = $e->getMessage(); }
    db()->prepare('INSERT INTO notification_log(recipient,type,subject,status,error) VALUES(?,?,?,?,?)')->execute([$to,$type,$subject,$ok ? 'sent' : 'failed',$error]);
    return $ok;
}
