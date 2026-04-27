-- ============================================================
-- DATA PROVENANCE TRIGGERS — MySQL Version
-- Bangladesh Railway Management System
-- ============================================================
-- MySQL requires DELIMITER change for trigger blocks.
-- Run in phpMyAdmin (SQL tab) or MySQL CLI.
-- ============================================================

USE railway_db;

DROP TRIGGER IF EXISTS trg_passenger_insert;
DROP TRIGGER IF EXISTS trg_passenger_update;
DROP TRIGGER IF EXISTS trg_passenger_delete;
DROP TRIGGER IF EXISTS trg_payment_insert;
DROP TRIGGER IF EXISTS trg_payment_update;
DROP TRIGGER IF EXISTS trg_payment_delete;
DROP TRIGGER IF EXISTS trg_route_insert;
DROP TRIGGER IF EXISTS trg_route_update;
DROP TRIGGER IF EXISTS trg_route_delete;
DROP TRIGGER IF EXISTS trg_schedule_insert;
DROP TRIGGER IF EXISTS trg_schedule_update;
DROP TRIGGER IF EXISTS trg_schedule_delete;
DROP TRIGGER IF EXISTS trg_employee_insert;
DROP TRIGGER IF EXISTS trg_employee_update;
DROP TRIGGER IF EXISTS trg_employee_delete;
DROP TRIGGER IF EXISTS trg_train_insert;
DROP TRIGGER IF EXISTS trg_train_update;
DROP TRIGGER IF EXISTS trg_train_delete;
DROP TRIGGER IF EXISTS trg_coach_insert;
DROP TRIGGER IF EXISTS trg_coach_update;
DROP TRIGGER IF EXISTS trg_coach_delete;
DROP TRIGGER IF EXISTS trg_booking_insert;
DROP TRIGGER IF EXISTS trg_booking_update;
DROP TRIGGER IF EXISTS trg_booking_delete;
DROP TRIGGER IF EXISTS trg_maintenance_insert;
DROP TRIGGER IF EXISTS trg_maintenance_update;
DROP TRIGGER IF EXISTS trg_maintenance_delete;

DELIMITER //

-- ==================== PASSENGER TRIGGERS ====================
CREATE TRIGGER trg_passenger_insert
AFTER INSERT ON Passenger FOR EACH ROW
BEGIN
    INSERT INTO Passenger_Audit
        (operation, passenger_id, new_first_name, new_last_name, new_gender, new_contact, new_dob, performed_by)
    VALUES
        ('INSERT', NEW.passenger_id, NEW.first_name, NEW.last_name, NEW.gender, NEW.contact_number, NEW.date_of_birth, USER());
END //

CREATE TRIGGER trg_passenger_update
AFTER UPDATE ON Passenger FOR EACH ROW
BEGIN
    INSERT INTO Passenger_Audit
        (operation, passenger_id,
         old_first_name, new_first_name, old_last_name, new_last_name,
         old_gender, new_gender, old_contact, new_contact, old_dob, new_dob, performed_by)
    VALUES
        ('UPDATE', NEW.passenger_id,
         OLD.first_name, NEW.first_name, OLD.last_name, NEW.last_name,
         OLD.gender, NEW.gender, OLD.contact_number, NEW.contact_number,
         OLD.date_of_birth, NEW.date_of_birth, USER());
END //

CREATE TRIGGER trg_passenger_delete
AFTER DELETE ON Passenger FOR EACH ROW
BEGIN
    INSERT INTO Passenger_Audit
        (operation, passenger_id, old_first_name, old_last_name, old_gender, old_contact, old_dob, performed_by)
    VALUES
        ('DELETE', OLD.passenger_id, OLD.first_name, OLD.last_name, OLD.gender, OLD.contact_number, OLD.date_of_birth, USER());
END //

-- ==================== PAYMENT TRIGGERS ====================
CREATE TRIGGER trg_payment_insert
AFTER INSERT ON Payment FOR EACH ROW
BEGIN
    INSERT INTO Payment_Audit (operation, payment_id, new_amount, new_method, new_status, performed_by)
    VALUES ('INSERT', NEW.payment_id, NEW.amount, NEW.method, NEW.status, USER());
END //

CREATE TRIGGER trg_payment_update
AFTER UPDATE ON Payment FOR EACH ROW
BEGIN
    INSERT INTO Payment_Audit
        (operation, payment_id, old_amount, new_amount, old_method, new_method, old_status, new_status, performed_by)
    VALUES
        ('UPDATE', NEW.payment_id, OLD.amount, NEW.amount, OLD.method, NEW.method, OLD.status, NEW.status, USER());
END //

CREATE TRIGGER trg_payment_delete
AFTER DELETE ON Payment FOR EACH ROW
BEGIN
    INSERT INTO Payment_Audit (operation, payment_id, old_amount, old_method, old_status, performed_by)
    VALUES ('DELETE', OLD.payment_id, OLD.amount, OLD.method, OLD.status, USER());
END //

-- ==================== ROUTE TRIGGERS ====================
CREATE TRIGGER trg_route_insert
AFTER INSERT ON Route FOR EACH ROW
BEGIN
    INSERT INTO Route_Audit (operation, route_id, new_origin, new_destination, new_distance_km, new_est_duration, performed_by)
    VALUES ('INSERT', NEW.route_id, NEW.origin, NEW.destination, NEW.distance_km, NEW.estimated_duration, USER());
END //

CREATE TRIGGER trg_route_update
AFTER UPDATE ON Route FOR EACH ROW
BEGIN
    INSERT INTO Route_Audit
        (operation, route_id, old_origin, new_origin, old_destination, new_destination,
         old_distance_km, new_distance_km, old_est_duration, new_est_duration, performed_by)
    VALUES
        ('UPDATE', NEW.route_id, OLD.origin, NEW.origin, OLD.destination, NEW.destination,
         OLD.distance_km, NEW.distance_km, OLD.estimated_duration, NEW.estimated_duration, USER());
END //

CREATE TRIGGER trg_route_delete
AFTER DELETE ON Route FOR EACH ROW
BEGIN
    INSERT INTO Route_Audit (operation, route_id, old_origin, old_destination, old_distance_km, old_est_duration, performed_by)
    VALUES ('DELETE', OLD.route_id, OLD.origin, OLD.destination, OLD.distance_km, OLD.estimated_duration, USER());
END //

-- ==================== SCHEDULE TRIGGERS ====================
CREATE TRIGGER trg_schedule_insert
AFTER INSERT ON Schedule FOR EACH ROW
BEGIN
    INSERT INTO Schedule_Audit (operation, schedule_id, new_dep_date, new_dep_time, new_arr_time, performed_by)
    VALUES ('INSERT', NEW.schedule_id, NEW.departure_date, NEW.departure_time, NEW.arrival_time, USER());
END //

CREATE TRIGGER trg_schedule_update
AFTER UPDATE ON Schedule FOR EACH ROW
BEGIN
    INSERT INTO Schedule_Audit
        (operation, schedule_id, old_dep_date, new_dep_date, old_dep_time, new_dep_time, old_arr_time, new_arr_time, performed_by)
    VALUES
        ('UPDATE', NEW.schedule_id, OLD.departure_date, NEW.departure_date,
         OLD.departure_time, NEW.departure_time, OLD.arrival_time, NEW.arrival_time, USER());
END //

CREATE TRIGGER trg_schedule_delete
AFTER DELETE ON Schedule FOR EACH ROW
BEGIN
    INSERT INTO Schedule_Audit (operation, schedule_id, old_dep_date, old_dep_time, old_arr_time, performed_by)
    VALUES ('DELETE', OLD.schedule_id, OLD.departure_date, OLD.departure_time, OLD.arrival_time, USER());
END //

-- ==================== EMPLOYEE TRIGGERS ====================
CREATE TRIGGER trg_employee_insert
AFTER INSERT ON Employee FOR EACH ROW
BEGIN
    INSERT INTO Employee_Audit
        (operation, emp_id, new_first_name, new_last_name, new_designation, new_salary, new_contact, new_address, new_hire_date, performed_by)
    VALUES
        ('INSERT', NEW.emp_id, NEW.first_name, NEW.last_name, NEW.designation, NEW.salary, NEW.contact_number, NEW.address, NEW.hire_date, USER());
END //

CREATE TRIGGER trg_employee_update
AFTER UPDATE ON Employee FOR EACH ROW
BEGIN
    INSERT INTO Employee_Audit
        (operation, emp_id,
         old_first_name, new_first_name, old_last_name, new_last_name,
         old_designation, new_designation, old_salary, new_salary,
         old_contact, new_contact, old_address, new_address,
         old_hire_date, new_hire_date, performed_by)
    VALUES
        ('UPDATE', NEW.emp_id,
         OLD.first_name, NEW.first_name, OLD.last_name, NEW.last_name,
         OLD.designation, NEW.designation, OLD.salary, NEW.salary,
         OLD.contact_number, NEW.contact_number, OLD.address, NEW.address,
         OLD.hire_date, NEW.hire_date, USER());
END //

CREATE TRIGGER trg_employee_delete
AFTER DELETE ON Employee FOR EACH ROW
BEGIN
    INSERT INTO Employee_Audit
        (operation, emp_id, old_first_name, old_last_name, old_designation, old_salary, old_contact, performed_by)
    VALUES
        ('DELETE', OLD.emp_id, OLD.first_name, OLD.last_name, OLD.designation, OLD.salary, OLD.contact_number, USER());
END //

-- ==================== TRAIN TRIGGERS ====================
CREATE TRIGGER trg_train_insert
AFTER INSERT ON Train FOR EACH ROW
BEGIN
    INSERT INTO Train_Audit (operation, train_id, new_train_name, new_type, new_route_id, new_schedule_id, new_status, performed_by)
    VALUES ('INSERT', NEW.train_id, NEW.train_name, NEW.type, NEW.route_id, NEW.schedule_id, NEW.status, USER());
END //

CREATE TRIGGER trg_train_update
AFTER UPDATE ON Train FOR EACH ROW
BEGIN
    INSERT INTO Train_Audit
        (operation, train_id, old_train_name, new_train_name, old_type, new_type,
         old_route_id, new_route_id, old_schedule_id, new_schedule_id, old_status, new_status, performed_by)
    VALUES
        ('UPDATE', NEW.train_id, OLD.train_name, NEW.train_name, OLD.type, NEW.type,
         OLD.route_id, NEW.route_id, OLD.schedule_id, NEW.schedule_id, OLD.status, NEW.status, USER());
END //

CREATE TRIGGER trg_train_delete
AFTER DELETE ON Train FOR EACH ROW
BEGIN
    INSERT INTO Train_Audit (operation, train_id, old_train_name, old_type, old_status, performed_by)
    VALUES ('DELETE', OLD.train_id, OLD.train_name, OLD.type, OLD.status, USER());
END //

-- ==================== COACH TRIGGERS ====================
CREATE TRIGGER trg_coach_insert
AFTER INSERT ON Coach FOR EACH ROW
BEGIN
    INSERT INTO Coach_Audit (operation, coach_id, new_train_id, new_coach_type, new_capacity, performed_by)
    VALUES ('INSERT', NEW.coach_id, NEW.train_id, NEW.coach_type, NEW.capacity, USER());
END //

CREATE TRIGGER trg_coach_update
AFTER UPDATE ON Coach FOR EACH ROW
BEGIN
    INSERT INTO Coach_Audit
        (operation, coach_id, old_train_id, new_train_id, old_coach_type, new_coach_type, old_capacity, new_capacity, performed_by)
    VALUES
        ('UPDATE', NEW.coach_id, OLD.train_id, NEW.train_id, OLD.coach_type, NEW.coach_type, OLD.capacity, NEW.capacity, USER());
END //

CREATE TRIGGER trg_coach_delete
AFTER DELETE ON Coach FOR EACH ROW
BEGIN
    INSERT INTO Coach_Audit (operation, coach_id, old_train_id, old_coach_type, old_capacity, performed_by)
    VALUES ('DELETE', OLD.coach_id, OLD.train_id, OLD.coach_type, OLD.capacity, USER());
END //

-- ==================== BOOKING TRIGGERS ====================
CREATE TRIGGER trg_booking_insert
AFTER INSERT ON Booking FOR EACH ROW
BEGIN
    INSERT INTO Booking_Audit
        (operation, booking_id, new_passenger_id, new_train_id, new_coach_id, new_seat_no, new_payment_id, new_travel_date, performed_by)
    VALUES
        ('INSERT', NEW.booking_id, NEW.passenger_id, NEW.train_id, NEW.coach_id, NEW.seat_no, NEW.payment_id, NEW.travel_date, USER());
END //

CREATE TRIGGER trg_booking_update
AFTER UPDATE ON Booking FOR EACH ROW
BEGIN
    INSERT INTO Booking_Audit
        (operation, booking_id,
         old_passenger_id, new_passenger_id, old_train_id, new_train_id,
         old_coach_id, new_coach_id, old_seat_no, new_seat_no,
         old_payment_id, new_payment_id, old_travel_date, new_travel_date, performed_by)
    VALUES
        ('UPDATE', NEW.booking_id,
         OLD.passenger_id, NEW.passenger_id, OLD.train_id, NEW.train_id,
         OLD.coach_id, NEW.coach_id, OLD.seat_no, NEW.seat_no,
         OLD.payment_id, NEW.payment_id, OLD.travel_date, NEW.travel_date, USER());
END //

CREATE TRIGGER trg_booking_delete
AFTER DELETE ON Booking FOR EACH ROW
BEGIN
    INSERT INTO Booking_Audit
        (operation, booking_id, old_passenger_id, old_train_id, old_coach_id, old_seat_no, old_travel_date, performed_by)
    VALUES
        ('DELETE', OLD.booking_id, OLD.passenger_id, OLD.train_id, OLD.coach_id, OLD.seat_no, OLD.travel_date, USER());
END //

-- ==================== MAINTENANCE TRIGGERS ====================
CREATE TRIGGER trg_maintenance_insert
AFTER INSERT ON Maintenance FOR EACH ROW
BEGIN
    INSERT INTO Maintenance_Audit
        (operation, maintenance_id, new_train_id, new_tech_id, new_maintenance_type, new_maintenance_date, new_remarks, performed_by)
    VALUES
        ('INSERT', NEW.maintenance_id, NEW.train_id, NEW.tech_id, NEW.maintenance_type, NEW.maintenance_date, NEW.remarks, USER());
END //

CREATE TRIGGER trg_maintenance_update
AFTER UPDATE ON Maintenance FOR EACH ROW
BEGIN
    INSERT INTO Maintenance_Audit
        (operation, maintenance_id,
         old_train_id, new_train_id, old_tech_id, new_tech_id,
         old_maintenance_type, new_maintenance_type,
         old_maintenance_date, new_maintenance_date,
         old_remarks, new_remarks, performed_by)
    VALUES
        ('UPDATE', NEW.maintenance_id,
         OLD.train_id, NEW.train_id, OLD.tech_id, NEW.tech_id,
         OLD.maintenance_type, NEW.maintenance_type,
         OLD.maintenance_date, NEW.maintenance_date,
         OLD.remarks, NEW.remarks, USER());
END //

CREATE TRIGGER trg_maintenance_delete
AFTER DELETE ON Maintenance FOR EACH ROW
BEGIN
    INSERT INTO Maintenance_Audit
        (operation, maintenance_id, old_train_id, old_tech_id, old_maintenance_type, old_maintenance_date, old_remarks, performed_by)
    VALUES
        ('DELETE', OLD.maintenance_id, OLD.train_id, OLD.tech_id, OLD.maintenance_type, OLD.maintenance_date, OLD.remarks, USER());
END //

DELIMITER ;

-- ==================== VIEWS (Reverse Queries) ====================
CREATE OR REPLACE VIEW v_passenger_lineage AS
SELECT audit_id, operation, passenger_id,
    CONCAT(IFNULL(old_first_name,''),' ',IFNULL(old_last_name,'')) AS old_name,
    CONCAT(IFNULL(new_first_name,''),' ',IFNULL(new_last_name,'')) AS new_name,
    old_contact, new_contact, performed_by, ip_address, operation_time
FROM Passenger_Audit ORDER BY passenger_id, operation_time;

CREATE OR REPLACE VIEW v_train_status_history AS
SELECT audit_id, train_id, old_status AS from_status, new_status AS to_status,
    performed_by, ip_address, operation_time
FROM Train_Audit
WHERE operation = 'UPDATE' AND (old_status != new_status OR old_status IS NULL)
ORDER BY train_id, operation_time;

CREATE OR REPLACE VIEW v_payment_changes AS
SELECT audit_id, payment_id, operation,
    old_amount, new_amount,
    (IFNULL(new_amount,0) - IFNULL(old_amount,0)) AS amount_diff,
    old_status, new_status, performed_by, operation_time
FROM Payment_Audit ORDER BY payment_id, operation_time;
