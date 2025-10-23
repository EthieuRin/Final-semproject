<?php
class Database {
    private $db_path;
    private $conn;

    public function __construct() {
        $this->db_path = __DIR__ . '/../eventzilla.db';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("sqlite:" . $this->db_path);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Enable foreign key support
            $this->conn->exec("PRAGMA foreign_keys = ON");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
