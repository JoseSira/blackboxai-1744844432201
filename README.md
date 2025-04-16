
Built by https://www.blackbox.ai

---

```markdown
# Training Platform

## Project Overview
Training Platform is a web-based application designed for fitness training and education. It allows users to register, log in, and access a variety of training videos and courses on fitness topics. The platform also features a system for redeeming subscription keys, tracking user progress, and managing video access.

## Installation

To install and run the Training Platform locally, follow these steps:

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd training-platform
   ```

2. **Set up a web server**:
   Ensure you have a local server environment set up, such as XAMPP, WAMP, or MAMP. Alternatively, you can use a PHP server.

3. **Create a database**:
   - Create a MySQL database named `video_platform`.
   - Import the necessary tables for users, courses, videos, and subscription keys from an SQL script (not provided).

4. **Update configuration**:
   Configure database connection settings in the `config.php` file:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'video_platform');
   ```

5. **Run the application**:
   Launch your web server and navigate to `http://localhost/path/to/index.php` in your web browser.

## Usage
1. **Register**: Create a new account using the registration form.
2. **Login**: Access your account using your username or email and password.
3. **Browse Courses**: Explore available training courses and access videos.
4. **Redeem Keys**: Use subscription keys to unlock additional content.
5. **Track Progress**: Mark videos as completed and track your progress.

## Features
- User registration and login system
- Access to high-quality training videos
- Subscription key redemption system
- Progress tracking for each video
- Responsive design for access on various devices
- Admin role (functionality not fully implemented in this demo)

## Dependencies
This project uses the following dependencies:
- **PHP**: Server-side scripting language used in this project.
- **MySQLi**: MySQL extension for database interactions.
- **Tailwind CSS**: Utility-first CSS framework for styling.
- **Font Awesome**: Icon library for easy use in the web interface.
- **Intl-Tel-Input**: Telephone input library for international phone number formatting.

## Project Structure
```
.
├── config.php            # Database configuration settings
├── functions.php         # Helper functions for authentication, sanitization, and video access
├── index.php             # Landing page for the application
├── login.php             # User login page
├── logout.php            # User logout script
├── register.php          # User registration page
├── dashboard.php         # User dashboard displaying courses
├── course.php            # Course detail page with videos
├── video.php             # Video playback page
├── mark_video_completed.php  # Endpoint to mark videos as completed
├── check_video_status.php   # Endpoint to check video completion status
└── styles                # Directory for additional styles if needed
```

## Conclusion
The Training Platform provides a comprehensive solution for users looking to enhance their fitness regimen through professional video content. With its user-friendly interface and robust backend, it allows for a seamless learning experience in the fitness domain.

For any contributions or issues, feel free to reach out or create a pull request!
```