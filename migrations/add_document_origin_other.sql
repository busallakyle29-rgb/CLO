ALTER TABLE documents
    ADD COLUMN origin_department_other VARCHAR(255) NULL
    AFTER origin_department_id;

INSERT INTO departments
    (department_code, department_name, description, is_active)
VALUES
    ('OTHERS', 'Others', 'Personal documents and sources without a registered department.', 1)
ON DUPLICATE KEY UPDATE
    department_name = VALUES(department_name),
    description = VALUES(description),
    is_active = 1;
