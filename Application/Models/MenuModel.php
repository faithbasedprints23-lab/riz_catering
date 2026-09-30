<?php
namespace Application\Models;

use PDO;

class MenuModel {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    /**
     * Gets all public available menu items joined with categories
     */
    public function getActiveMenuItems() {
        $sql = "SELECT 
                    m.id, 
                    m.category_id, 
                    c.name AS category_name, 
                    m.name, 
                    m.description, 
                    m.price_per_head, 
                    m.is_available 
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.is_available = 1
                ORDER BY c.name ASC, m.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all menu items for owner/admin management views
     */
    public function getAllMenuItemsAdmin() {
        $sql = "SELECT 
                    m.id, 
                    m.category_id, 
                    c.name AS category_name, 
                    m.name, 
                    m.description, 
                    m.price_per_head, 
                    m.is_available 
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                ORDER BY m.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adds a new menu item
     */
    public function addMenuItem($data) {
        $sql = "INSERT INTO menu_items (
                    category_id, 
                    name, 
                    description, 
                    price_per_head, 
                    is_available
                ) VALUES (
                    :category_id, 
                    :name, 
                    :description, 
                    :price_per_head, 
                    :is_available
                )";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':category_id'    => $data['category_id'],
            ':name'           => $data['name'],
            ':description'    => $data['description'] ?? '',
            ':price_per_head' => $data['price_per_head'],
            ':is_available'   => isset($data['is_available']) ? $data['is_available'] : 1
        ]);
    }

    /**
     * Updates an existing menu item
     */
    public function updateMenuItem($id, $data) {
        $sql = "UPDATE menu_items 
                SET category_id = :category_id, 
                    name = :name, 
                    description = :description, 
                    price_per_head = :price_per_head, 
                    is_available = :is_available 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':category_id'    => $data['category_id'],
            ':name'           => $data['name'],
            ':description'    => $data['description'] ?? '',
            ':price_per_head' => $data['price_per_head'],
            ':is_available'   => $data['is_available'],
            ':id'             => $id
        ]);
    }

    /**
     * Toggles availability status of a menu item
     */
    public function toggleAvailability($id, $status) {
        $sql = "UPDATE menu_items SET is_available = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }

    /**
     * Deletes a menu item
     */
    public function deleteMenuItem($id) {
        $sql = "DELETE FROM menu_items WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}