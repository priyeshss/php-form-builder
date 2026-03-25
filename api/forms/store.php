<?php
ini_set('display_errors', '0');
error_reporting(0);

// POST /api/forms

$payload = requireAuth();
$req     = new Request();

$errors = $req->validate(['name' => 'required|max:200']);
if ($errors) Response::error('Validation failed.', 422, $errors);

$name        = sanitize($req->get('name'));
$description = sanitize($req->get('description', ''));
$uuid        = generateUUID();

$db   = Database::getInstance();
$stmt = $db->prepare('INSERT INTO forms (uuid, name, description, created_by) VALUES (?, ?, ?, ?)');
$stmt->execute([$uuid, $name, $description, $payload['sub']]);
$id = (int) $db->lastInsertId();

$form = $db->prepare('SELECT * FROM forms WHERE id = ?');
$form->execute([$id]);

Response::success($form->fetch(), 'Form created.', 201);
