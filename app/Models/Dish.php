<?php

class Dish
{
    public $id;
    public $name;
    public $compound;
    public $price;
    public $img;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->compound = $data['compound'] ?? '';
        $this->price = $data['price'] ?? '';
        $this->img = $data['img'] ?? '';
    }
}
