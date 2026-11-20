CREATE DATABASE IF NOT EXISTS php_data_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE php_data_management;

CREATE TABLE IF NOT EXISTS admins (
  admin_id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admins MODIFY password VARCHAR(255) NOT NULL;

CREATE TABLE IF NOT EXISTS students (
  student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT NULL,
  registration_date DATE NOT NULL,
  PRIMARY KEY (student_id),
  UNIQUE KEY uq_students_email (email),
  KEY idx_students_name (full_name),
  KEY idx_students_registration_date (registration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
  course_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_name VARCHAR(150) NOT NULL,
  course_code VARCHAR(50) NOT NULL,
  duration VARCHAR(100) NULL,
  course_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  description TEXT NULL,
  PRIMARY KEY (course_id),
  UNIQUE KEY uq_courses_code (course_code),
  KEY idx_courses_name (course_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instructors (
  instructor_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NULL,
  specialization VARCHAR(150) NULL,
  joining_date DATE NULL,
  PRIMARY KEY (instructor_id),
  UNIQUE KEY uq_instructors_email (email),
  KEY idx_instructors_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
  enrollment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  enrollment_date DATE NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  remarks TEXT NULL,
  PRIMARY KEY (enrollment_id),
  KEY idx_enrollments_student (student_id),
  KEY idx_enrollments_course (course_id),
  KEY idx_enrollments_status (status),
  KEY idx_enrollments_date (enrollment_date),
  CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id)
    REFERENCES courses (course_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  payment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  payment_status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (payment_id),
  KEY idx_payments_student (student_id),
  KEY idx_payments_status (payment_status),
  KEY idx_payments_method (payment_method),
  KEY idx_payments_date (payment_date),
  CONSTRAINT fk_payments_student FOREIGN KEY (student_id)
    REFERENCES students (student_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
