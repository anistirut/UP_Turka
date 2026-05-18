<?php

require_once __DIR__ . '/../Contexts/DishContext.php';

class DishController
{
    private DishContext $dishCtx;

    public function __construct(DishContext $dishCtx)
    {
        $this->dishCtx = $dishCtx;
    }

    public function createDish($name, $compound, $price, $upload_file)
    {
        $name     = trim((string) $name);
        $compound = trim((string) $compound);
        $price    = (float) $price;

        if ($name === '' || $compound === '') {
            return ['success' => false, 'message' => 'Заполните название и состав'];
        }

        if (empty($upload_file['name'])) {
            return ['success' => false, 'message' => 'Добавьте изображение блюда'];
        }

        $uploadDir = __DIR__ . '/../../resources/img/';
        $ext       = pathinfo($upload_file['name'], PATHINFO_EXTENSION);
        $newName   = uniqid('dish_', true) . '.' . $ext;
        $fullPath  = $uploadDir . $newName;

        if (!move_uploaded_file($upload_file['tmp_name'], $fullPath)) {
            return ['success' => false, 'message' => 'Не удалось сохранить файл'];
        }

        if ($this->dishCtx->insert($name, $compound, $price, $newName)) {
            return ['success' => true, 'message' => 'Блюдо добавлено'];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения в БД'];
    }

    public function updateDish($id, $name, $compound, $price, $upload_file, $current_img)
    {
        $name     = trim((string) $name);
        $compound = trim((string) $compound);
        $price    = (float) $price;
        $imgPath  = (string) $current_img;

        if (!empty($upload_file['name'])) {
            $uploadDir = __DIR__ . '/../../resources/img/';
            $ext       = pathinfo($upload_file['name'], PATHINFO_EXTENSION);
            $newName   = uniqid('dish_', true) . '.' . $ext;
            $fullPath  = $uploadDir . $newName;
            if (!move_uploaded_file($upload_file['tmp_name'], $fullPath)) {
                return ['success' => false, 'message' => 'Не удалось сохранить файл'];
            }
            $imgPath = $newName;
        }

        if ($this->dishCtx->update((int) $id, $name, $compound, $price, $imgPath)) {
            return ['success' => true, 'message' => 'Сохранено'];
        }

        return ['success' => false, 'message' => 'Ошибка обновления'];
    }
}
