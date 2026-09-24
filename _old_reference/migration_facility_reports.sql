USE ppk_demo;

CREATE TABLE IF NOT EXISTS facility_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    category ENUM('kecil', 'sedang', 'parah') NOT NULL,
    report_date DATE NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_reports_facility FOREIGN KEY (facility_id) REFERENCES facilities(id)
);
