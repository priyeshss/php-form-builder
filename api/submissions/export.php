<?php
ini_set('display_errors', '0');
error_reporting(0);

// GET /api/forms/{form_id}/submissions/export

$payload = requireAuth();
$form_id = (int)($_GET['form_id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id, name FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
$form = $stmt->fetch();
if (!$form) Response::error('Form not found.', 404);

// Get fields in order
$fStmt = $db->prepare('SELECT id, label FROM fields WHERE form_id = ? ORDER BY sort_order ASC, id ASC');
$fStmt->execute([$form_id]);
$fields = $fStmt->fetchAll();

// Get all submissions
$sStmt = $db->prepare('SELECT id, submitted_at, ip_address FROM submissions WHERE form_id = ? ORDER BY submitted_at DESC');
$sStmt->execute([$form_id]);
$submissions = $sStmt->fetchAll();

// Override JSON content type for CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="form_' . $form_id . '_submissions.csv"');

$out = fopen('php://output', 'w');

// Header row
$headers = ['Submission ID', 'Submitted At', 'IP Address'];
foreach ($fields as $f) $headers[] = $f['label'];
fputcsv($out, $headers);

// Value rows
$valStmt = $db->prepare('
    SELECT field_id, value FROM submission_values WHERE submission_id = ?
');
foreach ($submissions as $sub) {
    $valStmt->execute([$sub['id']]);
    $valMap = [];
    foreach ($valStmt->fetchAll() as $v) {
        $valMap[$v['field_id']] = $v['value'];
    }

    $row = [$sub['id'], $sub['submitted_at'], $sub['ip_address']];
    foreach ($fields as $f) {
        $row[] = $valMap[$f['id']] ?? '';
    }
    fputcsv($out, $row);
}

fclose($out);
exit;
