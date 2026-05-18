<?php

require_once __DIR__ . '/../Contexts/UserContext.php';

class AuthController
{
    private UserContext $userCtx;

    public function __construct(UserContext $userCtx)
    {
        $this->userCtx = $userCtx;
    }

    public function attemptLogin($phone, $password)
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if ($phone === '' || $password === '') {
            return [
                'success'  => false,
                'message'  => 'Пожалуйста, заполните все поля.',
                'user_id'  => null,
                'role'     => null,
            ];
        }

        $user = $this->userCtx->findByPhone($phone);

        if (!$user) {
            return [
                'success'  => false,
                'message'  => 'Пользователь с таким номером телефона не найден.',
                'user_id'  => null,
                'role'     => null,
            ];
        }

        if (!password_verify((string) $password, (string) $user['Password'])) {
            return [
                'success'  => false,
                'message'  => 'Неверный пароль.',
                'user_id'  => null,
                'role'     => null,
            ];
        }

        $role = (string) $user['Role'];
        if (!in_array($role, ['client', 'courier', 'admin'], true)) {
            return [
                'success'  => false,
                'message'  => 'Неизвестная роль пользователя.',
                'user_id'  => null,
                'role'     => null,
            ];
        }

        return [
            'success'  => true,
            'message'  => '',
            'user_id'  => (int) $user['Id'],
            'role'     => $role,
        ];
    }

    public function registerClient($surname, $name, $patronomyc, $phone, $password, $confirm_password)
    {
        $errors     = [];
        $surname    = trim((string) $surname);
        $name       = trim((string) $name);
        $patronomyc = trim((string) $patronomyc);
        $phone      = preg_replace('/\D+/', '', (string) $phone);

        if ($surname === '' || $name === '' || $patronomyc === '') {
            $errors[] = 'Пожалуйста, заполните все поля ФИО.';
        }
        if (strlen($phone) < 10) {
            $errors[] = 'Введите корректный номер телефона.';
        }
        if (strlen((string) $password) < 6) {
            $errors[] = 'Пароль должен быть не менее 6 символов.';
        }
        if ((string) $password !== (string) $confirm_password) {
            $errors[] = 'Пароли не совпадают.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($this->userCtx->phoneExists($phone)) {
            return ['success' => false, 'errors' => ['Пользователь с таким номером телефона уже зарегистрирован.']];
        }

        $hash   = password_hash((string) $password, PASSWORD_DEFAULT);
        $userId = $this->userCtx->insert($surname, $name, $patronomyc, $phone, $hash, 'client');

        if ($userId > 0) {
            return ['success' => true, 'errors' => [], 'user_id' => $userId];
        }

        return ['success' => false, 'errors' => ['Ошибка регистрации.']];
    }
}
