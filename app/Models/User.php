<?php

class User
{
    public const ROLE_CLIENT = 'client';
    public const ROLE_COURIER = 'courier';
    public const ROLE_ADMIN = 'admin';

    public $id;
    public $surname;
    public $name;
    public $patronymic;
    public $phone;
    public $password_hash;
    public $role;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->surname = $data['surname'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->patronymic = $data['patronymic'] ?? null;
        $this->phone = $data['phone'] ?? '';
        $this->password_hash = $data['password_hash'] ?? '';
        $this->role = $data['role'] ?? self::ROLE_CLIENT;
    }
}
