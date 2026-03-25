<?php
ini_set('display_errors', '0');
error_reporting(0);

// POST /api/forms/{form_id}/submissions  — PUBLIC, no JWT required

$form_id = (int)($_GET['form_id'] ?? 0);
$db      = Database::getInstance();

// Verify form exists and is active
$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND is_active = 1');
$stmt->execute([$form_id]);
if (!$stmt->fetch()) Response::error('Form not found or inactive.', 404);

// Load fields
$fStmt = $db->prepare('SELECT * FROM fields WHERE form_id = ? ORDER BY sort_order ASC, id ASC');
$fStmt->execute([$form_id]);
$fields = $fStmt->fetchAll();

$req    = new Request();
$body   = $req->all();
$errors = [];

// Server-side validation per field
foreach ($fields as $field) {
    $key   = $field['field_name'];
    $value = $body[$key] ?? null;

    if ($field['is_required'] && ($value === null || $value === '' || $value === [])) {
        $errors[$key] = "{$field['label']} is required.";
        continue;
    }

    if ($value !== null && $value !== '') {
        switch ($field['field_type']) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                    $errors[$key] = "{$field['label']} must be a valid email.";
                break;
            case 'number':
                if (!is_numeric($value))
                    $errors[$key] = "{$field['label']} must be a number.";
                break;
        }
    }
}

if ($errors) Response::error('Validation failed.', 422, $errors);

// Insert submission
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
$ua = sanitize(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500));

$db->beginTransaction();
try {
    $sStmt = $db->prepare('INSERT INTO submissions (form_id, ip_address, user_agent) VALUES (?, ?, ?)');
    $sStmt->execute([$form_id, $ip, $ua]);
    $submission_id = (int)$db->lastInsertId();

    $vStmt = $db->prepare('
        INSERT INTO submission_values (submission_id, field_id, field_label, value)
        VALUES (?, ?, ?, ?)
    ');

    foreach ($fields as $field) {
        $key   = $field['field_name'];
        $value = $body[$key] ?? null;
        if (is_array($value)) $value = implode(', ', $value);
        $vStmt->execute([$submission_id, $field['id'], $field['label'], sanitize((string)($value ?? ''))]);
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    Response::error('Submission failed. Please try again.', 500);
}

Response::success(['submission_id' => $submission_id], 'Form submitted successfully.', 201);
