<?php
namespace Application\Models;

use PDO;
use Exception;

class OrderModel {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Retrieves all customer orders aligned with current database schema
     */
    public function getAllOrders() {
        $sql = "SELECT 
                    id, 
                    customer_name, 
                    contact_number, 
                    event_date, 
                    guest_count, 
                    package_id, 
                    total_amount, 
                    payment_status, 
                    order_status, 
                    created_at 
                FROM orders 
                ORDER BY event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets a single order by ID
     */
    public function getOrderById($id) {
        $sql = "SELECT 
                    id, 
                    customer_name, 
                    contact_number, 
                    event_date, 
                    guest_count, 
                    package_id, 
                    total_amount, 
                    payment_status, 
                    order_status, 
                    created_at 
                FROM orders 
                WHERE id = :id 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new customer reservation/order into the database
     */
    public function createOrder($data) {
        $sql = "INSERT INTO orders (
                    customer_name, 
                    contact_number, 
                    event_date, 
                    guest_count, 
                    package_id, 
                    total_amount, 
                    payment_status, 
                    order_status
                ) VALUES (
                    :customer_name, 
                    :contact_number, 
                    :event_date, 
                    :guest_count, 
                    :package_id, 
                    :total_amount, 
                    :payment_status, 
                    :order_status
                )";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':customer_name'  => $data['customer_name'],
            ':contact_number' => $data['contact_number'] ?? '',
            ':event_date'     => $data['event_date'],
            ':guest_count'    => $data['guest_count'],
            ':package_id'     => !empty($data['package_id']) ? $data['package_id'] : null,
            ':total_amount'   => $data['total_amount'],
            ':payment_status' => $data['payment_status'] ?? 'pending',
            ':order_status'   => $data['order_status'] ?? 'confirmed'
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Updates payment status and overall order status
     */
    public function updateOrderStatus($orderId, $orderStatus, $paymentStatus) {
        $sql = "UPDATE orders 
                SET order_status = :order_status, 
                    payment_status = :payment_status 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':order_status'   => $orderStatus,
            ':payment_status' => $paymentStatus,
            ':id'             => $orderId
        ]);
    }

    /**
     * Deletes an order by ID
     */
    public function deleteOrder($id) {
        $sql = "DELETE FROM orders WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}