# Admin Dashboard Project

This project is an admin dashboard for managing users and subjects in a school system. It allows administrators to add, delete, and manage users with different roles (Student, Teacher, Admin) and subjects.

## Project Structure

```
admin-dashboard
├── src
│   ├── controllers
│   │   ├── UserController.php
│   │   └── SubjectController.php
│   ├── models
│   │   ├── User.php
│   │   └── Subject.php
│   ├── views
│   │   ├── dashboard.php
│   │   ├── users.php
│   │   ├── subjects.php
│   │   └── partials
│   │       ├── header.php
│   │       └── footer.php
│   ├── config
│   │   └── database.php
│   └── types
│       └── index.php
├── public
│   ├── index.php
│   ├── login.php
│   └── style.css
├── README.md
└── .gitignore
```

## Features

- **User Management**: Admin can add, delete, and list users.
- **Subject Management**: Admin can add, delete, and list subjects.
- **User Roles**: Users can have different roles: Student, Teacher, or Admin.
- **Dashboard**: A central dashboard to view and manage users and subjects.

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```
   cd admin-dashboard
   ```
3. Set up the database connection in `src/config/database.php`.
4. Run the application using a local server (e.g., XAMPP, WAMP).

## Usage

- Access the admin dashboard by navigating to `public/index.php`.
- Use the navigation links to manage users and subjects.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any enhancements or bug fixes.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.