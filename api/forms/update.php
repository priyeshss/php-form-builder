<?php
declare(strict_types=1);
// PUT/PATCH /api/forms/{id}

$payload = requireAuth();
$id      = (int) ($_GET['id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$req    = new Request();
$errors = $req->validate(['name' => 'required|max:200']);
if ($errors) Response::error('Validation failed.', 422, $errors);

$name        = sanitize($req->get('name'));
$description = sanitize($req->get('description', ''));
$is_active   = (int) (bool) $req->get('is_active', 1);

$db->prepare('UPDATE forms SET name = ?, description = ?, is_active = ? WHERE id = ?')
   ->execute([$name, $description, $is_active, $id]);

$form = $db->prepare('SELECT * FROM forms WHERE id = ?');
$form->execute([$id]);

Response::success($form->fetch(), 'Form updated.');
