<?php
// src/types/index.php

interface UserType {
    const STUDENT = 'Student';
    const TEACHER = 'Teacher';
    const ADMIN = 'Admin';
}

interface SubjectType {
    const MATH = 'Mathematics';
    const SCIENCE = 'Science';
    const HISTORY = 'History';
    const LANGUAGE = 'Language';
}

class UserStatus {
    public static function getStatuses() {
        return [
            UserType::STUDENT,
            UserType::TEACHER,
            UserType::ADMIN,
        ];
    }
}

class SubjectStatus {
    public static function getSubjects() {
        return [
            SubjectType::MATH,
            SubjectType::SCIENCE,
            SubjectType::HISTORY,
            SubjectType::LANGUAGE,
        ];
    }
}
?>