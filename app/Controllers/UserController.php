<?php

require_once __DIR__ . '/../Contexts/UserContext.php';

class UserController
{
    private UserContext $userCtx;

    public function __construct(UserContext $userCtx)
    {
        $this->userCtx = $userCtx;
    }

    public function updateClientProfile($id, $name, $surname, $patronomyc, $new_password = null)
    {
        $name       = trim((string) $name);
        $surname    = trim((string) $surname);
        $patronomyc = trim((string) $patronomyc);

        if ($name === '' || $surname === '' || $patronomyc === '') {
            return ['success' => false, 'message' => 'Заполните ФИО.'];
        }

        if ($new_password !== null && trim((string) $new_password) !== '') {
            $hash = password_hash((string) $new_password, PASSWORD_DEFAULT);
            $ok   = $this->userCtx->updateProfileWithPassword((int) $id, $name, $surname, $patronomyc, $hash);
        } else {
            $ok = $this->userCtx->updateProfile((int) $id, $name, $surname, $patronomyc);
        }

        if ($ok) {
            return ['success' => true, 'message' => 'Профиль обновлён'];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения профиля'];
    }

    public function createUserAdmin($surname, $name, $patronomyc, $phone, $password, $confirm_password, $role)
    {
        $surname    = trim((string) $surname);
        $name       = trim((string) $name);
        $patronomyc = trim((string) $patronomyc);
        $phone      = preg_replace('/\D+/', '', (string) $phone);
        $role       = (string) $role;

        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Пароли не совпадают'];
        }
        if (!in_array($role, ['client', 'courier', 'admin'], true)) {
            return ['success' => false, 'message' => 'Некорректная роль'];
        }

        if ($this->userCtx->phoneExists($phone)) {
            return ['success' => false, 'message' => 'Телефон уже занят'];
        }

        $hash   = password_hash((string) $password, PASSWORD_DEFAULT);
        $userId = $this->userCtx->insert($surname, $name, $patronomyc, $phone, $hash, $role);

        if ($userId > 0) {
            return ['success' => true, 'message' => 'Пользователь создан'];
        }

        return ['success' => false, 'message' => 'Ошибка создания пользователя'];
    }

    public function updateUserAdmin($id, $surname, $name, $patronomyc, $role, $new_password = null)
    {
        $surname    = trim((string) $surname);
        $name       = trim((string) $name);
        $patronomyc = trim((string) $patronomyc);
        $role       = (string) $role;

        if (!in_array($role, ['client', 'courier', 'admin'], true)) {
            return ['success' => false, 'message' => 'Некорректная роль'];
        }

        if ($new_password !== null && trim((string) $new_password) !== '') {
            $hash = password_hash((string) $new_password, PASSWORD_DEFAULT);
            $ok   = $this->userCtx->updateAdminWithPassword((int) $id, $surname, $name, $patronomyc, $hash, $role);
        } else {
            $ok = $this->userCtx->updateAdmin((int) $id, $surname, $name, $patronomyc, $role);
        }

        if ($ok) {
            return ['success' => true, 'message' => 'Сохранено'];
        }

        return ['success' => false, 'message' => 'Ошибка обновления'];
    }

    public function deleteUser($id, $session_user_id)
    {
        if ((int) $id === (int) $session_user_id) {
            return ['success' => false, 'message' => 'Нельзя удалить себя', 'code' => 'own'];
        }

        if ($this->userCtx->deleteById((int) $id)) {
            return ['success' => true, 'message' => 'Удалено'];
        }

        return ['success' => false, 'message' => 'Ошибка удаления'];
    }
}
