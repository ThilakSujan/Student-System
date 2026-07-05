-- ══════════════════════════════════════════════════════════════
--  FEE MANAGEMENT SYSTEM – Database Schema
-- ══════════════════════════════════════════════════════════════

-- 1. Fee Categories  (custom, admin-defined — not predefined)
CREATE TABLE IF NOT EXISTS fee_categories (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    status      ENUM('Active','Inactive') DEFAULT 'Active',
    created_by  INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Fee Structures  (defines amount per category per class/academic year)
CREATE TABLE IF NOT EXISTS fee_structures (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    category_id   INT NOT NULL,
    class_id      INT DEFAULT NULL,          -- NULL = applies to all classes
    academic_year VARCHAR(20) NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    due_date      DATE DEFAULT NULL,
    description   TEXT,
    status        ENUM('Active','Inactive') DEFAULT 'Active',
    created_by    INT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES fee_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)    REFERENCES classes(id)        ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)          ON DELETE SET NULL
);

-- 3. Student Fee Payments
CREATE TABLE IF NOT EXISTS fee_payments (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    student_id      INT NOT NULL,
    structure_id    INT NOT NULL,
    amount_paid     DECIMAL(10,2) NOT NULL,
    payment_date    DATE NOT NULL,
    payment_method  ENUM('Cash','Bank Transfer','Cheque','Online','Other') DEFAULT 'Cash',
    receipt_no      VARCHAR(100),
    remarks         TEXT,
    recorded_by     INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)   REFERENCES students(id)        ON DELETE CASCADE,
    FOREIGN KEY (structure_id) REFERENCES fee_structures(id)  ON DELETE CASCADE,
    FOREIGN KEY (recorded_by)  REFERENCES users(id)           ON DELETE SET NULL
);
