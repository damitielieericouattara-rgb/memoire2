<?php
// Fichier: /backend/services/EmailService.php
// Version simplifiée - log uniquement en développement

class EmailService {
    public function send($to, $subject, $message) {
        // En développement : juste logguer
        if (AppConfig::$appEnv === 'development') {
            $log = "[EMAIL] To: $to | Subject: $subject\n";
            error_log($log);
            return true;
        }
        // En production : utiliser mail() ou SMTP
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: " . AppConfig::$mailFrom . "\r\n";
        return @mail($to, $subject, $message, $headers);
    }
}
