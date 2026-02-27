<?php
// Fichier: /backend/services/MultiDeviseService.php
// ─── SERVICE MULTI-DEVISE XOF/EUR/USD ────────────────────────

class MultiDeviseService {
    // Taux de change (à mettre à jour via API en prod: open.er-api.com)
    private static array $rates = [
        'XOF' => 1,
        'EUR' => 0.00152,   // 1 XOF = 0.00152 EUR
        'USD' => 0.00164,   // 1 XOF = 0.00164 USD
        'GBP' => 0.00131,
        'GHS' => 0.02,
        'NGN' => 1.31,
    ];

    private static array $symbols = [
        'XOF' => 'FCFA',
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'GHS' => '₵',
        'NGN' => '₦',
    ];

    private static array $locales = [
        'XOF' => 'fr-CI',
        'EUR' => 'fr-FR',
        'USD' => 'en-US',
    ];

    /**
     * Convertit un montant de XOF vers une devise cible
     */
    public static function convert($amountXOF, $toCurrency = 'EUR') {
        $toCurrency = strtoupper($toCurrency);
        if (!isset(self::$rates[$toCurrency])) {
            throw new InvalidArgumentException("Devise non supportée: $toCurrency");
        }
        return round($amountXOF * self::$rates[$toCurrency], 2);
    }

    /**
     * Convertit vers XOF depuis une devise étrangère
     */
    public static function toXOF($amount, $fromCurrency = 'EUR') {
        $fromCurrency = strtoupper($fromCurrency);
        if (!isset(self::$rates[$fromCurrency]) || self::$rates[$fromCurrency] == 0) {
            throw new InvalidArgumentException("Devise non supportée: $fromCurrency");
        }
        return round($amount / self::$rates[$fromCurrency], 2);
    }

    /**
     * Formate un montant avec symbole devise
     */
    public static function format($amount, $currency = 'XOF') {
        $currency = strtoupper($currency);
        $symbol   = self::$symbols[$currency] ?? $currency;

        switch ($currency) {
            case 'XOF':
                return number_format($amount, 0, ',', ' ') . ' ' . $symbol;
            case 'EUR':
                return $symbol . ' ' . number_format($amount, 2, ',', ' ');
            case 'USD':
                return $symbol . number_format($amount, 2, '.', ',');
            default:
                return number_format($amount, 2) . ' ' . $symbol;
        }
    }

    /**
     * Retourne tous les montants dans toutes les devises
     */
    public static function allCurrencies($amountXOF) {
        $result = [];
        foreach (self::$rates as $currency => $rate) {
            $converted = round($amountXOF * $rate, 2);
            $result[$currency] = [
                'amount'    => $converted,
                'formatted' => self::format($converted, $currency),
                'symbol'    => self::$symbols[$currency] ?? $currency,
                'rate'      => $rate,
            ];
        }
        return $result;
    }

    /**
     * Retourne les taux courants (utile pour le frontend)
     */
    public static function getRates() {
        return array_map(fn($rate) => [
            'rate'   => $rate,
            'symbol' => self::$symbols[array_search($rate, self::$rates)] ?? '',
        ], self::$rates);
    }

    /**
     * Retourne les devises supportées
     */
    public static function getSupportedCurrencies() {
        return array_keys(self::$rates);
    }
}
