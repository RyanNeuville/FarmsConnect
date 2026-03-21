<?php
/**
 * api/export_pdf.php
 * Génère des rapports PDF professionnels et brandés FarmsConnect.
 * Utilise TCPDF pour une mise en page riche avec logo, couleurs et tableaux.
 * Types : global | alerts
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

forcer_connexion();

$type = $_GET['type'] ?? 'global';
$date = date('d/m/Y à H:i');
$dateFile = date('Y-m-d_H-i');
$logoPath = realpath(__DIR__ . '/../assets/icon.png');

// ─── COULEURS DE MARQUE ─────────────────────────────────────────────────────
define('FC_DARK',   [15, 43, 70]);       // #0f2b46
define('FC_GREEN',  [22, 163, 74]);      // #16a34a
define('FC_LIGHT',  [248, 250, 252]);    // slate-50
define('FC_BORDER', [226, 232, 240]);    // slate-200
define('FC_GRAY',   [100, 116, 139]);    // slate-500

// ─── CLASSE PDF PERSONNALISÉE ────────────────────────────────────────────────
class FarmsConnectPDF extends TCPDF {
    private string $reportTitle;
    private string $reportDate;
    private string $logoPath;

    public function __construct(string $title, string $date, string $logo) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->reportTitle = $title;
        $this->reportDate  = $date;
        $this->logoPath    = $logo;
        $this->SetCreator('FarmsConnect');
        $this->SetAuthor('FarmsConnect - Système de gestion agricole');
        $this->SetTitle($title);
        $this->SetSubject('Rapport FarmsConnect');
        $this->SetMargins(15, 40, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(true, 20);
    }

    public function Header(): void {
        // Fond de l'en-tête
        $this->SetFillColor(...FC_DARK);
        $this->Rect(0, 0, 210, 32, 'F');

        // Logo
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 14, 7, 16, 16, 'PNG');
        }

        // Titre de l'app
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(34, 9);
        $this->Cell(80, 7, 'FarmsConnect', 0, 0, 'L');

        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(148, 163, 184); // slate-400
        $this->SetXY(34, 17);
        $this->Cell(80, 5, 'Gestion de ferme connectée', 0, 0, 'L');

        // Titre du rapport (droite)
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetXY(120, 9);
        $this->Cell(75, 7, strtoupper($this->reportTitle), 0, 0, 'R');

        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(148, 163, 184);
        $this->SetXY(120, 17);
        $this->Cell(75, 5, 'Généré le ' . $this->reportDate, 0, 0, 'R');

        // Ligne verte de séparation
        $this->SetFillColor(...FC_GREEN);
        $this->Rect(0, 32, 210, 1.5, 'F');
    }

    public function Footer(): void {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(...FC_GRAY);
        $this->Cell(0, 10, 'FarmsConnect — Rapport confidentiel — Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}


// ─── DONNÉES COMMUNES ────────────────────────────────────────────────────────
$capteurs    = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur' ORDER BY id")->fetchAll();
$actionneurs = $pdo->query("SELECT * FROM equipements WHERE type = 'actionneur' ORDER BY id")->fetchAll();
$statsAl     = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN niveau='critique' THEN 1 ELSE 0 END) as critiques, SUM(CASE WHEN est_lu=0 THEN 1 ELSE 0 END) as non_lues FROM alertes")->fetch();
$alertes     = $pdo->query("SELECT a.*, e.nom as equipement_nom FROM alertes a JOIN equipements e ON a.equipement_id = e.id ORDER BY a.cree_le DESC")->fetchAll();


// ═══════════════════════════════════════════════════════════════════════════════
// RAPPORT GLOBAL
// ═══════════════════════════════════════════════════════════════════════════════
if ($type === 'global') {
    $pdf = new FarmsConnectPDF('Rapport Global de la Ferme', $date, $logoPath);
    $pdf->addPage();

    // ─── RÉSUMÉ EXÉCUTIF ──────────────────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->SetY(40);
    $pdf->Cell(0, 8, 'RÉSUMÉ EXÉCUTIF', 0, 1, 'L');

    $pdf->SetFillColor(...FC_LIGHT);
    $pdf->SetDrawColor(...FC_BORDER);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Rect(15, $pdf->GetY(), 180, 22, 'DF');
    $y = $pdf->GetY() + 4;

    $nbCrit = 0;
    foreach ($capteurs as $c) { if ($c['statut'] === 'critique') $nbCrit++; }
    $nbActifs = count(array_filter($actionneurs, fn($a) => $a['statut'] === 'marche'));

    $pdf->SetXY(20, $y);
    $pdf->Cell(55, 6, '🌿 Capteurs actifs : ' . count($capteurs), 0, 0);
    $pdf->Cell(55, 6, '⚡ Actionneurs actifs : ' . $nbActifs, 0, 0);
    $pdf->Cell(0, 6, '🔔 Alertes en attente : ' . $statsAl['non_lues'], 0, 1);
    $pdf->SetX(20);
    $pdf->Cell(55, 6, '🚨 Anomalies critiques : ' . $nbCrit, 0, 0);
    $pdf->Cell(55, 6, '📊 Total alertes : ' . $statsAl['total'], 0, 0);
    $pdf->Cell(0, 6, '✅ État : ' . ($nbCrit === 0 ? 'Optimal' : 'Anomalie détectée'), 0, 1);
    $pdf->Ln(6);

    // ─── TABLEAU CAPTEURS ────────────────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Cell(0, 8, 'ÉTAT DES CAPTEURS', 0, 1, 'L');

    // Entêtes
    $pdf->SetFillColor(...FC_DARK);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(8,  7, 'ID',        1, 0, 'C', true);
    $pdf->Cell(55, 7, 'Capteur',   1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Valeur',    1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Unité',     1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Seuil min', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Seuil max', 1, 0, 'C', true);
    $pdf->Cell(0,  7, 'Statut',    1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 8);
    $odd = true;
    foreach ($capteurs as $cap) {
        $statusColors = [
            'normal'   => [220, 252, 231], // green-100
            'critique' => [254, 226, 226], // red-100
            'alerte'   => [254, 243, 199], // yellow-100
        ];
        $bg = $statusColors[$cap['statut']] ?? [248, 250, 252];
        $pdf->SetFillColor(...($odd ? [248, 250, 252] : [255, 255, 255]));
        $odd = !$odd;
        $pdf->SetTextColor(...FC_DARK);

        $pdf->Cell(8,  6, $cap['id'],              1, 0, 'C', true);
        $pdf->Cell(55, 6, $cap['nom'],             1, 0, 'L', true);
        $pdf->Cell(30, 6, $cap['valeur_actuelle'], 1, 0, 'C', true);
        $pdf->Cell(20, 6, $cap['unite'],           1, 0, 'C', true);
        $pdf->Cell(25, 6, $cap['seuil_min'],       1, 0, 'C', true);
        $pdf->Cell(25, 6, $cap['seuil_max'] ?? '∞', 1, 0, 'C', true);

        // Statut coloré
        $pdf->SetFillColor(...$bg);
        $sc = ['normal' => [22, 163, 74], 'critique' => [239, 68, 68], 'alerte' => [245, 158, 11]];
        $pdf->SetTextColor(...($sc[$cap['statut']] ?? FC_GRAY));
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 6, strtoupper($cap['statut']), 1, 1, 'C', true);
        $pdf->SetFont('helvetica', '', 8);
    }
    $pdf->Ln(6);

    // ─── TABLEAU ACTIONNEURS ─────────────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Cell(0, 8, 'ÉTAT DES ACTIONNEURS', 0, 1, 'L');

    $pdf->SetFillColor(...FC_DARK);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(8,  7, 'ID',         1, 0, 'C', true);
    $pdf->Cell(80, 7, 'Actionneur', 1, 0, 'C', true);
    $pdf->Cell(0,  7, 'Statut',     1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 8);
    $odd = true;
    foreach ($actionneurs as $act) {
        $isOn = $act['statut'] === 'marche';
        $pdf->SetFillColor(...($odd ? [248, 250, 252] : [255, 255, 255]));
        $pdf->SetTextColor(...FC_DARK);
        $odd = !$odd;
        $pdf->Cell(8,  6, $act['id'],  1, 0, 'C', true);
        $pdf->Cell(80, 6, $act['nom'], 1, 0, 'L', true);
        $pdf->SetFillColor(...($isOn ? [220, 252, 231] : [241, 245, 249]));
        $pdf->SetTextColor(...($isOn ? [22, 163, 74] : FC_GRAY));
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 6, $isOn ? '● MARCHE' : '○ ARRÊT', 1, 1, 'C', true);
        $pdf->SetFont('helvetica', '', 8);
    }
    $pdf->Ln(6);

    // ─── RÉSUMÉ ALERTES ──────────────────────────────────────────────────────
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Cell(0, 8, 'RÉSUMÉ DES ALERTES', 0, 1, 'L');

    $pdf->SetFillColor(...FC_LIGHT);
    $pdf->SetDrawColor(...FC_BORDER);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(...FC_DARK);
    $cols = [
        ['Total', $statsAl['total']],
        ['Critiques', $statsAl['critiques']],
        ['Non lues', $statsAl['non_lues']],
    ];
    foreach ($cols as $col) {
        $pdf->Cell(58, 10, $col[0] . ' : ' . $col[1], 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->Output("farmsconnect_rapport_global_{$dateFile}.pdf", 'D');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// RAPPORT ALERTES
// ═══════════════════════════════════════════════════════════════════════════════
if ($type === 'alerts') {
    $pdf = new FarmsConnectPDF('Rapport des Alertes', $date, $logoPath);
    $pdf->addPage();
    $pdf->SetY(40);

    // Résumé
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Cell(0, 8, 'RÉSUMÉ DES ALERTES', 0, 1, 'L');

    $pdf->SetFillColor(...FC_LIGHT);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Cell(58, 8, 'Total : ' . $statsAl['total'],            1, 0, 'C', true);
    $pdf->Cell(58, 8, 'Critiques : ' . $statsAl['critiques'],    1, 0, 'C', true);
    $pdf->Cell(0,  8, 'Non lues : ' . $statsAl['non_lues'],      1, 1, 'C', true);
    $pdf->Ln(4);

    // Journal complet
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...FC_DARK);
    $pdf->Cell(0, 8, 'JOURNAL COMPLET DES ALERTES', 0, 1, 'L');

    $pdf->SetFillColor(...FC_DARK);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->Cell(10,  7, 'ID',         1, 0, 'C', true);
    $pdf->Cell(40,  7, 'Équipement', 1, 0, 'C', true);
    $pdf->Cell(20,  7, 'Gravité',    1, 0, 'C', true);
    $pdf->Cell(70,  7, 'Message',    1, 0, 'C', true);
    $pdf->Cell(25,  7, 'Lecture',    1, 0, 'C', true);
    $pdf->Cell(0,   7, 'Date',       1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 7);
    $odd = true;
    foreach ($alertes as $al) {
        if ($pdf->GetY() > 260) {
            $pdf->addPage();
            $pdf->SetY(40);
        }
        $isCrit = $al['niveau'] === 'critique';
        $bg = $isCrit ? [254, 226, 226] : ($odd ? [248, 250, 252] : [255, 255, 255]);
        $odd = !$odd;
        $pdf->SetFillColor(...$bg);
        $pdf->SetTextColor(...FC_DARK);

        $pdf->Cell(10, 5.5, $al['id'],              1, 0, 'C', true);
        $pdf->Cell(40, 5.5, substr($al['equipement_nom'], 0, 22), 1, 0, 'L', true);

        $pdf->SetTextColor(...($isCrit ? [239, 68, 68] : [245, 158, 11]));
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(20, 5.5, strtoupper($al['niveau']), 1, 0, 'C', true);

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(...FC_DARK);
        $pdf->Cell(70, 5.5, substr($al['message'], 0, 45), 1, 0, 'L', true);

        $pdf->SetTextColor(...($al['est_lu'] ? [22, 163, 74] : [239, 68, 68]));
        $pdf->Cell(25, 5.5, $al['est_lu'] ? '✔ Lu' : '✘ Non lu', 1, 0, 'C', true);

        $pdf->SetTextColor(...FC_GRAY);
        $pdf->Cell(0, 5.5, date('d/m/Y H:i', strtotime($al['cree_le'])), 1, 1, 'C', true);
    }

    if (empty($alertes)) {
        $pdf->SetTextColor(...FC_GRAY);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->Cell(0, 10, 'Aucune alerte enregistrée.', 1, 1, 'C', true);
    }

    $pdf->Output("farmsconnect_rapport_alertes_{$dateFile}.pdf", 'D');
    exit;
}

http_response_code(400);
echo 'Type d\'export inconnu.';
