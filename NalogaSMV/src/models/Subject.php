<?php
// filepath: admin-dashboard/src/models/Subject.php

class Subject {
    private $id;
    private $name;
    private $description;

    public function __construct($id, $name, $description) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public static function all() {
        // Logic to retrieve all subjects from the database
    }

    public static function find($id) {
        // Logic to find a subject by its ID
    }

    public function save() {
        // Logic to save the subject to the database
    }

    public function delete() {
        // Logic to delete the subject from the database
    }
}
?>