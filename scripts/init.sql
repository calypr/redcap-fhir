-- Initialize REDCap database
CREATE TABLE IF NOT EXISTS fhir_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(50) NOT NULL,
    resource_id VARCHAR(255) NOT NULL,
    data LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_resource (resource_type, resource_id),
    INDEX idx_resource_type (resource_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit log table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation VARCHAR(20) NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255),
    ip_address VARCHAR(45),
    request_body LONGTEXT,
    response_status INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_operation (operation),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sync log table
CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_type VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50),
    status VARCHAR(20) NOT NULL,
    message TEXT,
    record_count INT,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create CDIS jobs table
CREATE TABLE IF NOT EXISTS cdis_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    requested_by VARCHAR(255),
    payload LONGTEXT,
    result LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cdis_jobs_type (job_type),
    INDEX idx_cdis_jobs_status (status),
    INDEX idx_cdis_jobs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create CDIS adjudication table
CREATE TABLE IF NOT EXISTS cdis_adjudications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT,
    patient_id VARCHAR(255) NOT NULL,
    redcap_field VARCHAR(255) NOT NULL,
    resource_type VARCHAR(50),
    resource_id VARCHAR(255),
    proposed_value LONGTEXT,
    selected_value LONGTEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    adjudicated_by VARCHAR(255),
    adjudicated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cdis_adj_job (job_id),
    INDEX idx_cdis_adj_patient (patient_id),
    INDEX idx_cdis_adj_status (status),
    CONSTRAINT fk_cdis_adj_job FOREIGN KEY (job_id) REFERENCES cdis_jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Set proper permissions
GRANT ALL PRIVILEGES ON redcap.* TO 'redcap'@'%';
FLUSH PRIVILEGES;
