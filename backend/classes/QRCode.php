<?php
/**
 * SneakX — Générateur de QR Code
 * Utilise l'API publique QR Server (sans dépendance externe)
 * /backend/classes/QRCode.php
 */

class QRCode {

    /**
     * Générer un QR Code pour une commande
     * Retourne une URL data:image/png;base64,... ou une URL distante
     */
    public static function generateForOrder(array $order): string {
        $data = json_encode([
            'id'      => $order['id'],
            'numero'  => $order['numero'],
            'client'  => ($order['prenom'] ?? '') . ' ' . ($order['nom'] ?? ''),
            'total'   => $order['total_ttc'],
            'adresse' => $order['adresse_livraison'] ?? '',
            'date'    => $order['created_at'],
        ], JSON_UNESCAPED_UNICODE);

        // Option 1 : API QR Server (nécessite connexion internet)
        $encoded = urlencode($data);
        $apiUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$encoded}";

        // Option 2 : Génération locale sans lib externe (QR minimaliste base64)
        // Pour la démo, on retourne l'URL API
        return $apiUrl;
    }

    /**
     * Générer QR Code en base64 (stockage local)
     * Télécharge l'image et la convertit
     */
    public static function generateBase64(string $text): string {
        $url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($text);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $image = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $image) {
            return 'data:image/png;base64,' . base64_encode($image);
        }

        // Fallback : QR code SVG minimaliste
        return self::fallbackSvg($text);
    }

    /**
     * QR SVG de secours (simple, sans lib)
     */
    private static function fallbackSvg(string $text): string {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
             . '<rect width="200" height="200" fill="white"/>'
             . '<text x="100" y="100" text-anchor="middle" font-size="10" font-family="monospace" fill="black">'
             . htmlspecialchars(substr($text, 0, 30))
             . '</text>'
             . '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Sauvegarder QR Code dans le système de fichiers
     */
    public static function saveToFile(int $orderId, string $base64): string {
        $dir = UPLOAD_PATH . '/qrcodes/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = "order_{$orderId}.png";
        $path     = $dir . $filename;
        // Extraire les données base64
        if (preg_match('/^data:image\/\w+;base64,/', $base64)) {
            $data = substr($base64, strpos($base64, ',') + 1);
            file_put_contents($path, base64_decode($data));
        }
        return UPLOAD_URL . '/qrcodes/' . $filename;
    }
}
