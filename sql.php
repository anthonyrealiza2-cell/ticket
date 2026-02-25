-- Drop database if exists (optional - only if you want a fresh start)
DROP DATABASE IF EXISTS ticket_system;

-- Create database
CREATE DATABASE IF NOT EXISTS ticket_system;
USE ticket_system;

-- =============================================
-- TABLES CREATION
-- =============================================

-- Clients table
CREATE TABLE clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Technical staff table
CREATE TABLE technical_staff (
    technical_id INT PRIMARY KEY AUTO_INCREMENT,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    contact_viber VARCHAR(50),
    branch VARCHAR(100),
    position VARCHAR(100),
    total_ticket INT DEFAULT 0,
    resolve INT DEFAULT 0,
    unresolve INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(255) NOT NULL,
    version VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Concerns table
CREATE TABLE concerns (
    concern_id INT PRIMARY KEY AUTO_INCREMENT,
    concern_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tickets table (UPDATED with more fields)
CREATE TABLE tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    company_id INT,
    contact_person VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50),
    email VARCHAR(255),
    technical_personnel VARCHAR(255),
    technical_id INT,
    product VARCHAR(255),
    concern_type VARCHAR(255),
    concern TEXT,
    date_requested DATETIME,
    submitted_date DATETIME,
    finish_date DATETIME,
    solution TEXT,
    remarks TEXT,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Pending', 'Assigned', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Pending',
    assigned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (technical_id) REFERENCES technical_staff(technical_id),
    FOREIGN KEY (company_id) REFERENCES clients(client_id)
);

-- Remarks/Follow-ups table
CREATE TABLE remarks (
    remark_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    company VARCHAR(255),
    date_requested DATETIME,
    scheduled_date DATETIME,
    finished_date DATETIME,
    remarks TEXT,
    FOREIGN KEY (client_id) REFERENCES clients(client_id)
);

-- =============================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =============================================

-- Drop triggers if they exist
DROP TRIGGER IF EXISTS update_tech_on_status_change;
DROP TRIGGER IF EXISTS update_tech_on_assign;
DROP TRIGGER IF EXISTS update_client_on_ticket_create;

-- Trigger for updating technical staff stats when ticket status changes
DELIMITER $$
CREATE TRIGGER update_tech_on_status_change 
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    -- If ticket is resolved
    IF NEW.status = 'Resolved' AND OLD.status != 'Resolved' AND NEW.technical_id IS NOT NULL THEN
        UPDATE technical_staff 
        SET resolve = resolve + 1
        WHERE technical_id = NEW.technical_id;
    
    -- If ticket was resolved and now changed to something else
    ELSEIF OLD.status = 'Resolved' AND NEW.status != 'Resolved' AND NEW.technical_id IS NOT NULL THEN
        UPDATE technical_staff 
        SET resolve = resolve - 1
        WHERE technical_id = NEW.technical_id;
    END IF;
END$$
DELIMITER ;

-- Trigger for when ticket is assigned to technical staff
DROP TRIGGER IF EXISTS update_tech_on_assign;
DELIMITER $$
CREATE TRIGGER update_tech_on_assign
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    -- When ticket is assigned to a technician (new assignment)
    IF NEW.technical_id IS NOT NULL AND OLD.technical_id IS NULL THEN
        UPDATE technical_staff 
        SET total_ticket = total_ticket + 1
        WHERE technical_id = NEW.technical_id;
    
    -- When ticket is reassigned to different technician
    ELSEIF NEW.technical_id IS NOT NULL AND OLD.technical_id IS NOT NULL AND NEW.technical_id != OLD.technical_id THEN
        -- Decrement old tech
        UPDATE technical_staff 
        SET total_ticket = total_ticket - 1
        WHERE technical_id = OLD.technical_id;
        -- Increment new tech
        UPDATE technical_staff 
        SET total_ticket = total_ticket + 1
        WHERE technical_id = NEW.technical_id;
    END IF;
END$$
DELIMITER ;

-- =============================================
-- SAMPLE DATA
-- =============================================

-- Insert sample technical staff
INSERT INTO technical_staff (firstname, lastname, email, contact_viber, branch, position, total_ticket, resolve) VALUES
('John', 'Doe', 'john.doe@company.com', '09123456789', 'Main Branch', 'Senior Technical', 15, 12),
('Jane', 'Smith', 'jane.smith@company.com', '09234567890', 'North Branch', 'Technical Support', 10, 9),
('Mike', 'Wilson', 'mike.wilson@company.com', '09345678901', 'South Branch', 'Junior Technical', 8, 5),
('Sarah', 'Brown', 'sarah.brown@company.com', '09456789012', 'East Branch', 'Technical Lead', 20, 18),
('Alex', 'Johnson', 'alex.johnson@company.com', '09567890123', 'West Branch', 'Support Specialist', 12, 8);

-- Insert sample products
INSERT INTO products (product_name, version) VALUES
('Customer Management System', '2.1.0'),
('Inventory Pro', '3.0.5'),
('Billing System', '1.8.2'),
('HR Management Suite', '4.2.1'),
('Point of Sale', '2.3.0');

-- Insert sample concerns
INSERT INTO concerns (concern_name, description) VALUES
('Technical Error', 'System showing error messages or crashes'),
('Feature Request', 'Customer requesting new feature or enhancement'),
('Bug Report', 'Application not working as expected'),
('Account Issue', 'Login or access problems'),
('Performance Issue', 'System running slow or lagging'),
('Data Loss', 'Missing or corrupted data'),
('Integration Problem', 'Issues with third-party integration');

-- Insert sample clients
INSERT INTO clients (company_name, contact_person, contact_number, email) VALUES
('ABC Corporation', 'Robert Santos', '09111213141', 'robert@abccorp.com'),
('XYZ Enterprises', 'Maria Garcia', '09222324252', 'maria@xyzenterprises.com'),
('Tech Solutions Inc', 'James Reyes', '09333435363', 'james@techsolutions.com'),
('Global Trading', 'Patricia Lim', '09444546474', 'patricia@globaltrading.com'),
('First Bank', 'Christopher Tan', '09555657585', 'christopher@firstbank.com');

-- Insert sample tickets (UPDATED with more fields)
INSERT INTO tickets (company_name, company_id, contact_person, contact_number, email, product, concern_type, concern, date_requested, priority, status, technical_id, created_at) VALUES
('ABC Corporation', 1, 'Robert Santos', '09111213141', 'robert@abccorp.com', 'Customer Management System', 'Technical Error', 
 'Product: Customer Management System\nConcern Type: Technical Error\nDescription: System crashes when generating monthly reports', 
 '2026-02-01 09:30:00', 'High', 'Resolved', 1, '2026-02-01 09:30:00'),

('XYZ Enterprises', 2, 'Maria Garcia', '09222324252', 'maria@xyzenterprises.com', 'Inventory Pro', 'Feature Request',
 'Product: Inventory Pro\nConcern Type: Feature Request\nDescription: Need additional field for batch numbers in inventory tracking',
 '2026-02-05 14:20:00', 'Medium', 'Assigned', 2, '2026-02-05 14:20:00'),

('Tech Solutions Inc', 3, 'James Reyes', '09333435363', 'james@techsolutions.com', 'Billing System', 'Bug Report',
 'Product: Billing System\nConcern Type: Bug Report\nDescription: Incorrect tax calculation for international clients',
 '2026-02-10 11:45:00', 'High', 'In Progress', 3, '2026-02-10 11:45:00'),

('Global Trading', 4, 'Patricia Lim', '09444546474', 'patricia@globaltrading.com', 'HR Management Suite', 'Account Issue',
 'Product: HR Management Suite\nConcern Type: Account Issue\nDescription: New employee cannot access payroll module',
 '2026-02-15 10:15:00', 'Low', 'Pending', NULL, '2026-02-15 10:15:00'),

('First Bank', 5, 'Christopher Tan', '09555657585', 'christopher@firstbank.com', 'Point of Sale', 'Performance Issue',
 'Product: Point of Sale\nConcern Type: Performance Issue\nDescription: Transaction processing taking too long during peak hours',
 '2026-02-18 16:30:00', 'High', 'Resolved', 4, '2026-02-18 16:30:00'),

('ABC Corporation', 1, 'Robert Santos', '09111213141', 'robert@abccorp.com', 'Inventory Pro', 'Integration Problem',
 'Product: Inventory Pro\nConcern Type: Integration Problem\nDescription: Unable to sync with accounting software',
 '2026-02-20 13:45:00', 'Medium', 'Pending', NULL, '2026-02-20 13:45:00'),

('XYZ Enterprises', 2, 'Maria Garcia', '09222324252', 'maria@xyzenterprises.com', 'Billing System', 'Data Loss',
 'Product: Billing System\nConcern Type: Data Loss\nDescription: Missing invoice records from last week',
 '2026-02-22 09:00:00', 'High', 'Assigned', 5, '2026-02-22 09:00:00');

-- Update finish dates for resolved tickets
UPDATE tickets SET 
    finish_date = DATE_ADD(date_requested, INTERVAL 3 DAY),
    solution = 'Fixed by updating system configuration and applying latest patch. Tested and verified working.',
    submitted_date = DATE_ADD(date_requested, INTERVAL 1 DAY)
WHERE status = 'Resolved';

-- Update technical staff resolve counts based on tickets
UPDATE technical_staff ts
SET ts.resolve = (
    SELECT COUNT(*) 
    FROM tickets t 
    WHERE t.technical_id = ts.technical_id 
    AND t.status = 'Resolved'
);

-- Update technical staff total_ticket counts
UPDATE technical_staff ts
SET ts.total_ticket = (
    SELECT COUNT(*) 
    FROM tickets t 
    WHERE t.technical_id = ts.technical_id
);

-- =============================================
-- VIEWS FOR EASY REPORTING
-- =============================================

-- View for ticket statistics by status
CREATE VIEW vw_ticket_stats AS
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets), 1) as percentage
FROM tickets
GROUP BY status;

-- View for priority distribution
CREATE VIEW vw_priority_stats AS
SELECT 
    priority,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets), 1) as percentage
FROM tickets
GROUP BY priority;

-- View for technical staff performance
CREATE VIEW vw_tech_performance AS
SELECT 
    technical_id,
    CONCAT(firstname, ' ', lastname) as full_name,
    email,
    branch,
    position,
    total_ticket,
    resolve,
    CASE 
        WHEN total_ticket > 0 THEN ROUND((resolve / total_ticket) * 100, 1)
        ELSE 0
    END as performance_rate,
    (total_ticket - resolve) as pending
FROM technical_staff;

-- View for client statistics
CREATE VIEW vw_client_stats AS
SELECT 
    c.client_id,
    c.company_name,
    c.contact_person,
    c.email,
    COUNT(t.ticket_id) as total_tickets,
    SUM(CASE WHEN t.status = 'Resolved' THEN 1 ELSE 0 END) as resolved_tickets,
    SUM(CASE WHEN t.status IN ('Pending', 'Assigned', 'In Progress') THEN 1 ELSE 0 END) as open_tickets,
    MAX(t.created_at) as last_ticket_date
FROM clients c
LEFT JOIN tickets t ON c.client_id = t.company_id
GROUP BY c.client_id;

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_priority ON tickets(priority);
CREATE INDEX idx_tickets_company ON tickets(company_id);
CREATE INDEX idx_tickets_technical ON tickets(technical_id);
CREATE INDEX idx_tickets_date ON tickets(date_requested);
CREATE INDEX idx_technical_email ON technical_staff(email);
CREATE INDEX idx_client_email ON clients(email);

-- =============================================
-- STORED PROCEDURES
-- =============================================

-- Procedure to get dashboard statistics
DELIMITER $$
CREATE PROCEDURE sp_get_dashboard_stats()
BEGIN
    -- Total tickets
    SELECT COUNT(*) as total_tickets FROM tickets;
    
    -- Pending tickets
    SELECT COUNT(*) as pending_tickets FROM tickets WHERE status = 'Pending';
    
    -- Resolved tickets
    SELECT COUNT(*) as resolved_tickets FROM tickets WHERE status = 'Resolved';
    
    -- Total technical staff
    SELECT COUNT(*) as total_tech FROM technical_staff;
    
    -- Recent tickets
    SELECT * FROM tickets ORDER BY created_at DESC LIMIT 10;
    
    -- Top performers
    SELECT 
        CONCAT(firstname, ' ', lastname) as name,
        resolve as resolved,
        total_ticket as total,
        ROUND((resolve / NULLIF(total_ticket, 0)) * 100, 1) as rate
    FROM technical_staff
    WHERE total_ticket > 0
    ORDER BY rate DESC
    LIMIT 5;
END$$
DELIMITER ;

-- =============================================
-- SAMPLE QUERIES FOR TESTING
-- =============================================

-- Test the database with some queries
SELECT '=== DATABASE CREATED SUCCESSFULLY ===' as status;

SELECT CONCAT('Total Tickets: ', COUNT(*)) as info FROM tickets;
SELECT CONCAT('Total Clients: ', COUNT(*)) as info FROM clients;
SELECT CONCAT('Total Technical Staff: ', COUNT(*)) as info FROM technical_staff;
SELECT CONCAT('Total Products: ', COUNT(*)) as info FROM products;
SELECT CONCAT('Total Concerns: ', COUNT(*)) as info FROM concerns;

-- Show ticket distribution by status
SELECT 'Ticket Distribution by Status:' as '';
SELECT status, COUNT(*) as count FROM tickets GROUP BY status;

-- Show priority distribution
SELECT 'Priority Distribution:' as '';
SELECT priority, COUNT(*) as count FROM tickets GROUP BY priority;

-- Show technical staff performance
SELECT 'Technical Staff Performance:' as '';
SELECT 
    CONCAT(firstname, ' ', lastname) as name,
    total_ticket as total,
    resolve as resolved,
    (total_ticket - resolve) as pending,
    ROUND((resolve / NULLIF(total_ticket, 0)) * 100, 1) as performance
FROM technical_staff
ORDER BY performance DESC;