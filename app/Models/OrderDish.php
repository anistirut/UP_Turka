<?php

class OrderDish
{
    public $id;
    public $id_order;
    public $id_dish;
    public $quantity;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->id_order = $data['id_order'] ?? 0;
        $this->id_dish = $data['id_dish'] ?? 0;
        $this->quantity = $data['quantity'] ?? 1;
    }
}
