-- ============================================================
-- Bangladesh Railway Management System
-- MySQL Schema — For XAMPP/phpMyAdmin
-- ============================================================
-- Run this in phpMyAdmin or MySQL CLI:
--   source /path/to/db/schema_mysql.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS railway_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE railway_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Booking_Audit, Booking, Coach, Train,
    Maintenance_Audit, Maintenance, Driver, Platform_Staff,
    Employee_Audit, Employee, Technician_Audit, Technician,
    Route_Audit, Route, Schedule_Audit, Schedule,
    Payment_Audit, Payment, Passenger_Audit, Passenger, UserDetails;
SET FOREIGN_KEY_CHECKS = 1;

-- ====================================
-- CORE TABLES
-- ====================================

CREATE TABLE UserDetails (
    user_id        INT PRIMARY KEY AUTO_INCREMENT,
    username       VARCHAR(30) UNIQUE NOT NULL,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('admin','staff','viewer') DEFAULT 'viewer',
    user_activated TINYINT DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Passenger (
    passenger_id   VARCHAR(8)  PRIMARY KEY,
    first_name     VARCHAR(50) NOT NULL,
    last_name      VARCHAR(50),
    gender         ENUM('Male','Female','Other'),
    contact_number VARCHAR(15) NOT NULL,
    date_of_birth  DATE NOT NULL
);

CREATE TABLE Payment (
    payment_id   VARCHAR(8)   PRIMARY KEY,
    amount       DECIMAL(10,2) NOT NULL,
    method       VARCHAR(20)  NOT NULL,
    status       VARCHAR(20)  DEFAULT 'PAID',
    payment_date TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Schedule (
    schedule_id    VARCHAR(8)  PRIMARY KEY,
    departure_date DATE        NOT NULL,
    departure_time VARCHAR(10),
    arrival_time   VARCHAR(10)
);

CREATE TABLE Route (
    route_id           VARCHAR(8)  PRIMARY KEY,
    origin             VARCHAR(50) NOT NULL,
    destination        VARCHAR(50) NOT NULL,
    distance_km        DECIMAL(8,2) NOT NULL,
    estimated_duration VARCHAR(20)
);

CREATE TABLE Employee (
    emp_id         VARCHAR(8)   PRIMARY KEY,
    first_name     VARCHAR(50)  NOT NULL,
    last_name      VARCHAR(50),
    designation    VARCHAR(30)  NOT NULL,
    salary         DECIMAL(10,2) NOT NULL,
    contact_number VARCHAR(15)  NOT NULL,
    address        VARCHAR(100),
    hire_date      DATE         NOT NULL
);

CREATE TABLE Train (
    train_id       VARCHAR(8)  PRIMARY KEY,
    train_name     VARCHAR(50) NOT NULL,
    type           VARCHAR(20),
    route_id       VARCHAR(8),
    schedule_id    VARCHAR(8),
    emp_id         VARCHAR(8),
    maintenance_id VARCHAR(8),
    status         VARCHAR(20) DEFAULT 'ACTIVE',
    FOREIGN KEY (route_id)    REFERENCES Route(route_id)    ON DELETE SET NULL,
    FOREIGN KEY (schedule_id) REFERENCES Schedule(schedule_id) ON DELETE SET NULL,
    FOREIGN KEY (emp_id)      REFERENCES Employee(emp_id)   ON DELETE SET NULL
);

CREATE TABLE Coach (
    coach_id   VARCHAR(8)  PRIMARY KEY,
    train_id   VARCHAR(8),
    coach_type VARCHAR(20) NOT NULL,
    capacity   INT         NOT NULL,
    FOREIGN KEY (train_id) REFERENCES Train(train_id) ON DELETE CASCADE
);

CREATE TABLE Booking (
    booking_id   VARCHAR(8)   PRIMARY KEY,
    passenger_id VARCHAR(8),
    train_id     VARCHAR(8),
    coach_id     VARCHAR(8),
    seat_no      INT          NOT NULL,
    payment_id   VARCHAR(8),
    booking_time TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    travel_date  DATE         NOT NULL,
    FOREIGN KEY (passenger_id) REFERENCES Passenger(passenger_id) ON DELETE CASCADE,
    FOREIGN KEY (train_id)     REFERENCES Train(train_id)         ON DELETE CASCADE,
    FOREIGN KEY (coach_id)     REFERENCES Coach(coach_id)         ON DELETE CASCADE,
    FOREIGN KEY (payment_id)   REFERENCES Payment(payment_id)     ON DELETE SET NULL
);

CREATE TABLE Technician (
    tech_id          VARCHAR(8)  PRIMARY KEY,
    first_name       VARCHAR(50) NOT NULL,
    last_name        VARCHAR(50),
    specialization   VARCHAR(30),
    experience_years INT,
    contact          VARCHAR(15)
);

CREATE TABLE Maintenance (
    maintenance_id   VARCHAR(8)   PRIMARY KEY,
    train_id         VARCHAR(8),
    tech_id          VARCHAR(8),
    maintenance_type VARCHAR(30),
    maintenance_date DATE         NOT NULL,
    remarks          VARCHAR(200),
    FOREIGN KEY (train_id) REFERENCES Train(train_id)      ON DELETE CASCADE,
    FOREIGN KEY (tech_id)  REFERENCES Technician(tech_id)  ON DELETE SET NULL
);

CREATE TABLE Driver (
    license_no          VARCHAR(20) PRIMARY KEY,
    emp_id              VARCHAR(8),
    train_certification VARCHAR(50),
    license_expiry      DATE,
    FOREIGN KEY (emp_id) REFERENCES Employee(emp_id) ON DELETE CASCADE
);

CREATE TABLE Platform_Staff (
    badge_no          VARCHAR(8)  PRIMARY KEY,
    emp_id            VARCHAR(8),
    platform_assigned VARCHAR(10),
    shift             VARCHAR(20),
    FOREIGN KEY (emp_id) REFERENCES Employee(emp_id) ON DELETE CASCADE
);

-- ====================================
-- AUDIT TABLES (Data Provenance)
-- ====================================

CREATE TABLE Passenger_Audit (
    audit_id       INT AUTO_INCREMENT PRIMARY KEY,
    operation      VARCHAR(10) NOT NULL,
    passenger_id   VARCHAR(8),
    old_first_name VARCHAR(50), new_first_name VARCHAR(50),
    old_last_name  VARCHAR(50), new_last_name  VARCHAR(50),
    old_gender     VARCHAR(10), new_gender     VARCHAR(10),
    old_contact    VARCHAR(15), new_contact    VARCHAR(15),
    old_dob        DATE,        new_dob        DATE,
    performed_by   VARCHAR(100) DEFAULT (USER()),
    session_id     VARCHAR(100),
    ip_address     VARCHAR(50),
    operation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Payment_Audit (
    audit_id       INT AUTO_INCREMENT PRIMARY KEY,
    operation      VARCHAR(10) NOT NULL,
    payment_id     VARCHAR(8),
    old_amount     DECIMAL(10,2), new_amount DECIMAL(10,2),
    old_method     VARCHAR(20),   new_method VARCHAR(20),
    old_status     VARCHAR(20),   new_status VARCHAR(20),
    performed_by   VARCHAR(100) DEFAULT (USER()),
    session_id     VARCHAR(100),
    ip_address     VARCHAR(50),
    operation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Route_Audit (
    audit_id         INT AUTO_INCREMENT PRIMARY KEY,
    operation        VARCHAR(10) NOT NULL,
    route_id         VARCHAR(8),
    old_origin       VARCHAR(50), new_origin       VARCHAR(50),
    old_destination  VARCHAR(50), new_destination  VARCHAR(50),
    old_distance_km  DECIMAL(8,2),new_distance_km  DECIMAL(8,2),
    old_est_duration VARCHAR(20), new_est_duration VARCHAR(20),
    performed_by     VARCHAR(100) DEFAULT (USER()),
    session_id       VARCHAR(100),
    ip_address       VARCHAR(50),
    operation_time   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Schedule_Audit (
    audit_id       INT AUTO_INCREMENT PRIMARY KEY,
    operation      VARCHAR(10) NOT NULL,
    schedule_id    VARCHAR(8),
    old_dep_date   DATE,         new_dep_date DATE,
    old_dep_time   VARCHAR(10),  new_dep_time VARCHAR(10),
    old_arr_time   VARCHAR(10),  new_arr_time VARCHAR(10),
    performed_by   VARCHAR(100) DEFAULT (USER()),
    session_id     VARCHAR(100),
    ip_address     VARCHAR(50),
    operation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Employee_Audit (
    audit_id         INT AUTO_INCREMENT PRIMARY KEY,
    operation        VARCHAR(10) NOT NULL,
    emp_id           VARCHAR(8),
    old_first_name   VARCHAR(50),  new_first_name   VARCHAR(50),
    old_last_name    VARCHAR(50),  new_last_name    VARCHAR(50),
    old_designation  VARCHAR(30),  new_designation  VARCHAR(30),
    old_salary       DECIMAL(10,2),new_salary       DECIMAL(10,2),
    old_contact      VARCHAR(15),  new_contact      VARCHAR(15),
    old_address      VARCHAR(100), new_address      VARCHAR(100),
    old_hire_date    DATE,         new_hire_date    DATE,
    performed_by     VARCHAR(100) DEFAULT (USER()),
    session_id       VARCHAR(100),
    ip_address       VARCHAR(50),
    operation_time   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Train_Audit (
    audit_id         INT AUTO_INCREMENT PRIMARY KEY,
    operation        VARCHAR(10) NOT NULL,
    train_id         VARCHAR(8),
    old_train_name   VARCHAR(50), new_train_name   VARCHAR(50),
    old_type         VARCHAR(20), new_type         VARCHAR(20),
    old_route_id     VARCHAR(8),  new_route_id     VARCHAR(8),
    old_schedule_id  VARCHAR(8),  new_schedule_id  VARCHAR(8),
    old_status       VARCHAR(20), new_status       VARCHAR(20),
    performed_by     VARCHAR(100) DEFAULT (USER()),
    session_id       VARCHAR(100),
    ip_address       VARCHAR(50),
    operation_time   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Coach_Audit (
    audit_id        INT AUTO_INCREMENT PRIMARY KEY,
    operation       VARCHAR(10) NOT NULL,
    coach_id        VARCHAR(8),
    old_train_id    VARCHAR(8),  new_train_id   VARCHAR(8),
    old_coach_type  VARCHAR(20), new_coach_type VARCHAR(20),
    old_capacity    INT,         new_capacity   INT,
    performed_by    VARCHAR(100) DEFAULT (USER()),
    session_id      VARCHAR(100),
    ip_address      VARCHAR(50),
    operation_time  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Booking_Audit (
    audit_id          INT AUTO_INCREMENT PRIMARY KEY,
    operation         VARCHAR(10) NOT NULL,
    booking_id        VARCHAR(8),
    old_passenger_id  VARCHAR(8),  new_passenger_id VARCHAR(8),
    old_train_id      VARCHAR(8),  new_train_id     VARCHAR(8),
    old_coach_id      VARCHAR(8),  new_coach_id     VARCHAR(8),
    old_seat_no       INT,         new_seat_no      INT,
    old_payment_id    VARCHAR(8),  new_payment_id   VARCHAR(8),
    old_travel_date   DATE,        new_travel_date  DATE,
    performed_by      VARCHAR(100) DEFAULT (USER()),
    session_id        VARCHAR(100),
    ip_address        VARCHAR(50),
    operation_time    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Maintenance_Audit (
    audit_id               INT AUTO_INCREMENT PRIMARY KEY,
    operation              VARCHAR(10) NOT NULL,
    maintenance_id         VARCHAR(8),
    old_train_id           VARCHAR(8),   new_train_id           VARCHAR(8),
    old_tech_id            VARCHAR(8),   new_tech_id            VARCHAR(8),
    old_maintenance_type   VARCHAR(30),  new_maintenance_type   VARCHAR(30),
    old_maintenance_date   DATE,         new_maintenance_date   DATE,
    old_remarks            VARCHAR(200), new_remarks            VARCHAR(200),
    performed_by           VARCHAR(100) DEFAULT (USER()),
    session_id             VARCHAR(100),
    ip_address             VARCHAR(50),
    operation_time         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


COMMIT;
