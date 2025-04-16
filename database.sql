-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS video_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE video_platform;

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de cursos
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de episodios/videos
CREATE TABLE episodes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    episode_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    UNIQUE KEY unique_episode (course_id, episode_number)
) ENGINE=InnoDB;

-- Tabla de keys de suscripción
CREATE TABLE subscription_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_code VARCHAR(255) NOT NULL UNIQUE,
    course_id INT NOT NULL,
    allowed_videos TEXT NOT NULL, -- JSON array de IDs de videos permitidos
    is_used BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tabla de acceso a videos
CREATE TABLE user_video_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    key_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (video_id) REFERENCES episodes(id),
    FOREIGN KEY (key_id) REFERENCES subscription_keys(id),
    UNIQUE KEY unique_access (user_id, video_id)
) ENGINE=InnoDB;

-- Insertar usuario administrador por defecto
-- Usuario: admin@admin.com
-- Contraseña: admin123
INSERT INTO users (username, email, phone, country, password, role) 
VALUES (
    'admin', 
    'admin@admin.com', 
    '1234567890', 
    'Admin Country', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- Crear índices para optimizar búsquedas
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_episodes_course ON episodes(course_id);
CREATE INDEX idx_subscription_keys_course ON subscription_keys(course_id);
CREATE INDEX idx_user_video_access_user ON user_video_access(user_id);
CREATE INDEX idx_user_video_access_video ON user_video_access(video_id);

-- Datos de ejemplo para pruebas
INSERT INTO courses (title, description, cover_image) VALUES
('Entrenamiento Básico', 'Curso de entrenamiento para principiantes', 'https://images.pexels.com/photos/841130/pexels-photo-841130.jpeg'),
('Entrenamiento Avanzado', 'Curso de entrenamiento para usuarios experimentados', 'https://images.pexels.com/photos/1552242/pexels-photo-1552242.jpeg');

INSERT INTO episodes (course_id, episode_number, title, description, video_url) VALUES
(1, 1, 'Introducción al Entrenamiento', 'Video introductorio sobre entrenamiento básico', 'https://example.com/video1.mp4'),
(1, 2, 'Calentamiento', 'Técnicas de calentamiento', 'https://example.com/video2.mp4'),
(2, 1, 'Técnicas Avanzadas', 'Introducción a técnicas avanzadas', 'https://example.com/video3.mp4'),
(2, 2, 'Entrenamiento de Alta Intensidad', 'Rutinas de alta intensidad', 'https://example.com/video4.mp4');
