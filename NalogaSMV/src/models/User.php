<?php
// filepath: admin-dashboard/src/models/User.php

class User {
    private $id;
    private $name;
    private $email;
    private $status; // Possible values: Student, Teacher, Admin

    public function __construct($id, $name, $email, $status) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->setStatus($status);
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $validStatuses = ['Student', 'Teacher', 'Admin'];
        if (in_array($status, $validStatuses)) {
            $this->status = $status;
        } else {
            throw new InvalidArgumentException("Invalid status provided.");
        }
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
        ];
    }
}
?>