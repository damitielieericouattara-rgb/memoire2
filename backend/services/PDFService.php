<?php
// Fichier: /backend/services/PDFService.php

// Note: Utilise TCPDF ou mPDF (à installer via Composer)
// Pour la démo, version simplifiée avec génération HTML

class PDFService {
    private $facturesDir;
    private $qrcodesDir;
    
    public function __construct() {
        $this->facturesDir = __DIR__ . '/../../storage/factures';
        $this->qrcodesDir = __DIR__ . '/../../storage/qrcodes';
        
        // Crée les dossiers s'ils n'existent pas
        if (!is_dir($this->facturesDir)) {
            mkdir($this->facturesDir, 0755, true);
        }
        if (!is_dir($this->qrcodesDir)) {
            mkdir($this->qrcodesDir, 0755, true);
        }
    }
    
    /**
     * Génère une facture PDF pour une commande
     */
    public function generateInvoice($orderId) {
        // Récupère les données de la commande
        $commandeModel = new Commande();
        $order = $commandeModel->getWithDetails($orderId);
        
        if (!$order) {
            throw new Exception('Commande non trouvée');
        }
        
        // Récupère l'utilisateur
        $userModel = new User();
        $user = $userModel->find($order['user_id']);
        
        // Génère le numéro de facture
        $invoiceNumber = $this->generateInvoiceNumber($orderId);
        
        // Génère le QR Code
        $qrCodeUrl = $this->generateQRCode($invoiceNumber, $order);
        
        // Génère le HTML de la facture
        $html = $this->generateInvoiceHTML($order, $user, $invoiceNumber, $qrCodeUrl);
        
        // Sauvegarde le HTML (en production, convertir en PDF avec TCPDF/mPDF)
        $filename = $invoiceNumber . '.html';
        $filepath = $this->facturesDir . '/' . $filename;
        file_put_contents($filepath, $html);
        
        // URL relative pour le frontend
        $pdfUrl = '/storage/factures/' . $filename;
        
        // Enregistre en base de données
        $invoiceModel = new Invoice();
        $invoiceModel->create([
            'order_id' => $orderId,
            'invoice_number' => $invoiceNumber,
            'pdf_url' => $pdfUrl,
            'qr_code_url' => $qrCodeUrl,
            'amount_ht' => $order['amount_ht'],
            'vat' => $order['amount_ttc'] - $order['amount_ht'],
            'amount_ttc' => $order['amount_ttc'],
            'issued_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'invoice_number' => $invoiceNumber,
            'pdf_url' => $pdfUrl,
            'qr_code_url' => $qrCodeUrl
        ];
    }
    
    /**
     * Génère un numéro de facture
     */
    private function generateInvoiceNumber($orderId) {
        $year = date('Y');
        return "FAC-{$year}-" . str_pad($orderId, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Génère un QR Code (version simplifiée)
     */
    private function generateQRCode($invoiceNumber, $order) {
        // Données à encoder
        $data = json_encode([
            'invoice' => $invoiceNumber,
            'amount' => $order['amount_ttc'],
            'date' => date('Y-m-d'),
            'hash' => hash_hmac('sha256', $invoiceNumber . $order['amount_ttc'], 'secret_key')
        ]);
        
        // En production, utiliser une bibliothèque QR Code PHP
        // Pour la démo, utilise une API externe
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data);
        
        return $qrUrl;
    }
    
    /**
     * Génère le HTML de la facture
     */
    private function generateInvoiceHTML($order, $user, $invoiceNumber, $qrCodeUrl) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Facture ' . $invoiceNumber . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info-block { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f4f4f4; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .qr-code { text-align: center; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>E-COMMERCE INTELLIGENT</h1>
                <p>Votre boutique en ligne</p>
                <hr>
            </div>
            
            <div class="info-block">
                <h2>FACTURE N° ' . $invoiceNumber . '</h2>
                <p><strong>Date d\'émission :</strong> ' . date('d/m/Y') . '</p>
                <p><strong>Commande N° :</strong> ' . $order['order_number'] . '</p>
            </div>
            
            <div class="info-block">
                <h3>Client</h3>
                <p>' . htmlspecialchars($user['first_name'] . ' ' . $user['name']) . '</p>
                <p>' . htmlspecialchars($user['email']) . '</p>
            </div>
            
            <h3>Détails de la commande</h3>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($order['items'] as $item) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item['product_name']) . '</td>
                        <td>' . $item['quantity'] . '</td>
                        <td>' . number_format($item['unit_price'], 0, ',', ' ') . ' FCFA</td>
                        <td>' . number_format($item['subtotal'], 0, ',', ' ') . ' FCFA</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Montant HT</strong></td>
                        <td><strong>' . number_format($order['amount_ht'], 0, ',', ' ') . ' FCFA</strong></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>TVA (18%)</strong></td>
                        <td><strong>' . number_format($order['amount_ttc'] - $order['amount_ht'], 0, ',', ' ') . ' FCFA</strong></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Frais de livraison</strong></td>
                        <td><strong>' . number_format($order['shipping_cost'], 0, ',', ' ') . ' FCFA</strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;"><strong>TOTAL TTC</strong></td>
                        <td><strong>' . number_format($order['amount_ttc'], 0, ',', ' ') . ' FCFA</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="qr-code">
                <p><strong>Code de vérification</strong></p>
                <img src="' . $qrCodeUrl . '" alt="QR Code" />
                <p><small>Scannez ce code pour vérifier l\'authenticité de la facture</small></p>
            </div>
            
            <p style="text-align: center; margin-top: 40px; color: #888;">
                Merci pour votre confiance !
            </p>
        </body>
        </html>';
        
        return $html;
    }
}

// Model Invoice
class Invoice extends Model {
    protected $table = 'invoices';
}