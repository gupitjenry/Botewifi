CREATE DATABASE IF NOT EXISTS bottle_wifi_vendo;
USE bottle_wifi_vendo;

CREATE TABLE wifi_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mac_address VARCHAR(17) NOT NULL,
    ip_address VARCHAR(45),
    bottle_count INT DEFAULT 0,
    total_minutes INT DEFAULT 0,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    is_active BOOLEAN DEFAULT 1,
    disconnected_at DATETIME NULL,
    INDEX(mac_address),
    INDEX(is_active, end_time)
);

CREATE TABLE session_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES wifi_sessions(id) ON DELETE CASCADE
);

-- Stored Procedure
DELIMITER $$
CREATE PROCEDURE insert_bottle(
    IN p_mac_address VARCHAR(17),
    OUT p_session_id INT,
    OUT p_minutes_added INT
)
BEGIN
    SET p_minutes_added = 5; -- MINUTES_PER_BOTTLE
    
    -- Check if active session exists
    SELECT id INTO p_session_id 
    FROM wifi_sessions 
    WHERE mac_address = p_mac_address AND is_active = 1
    LIMIT 1;
    
    IF p_session_id IS NULL THEN
        -- Create new session
        INSERT INTO wifi_sessions (mac_address, bottle_count, total_minutes, start_time, end_time)
        VALUES (p_mac_address, 1, p_minutes_added, NOW(), DATE_ADD(NOW(), INTERVAL p_minutes_added MINUTE));
        SET p_session_id = LAST_INSERT_ID();
    ELSE
        -- Extend existing session
        UPDATE wifi_sessions 
        SET bottle_count = bottle_count + 1,
            total_minutes = total_minutes + p_minutes_added,
            end_time = DATE_ADD(end_time, INTERVAL p_minutes_added MINUTE)
        WHERE id = p_session_id;
    END IF;
END$$
DELIMITER ;

-- Views
CREATE VIEW today_stats_view AS
SELECT 
    SUM(bottle_count) as total_bottles,
    SUM(total_minutes) as total_minutes,
    COUNT(DISTINCT mac_address) as unique_users,
    COUNT(*) as total_sessions
FROM wifi_sessions
WHERE DATE(start_time) = CURDATE();

CREATE VIEW active_sessions_view AS
SELECT 
    id,
    mac_address,
    bottle_count,
    total_minutes,
    start_time,
    end_time,
    TIMESTAMPDIFF(MINUTE, NOW(), end_time) as remaining_minutes,
    CASE 
        WHEN end_time > NOW() THEN 'Active'
        ELSE 'Expired'
    END as status
FROM wifi_sessions
WHERE is_active = 1;

CREATE TABLE daily_stats (
    stat_date DATE PRIMARY KEY,
    total_bottles INT DEFAULT 0,
    total_minutes_given INT DEFAULT 0,
    unique_users INT DEFAULT 0,
    total_sessions INT DEFAULT 0
);