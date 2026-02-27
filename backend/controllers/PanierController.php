<?php
// Fichier: /backend/controllers/PanierController.php

class PanierController extends Controller {
    private $panierModel;
    private $produitModel;
    private $comportementModel;
    
    public function __construct() {
        $this->panierModel = new Panier();
        $this->produitModel = new Produit();
        $this->comportementModel = new Comportement();
    }
    
    /**
     * Récupère le panier de l'utilisateur
     */
    public function index() {
        $user = $this->getUser();
        
        if (!$user) {
            Response::error('Authentification requise', 401);
        }
        
        $cart = $this->panierModel->getByUserId($user['id']);
        
        // Calcule le total
        $total = 0;
        foreach ($cart as &$item) {
            $price = $item['promo_price'] ?? $item['price'];
            $item['subtotal'] = $price * $item['quantity'];
            $total += $item['subtotal'];
        }
        
        Response::success([
            'items' => $cart,
            'total' => $total,
            'count' => count($cart)
        ], 'Panier récupéré');
    }
    
    /**
     * Ajoute un produit au panier
     */
    public function add() {
        $user = $this->getUser();
        
        if (!$user) {
            Response::error('Authentification requise', 401);
        }
        
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['product_id', 'quantity']);
        
        $productId = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
        
        if ($quantity <= 0) {
            Response::error('Quantité invalide', 400);
        }
        
        // Vérifie que le produit existe et est disponible
        $product = $this->produitModel->find($productId);
        
        if (!$product) {
            Response::error('Produit non trouvé', 404);
        }
        
        if ($product['status'] !== 'ACTIVE') {
            Response::error('Produit non disponible', 400);
        }
        
        if ($product['stock'] < $quantity) {
            Response::error('Stock insuffisant', 400);
        }
        
        try {
            $this->panierModel->addOrUpdate($user['id'], $productId, $quantity);
            
            // Enregistre l'action
            $sessionId = session_id() ?: bin2hex(random_bytes(16));
            $this->comportementModel->logAction(
                $user['id'],
                $productId,
                'CART_ADD',
                $sessionId
            );
            
            Response::success(null, 'Produit ajouté au panier', 201);
            
        } catch (Exception $e) {
            Response::error('Erreur lors de l\'ajout au panier', 500);
        }
    }
    
    /**
     * Met à jour la quantité d'un produit
     */
    public function update($itemId) {
        $user = $this->getUser();
        
        if (!$user) {
            Response::error('Authentification requise', 401);
        }
        
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['quantity']);
        
        $quantity = (int)$data['quantity'];
        
        if ($quantity <= 0) {
            Response::error('Quantité invalide', 400);
        }
        
        // Vérifie que l'item appartient à l'utilisateur
        $item = $this->panierModel->find($itemId);
        
        if (!$item || $item['user_id'] != $user['id']) {
            Response::error('Élément non trouvé', 404);
        }
        
        // Vérifie le stock
        $product = $this->produitModel->find($item['product_id']);
        
        if ($product['stock'] < $quantity) {
            Response::error('Stock insuffisant', 400);
        }
        
        $this->panierModel->update($itemId, [
            'quantity' => $quantity,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        Response::success(null, 'Quantité mise à jour');
    }
    
    /**
     * Supprime un produit du panier
     */
    public function remove($itemId) {
        $user = $this->getUser();
        
        if (!$user) {
            Response::error('Authentification requise', 401);
        }
        
        // Vérifie que l'item appartient à l'utilisateur
        $item = $this->panierModel->find($itemId);
        
        if (!$item || $item['user_id'] != $user['id']) {
            Response::error('Élément non trouvé', 404);
        }
        
        $this->panierModel->delete($itemId);
        
        Response::success(null, 'Produit retiré du panier');
    }
    
    /**
     * Vide le panier
     */
    public function clear() {
        $user = $this->getUser();
        
        if (!$user) {
            Response::error('Authentification requise', 401);
        }
        
        $this->panierModel->clearByUserId($user['id']);
        
        Response::success(null, 'Panier vidé');
    }
}