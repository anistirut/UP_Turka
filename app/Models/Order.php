<?php

class Order
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PROGRESS = 'progress';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERY = 'delivery';
    public const STATUS_DELIVERED = 'delivered';

    public $id;
    public $id_client;
    public $id_courier;
    public $total_sum;
    public $address;
    public $status;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->id_client = $data['id_client'] ?? 0;
        $this->id_courier = $data['id_courier'] ?? 0;
        $this->total_sum = $data['total_sum'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_ACCEPTED;
    }
}
