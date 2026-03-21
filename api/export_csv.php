<?php
/**
 * api/export_csv.php
 * Génère des exports CSV personnalisés et bien formatés.
 * Types : sensors | alerts | history
 */
require_once '../config/db.php';
require_once '../includes/auth.php';

forcer_connexion();

$type = $_GET['type'] ?? 'sensors';
$date = date('Y-m-d_H-i');

// ─── UTILITAIRES CSV ─────────────────────────────────────────────────────────
function csvRow(array $cols): string {
    return implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $cols)) . "\r\n";
}

function csvHeader(string $filename): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
}

// ─── BOM + META RAPPORT ───────────────────────────────────────────────────────
function csvReportHeader(string $title): string {
    $out  = csvRow(['FarmsConnect - ' . $title]);
    $out .= csvRow(['Généré le', date('d/m/Y à H:i:s')]);
    $out .= csvRow(['Application', 'FarmsConnect - Gestion de ferme connectée']);
    $out .= csvRow(['URL', 'http://localhost:8000']);
    $out .= "\r\n";
    return $out;
}

// ═══════════════════════════════════════════════════════════════════════════════
// TYPE : SENSORS — État temps réel des capteurs
// ═══════════════════════════════════════════════════════════════════════════════
if ($type === 'sensors') {
    csvHeader("farmsconnect_capteurs_{$date}.csv");

    echo csvReportHeader('Rapport Capteurs en Temps Réel');
    echo csvRow(['ID', 'Nom du capteur', 'Type', 'Valeur actuelle', 'Unité', 'Seuil min', 'Seuil max', 'Statut', 'Horodatage']);

    $stmt = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur' ORDER BY id");
    while ($row = $stmt->fetch()) {
        echo csvRow([
            $row['id'],
            $row['nom'],
            ucfirst($row['type']),
            $row['valeur_actuelle'],
            $row['unite'],
            $row['seuil_min'],
            $row['seuil_max'] ?? 'N/A',
            strtoupper($row['statut']),
            date('d/m/Y H:i:s'),
        ]);
    }

    echo "\r\n";
    $countStmt = $pdo->query("SELECT COUNT(*) FROM equipements WHERE type = 'capteur'");
    echo csvRow(['Total capteurs exportés', $countStmt->fetchColumn()]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// TYPE : ALERTS — Journal complet des alertes
// ═══════════════════════════════════════════════════════════════════════════════
if ($type === 'alerts') {
    csvHeader("farmsconnect_alertes_{$date}.csv");

    echo csvReportHeader('Journal des Alertes');
    echo csvRow(['ID', 'Équipement concerné', 'Niveau de gravité', 'Message', 'Statut lecture', 'Date et heure']);

    $stmt = $pdo->query("
        SELECT a.*, e.nom as equipement_nom
        FROM alertes a
        JOIN equipements e ON a.equipement_id = e.id
        ORDER BY a.cree_le DESC
    ");
    $total = 0;
    $critiques = 0;
    while ($row = $stmt->fetch()) {
        $total++;
        if ($row['niveau'] === 'critique') $critiques++;
        echo csvRow([
            $row['id'],
            $row['equipement_nom'],
            strtoupper($row['niveau']),
            $row['message'],
            $row['est_lu'] ? 'Lu' : 'Non lu',
            date('d/m/Y H:i:s', strtotime($row['cree_le'])),
        ]);
    }

    echo "\r\n";
    echo csvRow(['Total alertes', $total]);
    echo csvRow(['Dont critiques', $critiques]);
    echo csvRow(['Dont non lues', $total - $critiques]); // approximation
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// TYPE : HISTORY — Historique des mesures des 24 dernières heures
// ═══════════════════════════════════════════════════════════════════════════════
if ($type === 'history') {
    csvHeader("farmsconnect_historique_{$date}.csv");

    echo csvReportHeader('Historique des Mesures (24 dernières heures)');
    echo csvRow(['ID', 'Capteur', 'Valeur mesurée', 'Unité', 'Date et heure de mesure']);

    $stmt = $pdo->query("
        SELECT h.*, e.nom, e.unite
        FROM historique_capteurs h
        JOIN equipements e ON h.equipement_id = e.id
        WHERE h.enregistre_le >= NOW() - INTERVAL 24 HOUR
        ORDER BY h.enregistre_le DESC
    ");
    $total = 0;
    while ($row = $stmt->fetch()) {
        $total++;
        echo csvRow([
            $row['id'],
            $row['nom'],
            $row['valeur'],
            $row['unite'],
            date('d/m/Y H:i:s', strtotime($row['enregistre_le'])),
        ]);
    }

    echo "\r\n";
    echo csvRow(['Total mesures exportées', $total]);
    exit;
}

// Fallback
http_response_code(400);
echo 'Type d\'export inconnu.';
