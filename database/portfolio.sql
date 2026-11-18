-- Portfolio database: structure and sample data
CREATE DATABASE IF NOT EXISTS portfolio_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio_db;

-- Administrator accounts (passwords are stored hashed, never as plain text)
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Records failed logins to block repeated attempts
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  username VARCHAR(50) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;

-- Personal information (always a single row)
CREATE TABLE IF NOT EXISTS profile (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  headline VARCHAR(150) NOT NULL,
  introduction TEXT NOT NULL,
  biography TEXT NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  location VARCHAR(150) NOT NULL,
  school VARCHAR(150) DEFAULT NULL,
  github_url VARCHAR(255) DEFAULT NULL,
  facebook_url VARCHAR(255) DEFAULT NULL,
  linkedin_url VARCHAR(255) DEFAULT NULL,
  instagram_url VARCHAR(255) DEFAULT NULL,
  profile_image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- Website settings (always a single row)
CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_title VARCHAR(150) NOT NULL,
  logo_text VARCHAR(10) NOT NULL,
  logo_image VARCHAR(255) DEFAULT NULL,
  footer_text VARCHAR(255) NOT NULL,
  default_theme ENUM('light','dark') NOT NULL DEFAULT 'light'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  category VARCHAR(50) NOT NULL,
  level TINYINT UNSIGNED NOT NULL DEFAULT 50,
  note VARCHAR(150) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  category VARCHAR(30) NOT NULL,
  project_type VARCHAR(60) NOT NULL,
  description TEXT NOT NULL,
  tags VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS education (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  degree VARCHAR(150) NOT NULL,
  school VARCHAR(150) NOT NULL,
  period_label VARCHAR(50) NOT NULL,
  description TEXT DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS experience (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  organization VARCHAR(150) NOT NULL,
  period_label VARCHAR(50) NOT NULL,
  description TEXT DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS achievements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  issuer VARCHAR(150) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===== Sample data (from the midterm portfolio) =====

INSERT INTO profile (full_name, headline, introduction, biography, email, location, school,
                     github_url, facebook_url, linkedin_url, instagram_url, profile_image)
VALUES (
  'Jezriel D. Alabastro',
  'BSINT Student, Java Programmer, Digital Animator',
  'A 20-year-old BSINT student from Basay, Negros Oriental. I build desktop applications with Java and C++, create 2D animations with Adobe Animate, and I am always looking for a new problem to solve.',
  'I grew up curious about how things work, and that curiosity pulled me into information technology. Today I am a 3rd year BSINT student. I spend most of my study time writing Java and C++ programs, designing small desktop systems, and experimenting with 2D animation in Adobe Animate.',
  'jezriel.alabastro@example.com',
  'Basay, Negros Oriental, Philippines',
  'Negros Oriental State University',
  'https://github.com/iCantSeeColor',
  'https://www.facebook.com/',
  'https://www.linkedin.com/',
  'https://www.instagram.com/',
  'images/jez.jpg'
);

INSERT INTO settings (site_title, logo_text, footer_text, default_theme)
VALUES ('Jezriel D. Alabastro | Portfolio', 'JA', 'Jezriel D. Alabastro. All rights reserved.', 'light');

INSERT INTO skills (name, category, level, note) VALUES
('Java', 'Programming', 85, 'OOP, Swing, JavaFX'),
('C++', 'Programming', 75, 'Console apps, File handling'),
('HTML5', 'Web', 80, 'Semantic structure'),
('CSS3', 'Web', 78, 'Flexbox, Grid, Responsive'),
('JavaScript', 'Web', 70, 'DOM, Events, Validation'),
('MySQL', 'Database', 72, 'Queries, CRUD, Joins'),
('Adobe Animate', 'Creative', 74, '2D animation, Motion tween'),
('Networking', 'Networking', 68, 'Cisco Networking 1 certified'),
('Git & GitHub', 'Tools', 65, 'Version control basics'),
('Problem Solving', 'Soft Skills', 82, 'Logic, Debugging, Chess thinking');

INSERT INTO projects (title, category, project_type, description, tags, image) VALUES
('NORSU Student Skill Hub', 'java', 'Desktop Application',
 'A Java desktop system where NORSU students can register, list their skills, and browse other students profiles. It uses a MySQL database for accounts and skill records, with a simple Swing interface.',
 'Java,Swing,MySQL,JDBC', 'images/norsu.png'),
('Java Currency Converter', 'java', 'Desktop Application',
 'A desktop currency converter built in Java using NetBeans. It converts between PHP, USD, EUR, JPY and other currencies using preset exchange rates.',
 'Java,NetBeans,Swing,Event Handling', 'images/currency.png'),
('Calculator & Menu Programs (C++)', 'cpp', 'Console Program',
 'A set of small C++ practice programs, including a menu-driven calculator, a number guessing game and a grade average solver.',
 'C++,Functions,Logic', 'images/cpp.png'),
('Animated Short Scenes', 'animation', '2D Animation',
 'Short 2D animated scenes created in Adobe Animate, covering character drawing, frame-by-frame animation, motion tweens, symbol reuse and scene transitions.',
 'Adobe Animate,Motion Tween,Storyboard', 'images/animate.png'),
('Personal Portfolio Website', 'web', 'Website',
 'A responsive portfolio website with sidebar navigation, dark and light mode, and form validation. Being upgraded into a dynamic PHP and MySQL website.',
 'HTML5,CSS3,JavaScript,PHP,MySQL', 'images/portfolio.png');

INSERT INTO education (degree, school, period_label, description) VALUES
('BS Information Technology (BSINT) - 3rd Year', 'Negros Oriental State University', 'Present',
 'Focusing on programming fundamentals, object-oriented design, database management, networking and systems development.'),
('Senior High School', 'Basay, Negros Oriental', 'Completed',
 'Finished secondary education with an interest in computers and technology.'),
('Self-Directed Learning', 'Online tutorials and personal practice', 'Ongoing',
 'Studying Java, C++, web development and animation through online resources and daily coding practice.');

INSERT INTO experience (title, organization, period_label, description) VALUES
('Programming Practice', 'Self-directed', 'Ongoing',
 'Building small Java and C++ programs outside of class to strengthen logic, debugging and problem-solving skills.'),
('Group Projects', 'Negros Oriental State University', 'Ongoing',
 'Working with classmates on system development tasks: dividing modules, testing features and preparing documentation.');

INSERT INTO achievements (title, issuer, description) VALUES
('Cisco - Networking 1 Certificate', 'Cisco', 'Completed the Cisco Networking 1 course covering network fundamentals, OSI and TCP/IP models, IP addressing, subnetting, and basic router and switch configuration.'),
('Java Programming Training', NULL, 'Hands-on training covering classes and objects, inheritance, interfaces, exception handling, collections and Swing GUIs.'),
('C++ Programming Fundamentals', NULL, 'Studied C++ syntax, control structures, functions, arrays, pointers, structures and file handling.'),
('Database Management (MySQL)', NULL, 'Training in relational database design, SQL queries, joins, and connecting Java applications to MySQL through JDBC.');