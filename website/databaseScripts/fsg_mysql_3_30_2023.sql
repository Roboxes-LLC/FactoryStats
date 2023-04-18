/*
 * Factory Stats MySql upgrade script
 *
 * Covers changes made between 10/7/2022 and 3/30/2023
 *
 * Table: factorystatsglobal
 *
 * Update summary:
 * - Add factstatdisplay user for all customers
 */
 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Factory Stats v1.30, live on 3/14/2022
-- Add facstatdisplay user
INSERT INTO `user` (`userId`, `username`, `passwordHash`, `roles`, `permissions`, `firstName`, `lastName`, `email`, `authToken`) VALUES
(13, 'factstatdisplay', 'notmeantforlogin', 3, 515, 'operator', '', '', 'jO9xT7iKvBwUsZDD56fV9UzFPin3qyvp');

-- Add user 13 for every customer
DELIMITER $$
CREATE PROCEDURE AddUserToCustomers()
BEGIN
   DECLARE done INT DEFAULT FALSE;
   DECLARE curCustomerId INT DEFAULT 0;
   DECLARE customerCursor CURSOR FOR SELECT customerId FROM customer;
   DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
   OPEN customerCursor;
   read_loop: LOOP
      FETCH customerCursor INTO curCustomerId;
      IF done THEN
         LEAVE read_loop;
      END IF;
      INSERT INTO `user_customer` (`userId`, `customerId`) VALUES (13, curCustomerId);
   END LOOP;
   CLOSE customerCursor;
END$$
DELIMITER ;

CALL AddUserToCustomers();

DROP PROCEDURE AddUserToCustomers;

COMMIT;
 

