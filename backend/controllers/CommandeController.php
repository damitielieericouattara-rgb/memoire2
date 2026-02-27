<?php
// Fichier: /backend/controllers/CommandeController.php

class CommandeController extends Controller {
    private $commandeModel;
    private $panierModel;
    private $produitModel;

    public function __construct() {
        $this->commandeModel = new Commande();
        $this->panierModel   = new Panier();
        $this->produitModel  = new Produit();
    }

    /** Liste des commandes */
    public function index() {
        $user   = $this->getUser();
        $orders = $this->commandeModel->getByUserIdWithItems($user['id']);
        Response::success($orders, 'Commandes récupérées');
    }

    /** Détail d'une commande */
    public function show($id) {
        $user  = $this->getUser();
        $order = $this->commandeModel->getWithDetails($id);
        if (!$order) { Response::error('Commande non trouvée', 404); }
        if ($order['user_id'] != $user['id'] && $user['role'] !== 'ADMIN') {
            Response::error('Accès refusé', 403);
        }
        Response::success($order, 'Commande récupérée');
    }

    /** Créer une commande */
    public function create() {
        $user = $this->getUser();
        $data = $this->getJsonInput();

        if (!$data) { Response::error('Données JSON invalides', 400); }

        // payment_method obligatoire, address_id optionnel
        $this->validateRequired($data, ['payment_method']);

        $paymentMethod = $data['payment_method'];
        $allowedMethods = ['WALLET', 'CASH_ON_DELIVERY', 'MOBILE_MONEY', 'CARD'];
        if (!in_array($paymentMethod, $allowedMethods)) {
            Response::error('Méthode de paiement invalide', 400);
        }

        // Récupère le panier
        $cart = $this->panierModel->getByUserId($user['id']);
        if (empty($cart)) {
            Response::error('Votre panier est vide', 400);
        }

        // Calcule les montants et vérifie le stock
        $amountHT = 0;
        $items    = [];

        foreach ($cart as $item) {
            $product = $this->produitModel->find($item['product_id']);
            if (!$product) {
                Response::error("Produit introuvable : {$item['name']}", 400);
            }
            if ($product['stock'] < $item['quantity']) {
                Response::error("Stock insuffisant pour {$product['name']} (disponible : {$product['stock']})", 400);
            }

            $price    = (!empty($product['promo_price']) && $product['promo_price'] > 0)
                            ? (float)$product['promo_price']
                            : (float)$product['price'];
            $subtotal  = $price * (int)$item['quantity'];
            $amountHT += $subtotal;

            $items[] = [
                'product_id'   => $item['product_id'],
                'product_name' => $product['name'],
                'quantity'     => (int)$item['quantity'],
                'unit_price'   => $price,
                'discount'     => 0,
                'subtotal'     => $subtotal,
            ];
        }

        $vatRate      = 18.00;
        $vat          = $amountHT * ($vatRate / 100);
        $shippingCost = 2500.00;
        $amountTTC    = $amountHT + $vat + $shippingCost;

        // Paiement WALLET → vérifie et débite
        if ($paymentMethod === 'WALLET') {
            $walletModel = new Wallet();
            $wallet      = $walletModel->getByUserId($user['id']);
            if (!$wallet || (float)$wallet['balance'] < $amountTTC) {
                Response::error('Solde wallet insuffisant. Montant requis : ' . number_format($amountTTC, 0, ',', ' ') . ' XOF', 400);
            }
            // Débit
            try {
                $walletModel->debit($wallet['id'], $amountTTC, "Paiement commande en attente");
            } catch (Exception $e) {
                Response::error($e->getMessage(), 400);
            }
        }

        // Gestion adresse
        $addressId       = $data['address_id'] ?? null;
        $addressSnapshot = null;
        if ($addressId) {
            try {
                $db      = Database::getInstance()->getConnection();
                $stmt    = $db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$addressId, $user['id']]);
                $address = $stmt->fetch();
                if ($address) {
                    $addressSnapshot = json_encode([
                        'street'      => $address['street'],
                        'complement'  => $address['complement'] ?? '',
                        'city'        => $address['city'],
                        'postal_code' => $address['postal_code'],
                        'country'     => $address['country'],
                    ]);
                }
            } catch (Exception $e) {}
        }

        if (!$addressSnapshot) {
            $addressSnapshot = json_encode([
                'street'      => $data['street'] ?? 'Non spécifiée',
                'complement'  => $data['complement'] ?? '',
                'city'        => $data['city'] ?? 'Abidjan',
                'postal_code' => $data['postal_code'] ?? '00000',
                'country'     => $data['country'] ?? "Côte d'Ivoire",
            ]);
        }

        try {
            $orderId = $this->commandeModel->createWithItems([
                'user_id'               => $user['id'],
                'address_id'            => $addressId,
                'status'                => 'RECEIVED',
                'amount_ht'             => $amountHT,
                'vat_rate'              => $vatRate,
                'amount_ttc'            => $amountTTC,
                'shipping_cost'         => $shippingCost,
                'payment_method'        => $paymentMethod,
                'delivery_address_snap' => $addressSnapshot,
                'notes'                 => $data['notes'] ?? null,
                'is_preorder'           => 0,
                'estimated_delivery'    => date('Y-m-d', strtotime('+3 days')),
            ], $items);

            // Vide le panier
            $this->panierModel->clearByUserId($user['id']);

            // Notification (non bloquante)
            try {
                $notif = new NotificationService();
                $order = $this->commandeModel->find($orderId);
                $notif->notifyOrderStatus($user['id'], $order['order_number'], 'RECEIVED');
            } catch (Exception $e) {}

            $order = $this->commandeModel->find($orderId);

            Response::success([
                'order_id'     => $orderId,
                'order_number' => $order['order_number'],
                'amount_ttc'   => $amountTTC,
                'status'       => 'RECEIVED',
            ], 'Commande créée avec succès', 201);

        } catch (Exception $e) {
            // Remboursement si paiement wallet effectué
            if ($paymentMethod === 'WALLET') {
                try {
                    $walletModel = new Wallet();
                    $wallet      = $walletModel->getByUserId($user['id']);
                    $walletModel->credit($wallet['id'], $amountTTC, "Remboursement commande échouée");
                } catch (Exception $re) {}
            }
            Response::error('Erreur lors de la création de la commande : ' . $e->getMessage(), 500);
        }
    }

    /** Suivi d'une commande */
    public function tracking($id) {
        $user  = $this->getUser();
        $order = $this->commandeModel->find($id);
        if (!$order || ($order['user_id'] != $user['id'] && $user['role'] !== 'ADMIN')) {
            Response::error('Commande non trouvée', 404);
        }
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY event_date ASC");
            $stmt->execute([$id]);
            $tracking = $stmt->fetchAll();
        } catch (Exception $e) {
            $tracking = [];
        }
        Response::success(['order' => $order, 'tracking' => $tracking], 'Suivi récupéré');
    }
}
