ALTER TABLE document_routes
    ADD COLUMN external_recipient_name VARCHAR(150) DEFAULT NULL
    AFTER routed_to_user_id;
