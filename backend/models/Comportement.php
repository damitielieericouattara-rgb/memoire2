<?php
// Fichier: /backend/models/Comportement.php

class Comportement extends Model {
    // Stocke les événements dans la table `product_views` (profil d'achat)
    protected $table = 'product_views';

    /**
     * Enregistre une action utilisateur sur un produit.
     *
     * @param int|null $userId      Utilisateur (peut être null si non connecté)
     * @param int      $productId   Produit concerné
     * @param string   $actionType  VIEW, SEARCH, CART_ADD, PURCHASE, VOCAL, CART_ABANDON…
     * @param string   $sessionId   Identifiant de session (PHP ou généré)
     * @param array    $metadata    Métadonnées optionnelles : ['duration' => 12, 'page_url' => '...', 'value' => '...']
     * @return int|null             ID de la ligne créée ou null si erreur (non bloquant)
     */
    public function logAction($userId, $productId, $actionType, $sessionId, $metadata = []) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            // Anonymisation simple de l'IP pour les stats (x.x.x.0)
            if ($ip && strpos($ip, '.') !== false) {
                $parts = explode('.', $ip);
                $parts[3] = '0';
                $ip = implode('.', $parts);
            }

            $data = [
                'user_id'       => $userId,
                'product_id'    => $productId,
                'action_type'   => $actionType,
                'view_duration' => $metadata['duration'] ?? null,
                'session_id'    => $sessionId,
                'ip_anonymized' => $ip,
                'page_url'      => $metadata['page_url'] ?? ($_SERVER['REQUEST_URI'] ?? null),
                'value_action'  => $metadata['value'] ?? null,
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            return $this->create($data);
        } catch (Exception $e) {
            // Erreur de tracking non bloquante pour ne jamais casser le tunnel d'achat
            return null;
        }
    }

    /**
     * Retourne les produits les plus vus par un utilisateur (profil d'achat).
     *
     * @param int $userId
     * @param int $limit
     * @return array [ ['product_id' => 1, 'views' => 10], … ]
     */
    public function getMostViewedByUser($userId, $limit = 5) {
        $sql = "SELECT product_id, COUNT(*) AS views
                FROM {$this->table}
                WHERE user_id = :user_id
                  AND action_type IN ('VIEW','CLICK','CART_ADD','PURCHASE')
                GROUP BY product_id
                ORDER BY views DESC, MAX(created_at) DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    
    /**
     * Analyse les préférences de catégories d'un utilisateur
     */
    public function getCategoryPreferences($userId) {
        $sql = "SELECT c.id, c.name, COUNT(*) as interaction_count
                FROM {$this->table} pv
                JOIN products p ON pv.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE pv.user_id = :user_id
                GROUP BY c.id, c.name
                ORDER BY interaction_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Détection d'abandon de panier
     */
    public function detectCartAbandonment($sessionId, $hours = 2) {
        $sql = "SELECT DISTINCT pv.product_id, p.name, p.price
                FROM {$this->table} pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.session_id = :session_id
                  AND pv.action_type = 'CART_ADD'
                  AND pv.created_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)
                  AND NOT EXISTS (
                      SELECT 1 FROM {$this->table} pv2 
                      WHERE pv2.session_id = :session_id 
                        AND pv2.product_id = pv.product_id 
                        AND pv2.action_type = 'PURCHASE'
                  )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':session_id', $sessionId);
        $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Statistiques de comportement pour l'admin
     */
    public function getBehaviorStats($days = 30) {
        $sql = "SELECT 
                    action_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT session_id) as unique_sessions
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY action_type
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
