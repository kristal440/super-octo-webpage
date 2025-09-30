<?php
// filepath: admin-dashboard/src/controllers/SubjectController.php

class SubjectController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addSubject($name, $description) {
        $stmt = $this->conn->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        return $this->conn->lastInsertId();
    }

    public function deleteSubject($id) {
        $stmt = $this->conn->prepare("DELETE FROM subjects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function listSubjects() {
        $stmt = $this->conn->query("SELECT * FROM subjects");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Simple HTML output for demonstration
        echo "<h2>Predmeti</h2><ul>";
        foreach ($subjects as $subject) {
            echo "<li>{$subject['name']} - {$subject['description']} <a href='?page=subjects&delete={$subject['id']}'>Izbriši</a></li>";
        }
        echo "</ul>";
    }
}
?>