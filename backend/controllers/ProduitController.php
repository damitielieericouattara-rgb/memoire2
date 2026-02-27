<?php
// Fichier: /backend/controllers/ProduitController.php

class ProduitController extends Controller {
    private $produitModel;

    public function __construct() {
        $this->produitModel = new Produit();
    }

    public function index() {
        $params   = $this->getQueryParams();
        $products = $this->produitModel->search($params);
        $total    = count($products);
        $page     = (int)($params['page'] ?? 1);
        $perPage  = (int)($params['per_page'] ?? 20);
        Response::paginated($products, $page, $perPage, $total, 'Produits récupérés');
    }

    public function show($id) {
        $product = $this->produitModel->findWithImages($id);
        if (!$product) { Response::error('Produit non trouvé', 404); }

        // Log vue (non bloquant)
        try {
            $this->produitModel->incrementPopularity($id);
            if ($this->getUser()) {
                $comportement = new Comportement();
                $comportement->logAction($this->getUser()['id'], $id, 'VIEW', session_id() ?: uniqid(), []);
            }
        } catch (Exception $e) {}

        Response::success($product, 'Produit récupéré');
    }

    public function search() {
        $query = $_GET['q'] ?? '';
        if (empty($query)) { Response::error('Terme de recherche requis', 400); }
        $params            = $this->getQueryParams();
        $params['q']       = $query;
        $results           = $this->produitModel->search($params);
        Response::success($results, count($results) . ' résultat(s)');
    }

    public function popular() {
        $limit    = (int)($_GET['limit'] ?? 10);
        $products = $this->produitModel->getMostPopular($limit);
        Response::success($products, 'Produits populaires');
    }

    public function recommendations() {
        $user = $this->getUser();
        // Simplifié : retourne les produits populaires
        $products = $this->produitModel->getMostPopular(10);
        Response::success($products, 'Recommandations');
    }
}
