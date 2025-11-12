-- Crear tabla notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type VARCHAR(50) DEFAULT 'info',
    data TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collector_id INT NOT NULL,
    collector_name VARCHAR(255) NOT NULL,
    loan_count INT NOT NULL,
    total_commission DECIMAL(10,2) NOT NULL,
    start_date DATE,
    end_date DATE,
    selected_loans TEXT,
    status ENUM('received', 'approved', 'rejected') DEFAULT 'received',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Crear tabla payment_audit_trail para auditoría de cálculos de pagos
CREATE TABLE IF NOT EXISTS payment_audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    timestamp DATETIME NOT NULL,
    payment_amount DECIMAL(15,2) NOT NULL,
    breakdown LONGTEXT,
    redistribution_log LONGTEXT,
    validation_results LONGTEXT,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_loan_id (loan_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_user_id (user_id)
);

-- Crear tabla customer_documents para documentos KYC/AML
CREATE TABLE IF NOT EXISTS customer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    document_type ENUM('identity', 'income_proof', 'personal_references', 'address_validation') NOT NULL,
    file_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    verified_by INT NULL,
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_document_type (document_type),
    INDEX idx_status (status)
);