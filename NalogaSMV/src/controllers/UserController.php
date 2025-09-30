<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $users = [];

    public function addUser($name, $email, $status) {
        $id = count($this->users) + 1; // Simple ID generation
        $this->users[] = new User($id, $name, $email, $status);
        return "User added successfully.";
    }

    public function deleteUser($id) {
        foreach ($this->users as $key => $user) {
            if ($user->getId() == $id) {
                unset($this->users[$key]);
                return "User deleted successfully.";
            }
        }
        return "User not found.";
    }

    public function listUsers() {
        return $this->users;
    }
}
?>