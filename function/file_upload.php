<?php
/**
 * Загрузка файлов для заказов (таблица order_file).
 */

function uploadOrderFile(array $file, int $orderId, int $userId) {
    global $pdo;

    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Неверные параметры загрузки файла.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return null;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Превышен максимальный размер файла.');
        default:
            throw new RuntimeException('Неизвестная ошибка загрузки файла.');
    }

    if ($file['size'] > 50 * 1024 * 1024) { // 50 МБ
        throw new RuntimeException('Файл слишком большой (максимум 50 МБ).');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif',
        'text/plain'
    ];

    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new RuntimeException('Недопустимый тип файла.');
    }

    $uploadDir = __DIR__ . '/../uploads/orders/' . $orderId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $file['name']);
    $fileName = uniqid('file_', true) . '_' . $safeName;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    $relativePath = 'uploads/orders/' . $orderId . '/' . $fileName;

    $stmt = $pdo->prepare(
        "INSERT INTO order_file (order_id, file_name, file_path, file_size, uploaded_by)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$orderId, $file['name'], $relativePath, $file['size'], $userId]);

    return $pdo->lastInsertId();
}

function getOrderFiles(int $orderId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM order_file WHERE order_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll() ?: [];
}

