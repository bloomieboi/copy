<?php
/**
 * Хелпер для обработки изображений (Intervention Image).
 * Использование: создание превью макетов для типографии.
 * Требует: composer require intervention/image
 */
if (!class_exists(\Intervention\Image\ImageManager::class)) {
    return;
}

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Создать превью изображения (например, для макета заказа).
 * @param string $sourcePath путь к исходному файлу
 * @param string $destPath путь для сохранения превью
 * @param int $width ширина превью
 * @param int $height высота превью (0 = авто по пропорциям)
 * @return bool успех
 */
function createImagePreview(string $sourcePath, string $destPath, int $width = 300, int $height = 0): bool {
    try {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($sourcePath);
        if ($height > 0) {
            $image->cover($width, $height);
        } else {
            $image->scale(width: $width);
        }
        $image->save($destPath);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
