DELIMITER //

CREATE TRIGGER after_booking_checkout
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    IF NEW.status = 'checked_out' AND OLD.status != 'checked_out' THEN
        -- Wait for a short delay to ensure checkout process is complete
        SET @current_date = CURRENT_DATE();
        
        -- If checkout date is in the past or today, archive immediately
        IF NEW.check_out <= @current_date THEN
            UPDATE bookings 
            SET status = 'archived'
            WHERE id = NEW.id;
        END IF;
    END IF;
END//

DELIMITER ;
