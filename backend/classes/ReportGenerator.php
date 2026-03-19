<?php
/**
 * SneakX — Générateur de Rapports PDF Mensuels
 * Génère un rapport HTML→PDF via wkhtmltopdf ou fallback HTML
 * /backend/classes/ReportGenerator.php
 */

class ReportGenerator {

    private PDO $db;
    private string $reportDir;

    public function __construct() {
        $this->db        = Database::getInstance();
        $this->reportDir = UPLOAD_PATH . '/reports/';
        if (!is_dir($this->reportDir)) mkdir($this->reportDir, 0755, true);
    }

    /**
     * Générer le rapport mensuel
     */
    public function generateMonthly(int $year, int $month): array {
        $periode = sprintf('%04d-%02d', $year, $month);
        $label   = (new DateTime("{$periode}-01"))->format('F Y');

        // Collecte des données
        $data = $this->collectData($year, $month);

        // Générer le fichier HTML
        $html     = $this->renderHtml($data, $label);
        $filename = "rapport_{$periode}.html";
        $filepath = $this->reportDir . $filename;
        file_put_contents($filepath, $html);

        // Tenter de générer PDF si wkhtmltopdf disponible
        $pdfFile = "rapport_{$periode}.pdf";
        $pdfPath = $this->reportDir . $pdfFile;
        $pdfGenerated = false;

        if (shell_exec('which wkhtmltopdf 2>/dev/null')) {
            $cmd = "wkhtmltopdf --quiet --page-size A4 '{$filepath}' '{$pdfPath}' 2>/dev/null";
            exec($cmd, $output, $code);
            $pdfGenerated = ($code === 0 && file_exists($pdfPath));
        }

        // Sauvegarder en base
        $stmt = $this->db->prepare(
            "INSERT INTO reports (type, periode, fichier, donnees)
             VALUES ('mensuel', ?, ?, ?)
             ON DUPLICATE KEY UPDATE fichier = VALUES(fichier), donnees = VALUES(donnees)"
        );
        $stmt->execute([$periode, $pdfGenerated ? $pdfFile : $filename, json_encode($data)]);

        return [
            'success'  => true,
            'periode'  => $periode,
            'label'    => $label,
            'fichier'  => $pdfGenerated ? $pdfFile : $filename,
            'url'      => UPLOAD_URL . '/reports/' . ($pdfGenerated ? $pdfFile : $filename),
            'pdf'      => $pdfGenerated,
            'donnees'  => $data,
        ];
    }

    /**
     * Collecter les données du mois
     */
    private function collectData(int $year, int $month): array {
        $debut = sprintf('%04d-%02d-01', $year, $month);
        $fin   = date('Y-m-t', strtotime($debut));

        // KPIs principaux
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS nb_commandes,
                    COALESCE(SUM(total_ttc),0) AS ca_total,
                    COALESCE(AVG(total_ttc),0) AS panier_moyen
             FROM orders
             WHERE created_at BETWEEN ? AND ?
               AND statut NOT IN ('annulee','remboursee')"
        );
        $stmt->execute([$debut . ' 00:00:00', $fin . ' 23:59:59']);
        $kpi = $stmt->fetch();

        // Ventes par jour
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS date,
                    COUNT(*) AS commandes,
                    COALESCE(SUM(total_ttc),0) AS total
             FROM orders
             WHERE created_at BETWEEN ? AND ?
               AND statut NOT IN ('annulee')
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );
        $stmt->execute([$debut . ' 00:00:00', $fin . ' 23:59:59']);
        $ventesParJour = $stmt->fetchAll();

        // Top produits
        $stmt = $this->db->prepare(
            "SELECT oi.nom_produit,
                    SUM(oi.quantite) AS quantite,
                    SUM(oi.sous_total) AS ca
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.created_at BETWEEN ? AND ?
               AND o.statut NOT IN ('annulee')
             GROUP BY oi.nom_produit
             ORDER BY quantite DESC
             LIMIT 10"
        );
        $stmt->execute([$debut . ' 00:00:00', $fin . ' 23:59:59']);
        $topProduits = $stmt->fetchAll();

        // Répartition méthodes de paiement
        $stmt = $this->db->prepare(
            "SELECT methode_paiement, COUNT(*) AS nb, COALESCE(SUM(total_ttc),0) AS total
             FROM orders
             WHERE created_at BETWEEN ? AND ?
               AND statut NOT IN ('annulee')
             GROUP BY methode_paiement"
        );
        $stmt->execute([$debut . ' 00:00:00', $fin . ' 23:59:59']);
        $paiements = $stmt->fetchAll();

        // Nouveaux utilisateurs
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ? AND role = 'client'"
        );
        $stmt->execute([$debut . ' 00:00:00', $fin . ' 23:59:59']);
        $nouveauxUsers = (int) $stmt->fetchColumn();

        return [
            'kpi'           => $kpi,
            'ventes_par_jour' => $ventesParJour,
            'top_produits'  => $topProduits,
            'paiements'     => $paiements,
            'nouveaux_users'=> $nouveauxUsers,
        ];
    }

    /**
     * Rendu HTML du rapport
     */
    private function renderHtml(array $data, string $label): string {
        $kpi          = $data['kpi'];
        $topProduits  = $data['top_produits'];
        $paiements    = $data['paiements'];
        $nbCommandes  = (int) $kpi['nb_commandes'];
        $caTotal      = number_format((float)$kpi['ca_total'], 0, ',', ' ');
        $panierMoyen  = number_format((float)$kpi['panier_moyen'], 0, ',', ' ');

        $produitsRows = '';
        foreach ($topProduits as $i => $p) {
            $ca = number_format((float)$p['ca'], 0, ',', ' ');
            $produitsRows .= "<tr>
                <td>" . ($i+1) . "</td>
                <td>" . htmlspecialchars($p['nom_produit']) . "</td>
                <td style='text-align:center'>" . $p['quantite'] . "</td>
                <td style='text-align:right'>{$ca} FCFA</td>
            </tr>";
        }

        $paiementRows = '';
        $methodLabels = [
            'wave' => 'Wave CI', 'orange_money' => 'Orange Money',
            'mtn_momo' => 'MTN MoMo', 'moov_money' => 'Moov Money',
            'carte_bancaire' => 'Carte Bancaire', 'livraison' => 'Paiement à la Livraison',
        ];
        foreach ($paiements as $p) {
            $label2 = $methodLabels[$p['methode_paiement']] ?? $p['methode_paiement'];
            $total  = number_format((float)$p['total'], 0, ',', ' ');
            $paiementRows .= "<tr><td>{$label2}</td><td style='text-align:center'>{$p['nb']}</td><td style='text-align:right'>{$total} FCFA</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Mensuel SneakX — {$label}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1a1a1a; background: #fff; font-size: 13px; }
.header { background: linear-gradient(135deg, #FF5C00, #FF8C00); color: #fff; padding: 36px 48px; }
.header h1 { font-size: 32px; font-weight: 900; letter-spacing: -1px; }
.header .sub { opacity: .8; margin-top: 6px; font-size: 14px; }
.logo { font-size: 22px; font-weight: 900; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 10px; }
.content { padding: 36px 48px; }
.kpi-row { display: flex; gap: 20px; margin-bottom: 36px; }
.kpi { flex: 1; background: #f9f9f9; border-left: 4px solid #FF5C00; padding: 18px 22px; border-radius: 6px; }
.kpi .val { font-size: 28px; font-weight: 900; color: #FF5C00; margin-bottom: 4px; }
.kpi .lab { color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
h2 { font-size: 18px; font-weight: 800; margin-bottom: 16px; border-bottom: 2px solid #FF5C00; padding-bottom: 8px; color: #1a1a1a; }
table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
thead th { background: #FF5C00; color: #fff; padding: 10px 14px; text-align: left; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; }
tbody td { padding: 10px 14px; border-bottom: 1px solid #eee; color: #333; }
tbody tr:nth-child(even) td { background: #fafafa; }
.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #999; font-size: 11px; }
.badge { background: #FF5C00; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; }
@media print { body { -webkit-print-color-adjust: exact; } }
</style>
</head>
<body>
<div class="header">
  <div class="logo">SneakX</div>
  <h1>Rapport Mensuel</h1>
  <div class="sub">{$label} — Généré le {$this->now()}</div>
</div>
<div class="content">
  <div class="kpi-row">
    <div class="kpi"><div class="val">{$nbCommandes}</div><div class="lab">Commandes</div></div>
    <div class="kpi"><div class="val">{$caTotal} FCFA</div><div class="lab">Chiffre d'affaires</div></div>
    <div class="kpi"><div class="val">{$panierMoyen} FCFA</div><div class="lab">Panier moyen</div></div>
    <div class="kpi"><div class="val">{$data['nouveaux_users']}</div><div class="lab">Nouveaux clients</div></div>
  </div>

  <h2>Top 10 Produits</h2>
  <table>
    <thead><tr><th>#</th><th>Produit</th><th style="text-align:center">Qté vendue</th><th style="text-align:right">CA</th></tr></thead>
    <tbody>{$produitsRows}</tbody>
  </table>

  <h2>Répartition des Paiements</h2>
  <table>
    <thead><tr><th>Méthode</th><th style="text-align:center">Commandes</th><th style="text-align:right">Total</th></tr></thead>
    <tbody>{$paiementRows}</tbody>
  </table>

  <div class="footer">
    <strong>SneakX</strong> — Plateforme e-commerce africaine inclusive<br>
    Rapport généré automatiquement le {$this->now()}
  </div>
</div>
</body>
</html>
HTML;
    }

    private function now(): string {
        return date('d/m/Y à H:i');
    }
}
