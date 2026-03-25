<?php
ini_set('display_errors', '0');
error_reporting(0);

// GET /api/forms

$payload = requireAuth();
$db      = Database::getInstance();

$stmt = $db->prepare('
    SELECT f.id, f.uuid, f.name, f.description, f.is_active, f.created_at, f.updated_at,
           COUNT(DISTINCT fi.id)  AS field_count,
           COUNT(DISTINCT s.id)   AS submission_count
    FROM   forms f
    LEFT JOIN fields      fi ON fi.form_id = f.id
    LEFT JOIN submissions s  ON s.form_id  = f.id
    WHERE  f.created_by = ?
    GROUP  BY f.id
    ORDER  BY f.created_at DESC
');
$stmt->execute([$payload['sub']]);
$forms = $stmt->fetchAll();

Response::success($forms);
