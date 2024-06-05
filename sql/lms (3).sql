-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2024 at 05:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `GetAcronym` (`str` TEXT) RETURNS TEXT CHARSET utf8 COLLATE utf8_general_ci READS SQL DATA SQL SECURITY INVOKER BEGIN
    declare result text default '';
    set result = GetInitials( str, '[[:alnum:]]' );
    return result;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetAncestry` (`GivenID` INT) RETURNS VARCHAR(1024) CHARSET utf8 COLLATE utf8_general_ci READS SQL DATA SQL SECURITY INVOKER BEGIN
    DECLARE rv VARCHAR(1024);
    DECLARE cm CHAR(1);
    DECLARE ch INT;

    SET rv = '';
    SET cm = '';
    SET ch = GivenID;
    WHILE ch > 0 DO
        SELECT IFNULL(parent_id,-1) INTO ch FROM
        (SELECT parent_id FROM organization WHERE id = ch) A;
        IF ch > 0 THEN
            SET rv = CONCAT(rv,cm,ch);
            SET cm = ',';
        END IF;
    END WHILE;
    RETURN rv;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetFamilyTree` (`GivenID` INT) RETURNS VARCHAR(1024) CHARSET utf8 COLLATE utf8_general_ci READS SQL DATA SQL SECURITY INVOKER BEGIN

    DECLARE rv,q,queue,queue_children VARCHAR(1024);
    DECLARE queue_length,front_id,pos INT;

    SET rv = '';
    SET queue = GivenID;
    SET queue_length = 1;

    WHILE queue_length > 0 DO
        IF queue_length = 1 THEN
            SET front_id = CAST(queue AS DECIMAL);
            SET queue = '';
        ELSE
            SET pos = LOCATE(',',queue);
            SET front_id = CAST(SUBSTR(queue, 1, pos-1) AS DECIMAL);
            SET q = SUBSTR(queue,pos + 1); 
            SET queue = q;
        END IF;
        SET queue_length = queue_length - 1;

        SELECT IFNULL(qc,'') INTO queue_children
        FROM (SELECT GROUP_CONCAT(id) qc
        FROM organization WHERE parent_id = front_id) A;

        IF LENGTH(queue_children) = 0 THEN
            IF LENGTH(queue) = 0 THEN
                SET queue_length = 0;
            END IF;
        ELSE
            IF LENGTH(rv) = 0 THEN
                SET rv = queue_children;
            ELSE
                SET rv = CONCAT(rv,',',queue_children);
            END IF;
            IF LENGTH(queue) = 0 THEN
                SET queue = queue_children;
            ELSE
                SET queue = CONCAT(queue,',',queue_children);
            END IF;
            SET queue_length = LENGTH(queue) - LENGTH(REPLACE(queue,',','')) + 1;
        END IF;
    END WHILE;
    RETURN rv;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetInitials` (`str` TEXT, `expr` TEXT) RETURNS TEXT CHARSET utf8 COLLATE utf8_general_ci READS SQL DATA SQL SECURITY INVOKER BEGIN
    declare result text default '';
    declare buffer text default '';
    declare i int default 1;
    if(str is null) then
        return null;
    end if;
    set buffer = trim(str);
    while i <= length(buffer) do
        if substr(buffer, i, 1) regexp expr then
            set result = concat( result, substr( buffer, i, 1 ));
            set i = i + 1;
            while i <= length( buffer ) and substr(buffer, i, 1) regexp expr do
                set i = i + 1;
            end while;
            while i <= length( buffer ) and substr(buffer, i, 1) not regexp expr do
                set i = i + 1;
            end while;
        else
            set i = i + 1;
        end if;
    end while;
    return result;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `GetParentIDByID` (`GivenID` INT) RETURNS INT(11) READS SQL DATA SQL SECURITY INVOKER BEGIN
    DECLARE rv INT;

    SELECT IFNULL(parent_id,-1) INTO rv FROM
    (SELECT parent_id FROM organization WHERE id = GivenID) A;
    RETURN rv;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `actions`
--

CREATE TABLE `actions` (
  `name` varchar(45) NOT NULL,
  `mask` bit(16) NOT NULL,
  `Description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of possible actions';

--
-- Dumping data for table `actions`
--

INSERT INTO `actions` (`name`, `mask`, `Description`) VALUES
('accept_requests', b'0011000100110010', 'Accept the request of my team members'),
('admin_menu', b'0011000100110010', 'View admin menu'),
('change_password', b'0011000100110010', 'Change password'),
('create_leaves', b'0011000100110010', 'Create a new user leave request'),
('create_user', b'0011000100110010', 'Create a new user'),
('delete_user', b'0011000100110010', 'Delete an existing user'),
('edit_leaves', b'0011000100110010', 'Edit a leave request'),
('edit_settings', b'0011000100110010', 'Edit application settings'),
('edit_user', b'0011000100110010', 'Edit a user'),
('export_leaves', b'0011000100110010', 'Export the list of leave requests into an Excel file'),
('export_user', b'0011000100110010', 'Export the list of users into an Excel file'),
('hr_menu', b'0011000100110010', 'View HR menu'),
('individual_calendar', b'0011000100110010', 'View my leaves in a calendar'),
('list_leaves', b'0011000100110010', 'List my leave requests'),
('list_requests', b'0011000100110010', 'List the request of my team members'),
('list_users', b'0011000100110010', 'List users'),
('reject_requests', b'0011000100110010', 'Reject the request of my team members'),
('reset_password', b'0011000100110010', 'Modifiy the password of another user'),
('team_calendar', b'0011000100110010', 'View the leaves of my team in a calendar'),
('update_user', b'0011000100110010', 'Update a user'),
('view_leaves', b'0011000100110010', 'View the details of a leave request'),
('view_user', b'0011000100110010', 'View user\'s details');

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CodeIgniter sessions (you can empty this table without consequence)';

--
-- Dumping data for table `ci_sessions`
--

INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES
('dtoo6ati7utjap3fht2731vlsvg14mak', '::1', 1716947020, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363934373032303b6c6173745f706167657c733a33313a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f7573657273223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a383a22514f306c6e755152223b6c6f67696e7c733a323a226872223b69647c693a32333b66697273746e616d657c733a323a226872223b6c6173746e616d657c733a393a22706572736f6e6e656c223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a32373b72616e646f6d5f686173687c733a32343a22685446546f536e656d57447633736863783033784b627252223b6c6f676765645f696e7c623a313b),
('j78j3mmqjlrao5ok4m526csivqdj2usi', '::1', 1716947416, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363934373431363b6c6173745f706167657c733a33383a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f68722f656d706c6f79656573223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a383a22514f306c6e755152223b6c6f67696e7c733a323a226872223b69647c693a32333b66697273746e616d657c733a323a226872223b6c6173746e616d657c733a393a22706572736f6e6e656c223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a32373b72616e646f6d5f686173687c733a32343a22685446546f536e656d57447633736863783033784b627252223b6c6f676765645f696e7c623a313b),
('5s5iouiecfphvjj9rbj58s2rjoeo58uo', '::1', 1716948120, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363934383132303b6c6173745f706167657c733a33383a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f6f7267616e697a6174696f6e223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a383a22514f306c6e755152223b6c6f67696e7c733a323a226872223b69647c693a32333b66697273746e616d657c733a323a226872223b6c6173746e616d657c733a393a22706572736f6e6e656c223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a32373b72616e646f6d5f686173687c733a32343a22685446546f536e656d57447633736863783033784b627252223b6c6f676765645f696e7c623a313b),
('5g5e9obtavhs7bd1ueppi0dnfpue04c2', '::1', 1716953532, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363935333533323b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22564f4564566b657674626e49576d436a223b6c6f67696e7c733a323a225341223b69647c693a313b66697273746e616d657c733a363a22537570657220223b6c6173746e616d657c733a353a2241646d696e223b69735f6d616e616765727c623a303b69735f61646d696e7c623a303b69735f68727c623a313b6d616e616765727c693a313b72616e646f6d5f686173687c733a32343a2235673556556d355a4b6635546b4b3038794d74754b786535223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33383a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f6f7267616e697a6174696f6e223b6c6173745f706167655f706172616d737c733a303a22223b),
('nk8s1skr1mc7adm9bhq092i1kmdokv7g', '::1', 1716953947, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363935333934373b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32303a2275426b50444448367665534b70784a5173575064223b6c6f67696e7c733a323a226872223b69647c693a323833353b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a31333a2241646d696e6973747261746f72223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323833353b72616e646f6d5f686173687c733a32343a22485344765a685f6e6f704a4476656b7a6f536b46514f7567223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33383a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f68722f656d706c6f79656573223b6c6173745f706167655f706172616d737c733a303a22223b),
('sgo7dv12o1qbb7ih97poh3s0d7l8nsdg', '::1', 1716953996, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363935333934373b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32303a2275426b50444448367665534b70784a5173575064223b6c6f67696e7c733a323a226872223b69647c693a323833353b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a31333a2241646d696e6973747261746f72223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323833353b72616e646f6d5f686173687c733a32343a22485344765a685f6e6f704a4476656b7a6f536b46514f7567223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33313a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f7573657273223b6c6173745f706167655f706172616d737c733a303a22223b),
('dtoo6ati7utjap3fht2731vlsvg14mak', '::1', 1716947020, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363934373032303b6c6173745f706167657c733a33313a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f7573657273223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a383a22514f306c6e755152223b6c6f67696e7c733a323a226872223b69647c693a32333b66697273746e616d657c733a323a226872223b6c6173746e616d657c733a393a22706572736f6e6e656c223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a32373b72616e646f6d5f686173687c733a32343a22685446546f536e656d57447633736863783033784b627252223b6c6f676765645f696e7c623a313b),
('j78j3mmqjlrao5ok4m526csivqdj2usi', '::1', 1716947416, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363934373431363b6c6173745f706167657c733a33383a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f68722f656d706c6f79656573223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a383a22514f306c6e755152223b6c6f67696e7c733a323a226872223b69647c693a32333b66697273746e616d657c733a323a226872223b6c6173746e616d657c733a393a22706572736f6e6e656c223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a32373b72616e646f6d5f686173687c733a32343a22685446546f536e656d57447633736863783033784b627252223b6c6f676765645f696e7c623a313b),
('5s5iouiecfphvjj9rbj58s2rjoeo58uo', '::1', 1716947689, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731363934373431363b6c6173745f706167657c733a33383a2268747470733a2f2f6c6f63616c686f73742f534b472d4c4d532f6f7267616e697a6174696f6e223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a383a22514f306c6e755152223b6c6f67696e7c733a323a226872223b69647c693a32333b66697273746e616d657c733a323a226872223b6c6173746e616d657c733a393a22706572736f6e6e656c223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a32373b72616e646f6d5f686173687c733a32343a22685446546f536e656d57447633736863783033784b627252223b6c6f676765645f696e7c623a313b);

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of a contract',
  `name` varchar(128) NOT NULL COMMENT 'Name of the contract',
  `startentdate` varchar(5) NOT NULL COMMENT 'Day and month numbers of the left boundary',
  `endentdate` varchar(5) NOT NULL COMMENT 'Day and month numbers of the right boundary',
  `weekly_duration` int(11) DEFAULT NULL COMMENT 'Approximate duration of work per week (in minutes)',
  `daily_duration` int(11) DEFAULT NULL COMMENT 'Approximate duration of work per day and (in minutes)',
  `default_leave_type` int(11) DEFAULT NULL COMMENT 'default leave type for the contract (overwrite default type set in config file).'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A contract groups employees having the same days off and entitlement rules';

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`id`, `name`, `startentdate`, `endentdate`, `weekly_duration`, `daily_duration`, `default_leave_type`) VALUES
(0, 'System Admin', '01/01', '12/31', NULL, NULL, 0),
(1, 'Executive before 13 March 2006', '01/01', '12/31', NULL, NULL, 0),
(2, 'Non-Executive before 13 March 2006', '01/01', '12/31', NULL, NULL, 0),
(3, 'Executive after 13 March 2006', '01/01', '12/31', NULL, NULL, 0),
(4, 'Non-Executive after 13 March 2006', '01/01', '12/31', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `dayoffs`
--

CREATE TABLE `dayoffs` (
  `id` int(11) NOT NULL,
  `contract` int(11) NOT NULL COMMENT 'Contract id',
  `date` date NOT NULL COMMENT 'Date of the day off',
  `type` int(11) NOT NULL COMMENT 'Half or full day',
  `title` varchar(128) NOT NULL COMMENT 'Description of day off'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of non working days';

-- --------------------------------------------------------

--
-- Table structure for table `delegations`
--

CREATE TABLE `delegations` (
  `id` int(11) NOT NULL COMMENT 'Id of delegation',
  `manager_id` int(11) NOT NULL COMMENT 'Manager wanting to delegate',
  `delegate_id` int(11) NOT NULL COMMENT 'Employee having the delegation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Delegation of approval';

-- --------------------------------------------------------

--
-- Table structure for table `entitleddays`
--

CREATE TABLE `entitleddays` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of an entitlement',
  `contract` int(11) DEFAULT NULL COMMENT 'If entitlement is credited to a contract, Id of contract',
  `employee` int(11) DEFAULT NULL COMMENT 'If entitlement is credited to an employee, Id of employee',
  `overtime` int(11) DEFAULT NULL COMMENT 'Optional Link to an overtime request, if the credit is due to an OT',
  `startdate` date DEFAULT NULL COMMENT 'Left boundary of the credit validity',
  `enddate` date DEFAULT NULL COMMENT 'Right boundary of the credit validity. Duration cannot exceed one year',
  `type` int(11) NOT NULL COMMENT 'Leave type',
  `days` decimal(10,2) NOT NULL COMMENT 'Number of days (can be negative so as to deduct/adjust entitlement)',
  `description` text DEFAULT NULL COMMENT 'Description of a credit / debit (entitlement / adjustment)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Add or substract entitlement on employees or contracts (can be the result of an OT)';

-- --------------------------------------------------------

--
-- Table structure for table `excluded_types`
--

CREATE TABLE `excluded_types` (
  `id` int(11) NOT NULL COMMENT 'Id of exclusion',
  `contract_id` int(11) NOT NULL COMMENT 'Id of contract',
  `type_id` int(11) NOT NULL COMMENT 'Id of leave ype to be excluded to the contract'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Exclude a leave type from a contract';

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of the leave request',
  `startdate` date DEFAULT NULL COMMENT 'Start date of the leave request',
  `enddate` date DEFAULT NULL COMMENT 'End date of the leave request',
  `status` int(11) DEFAULT NULL COMMENT 'Identifier of the status of the leave request (Requested, Accepted, etc.). See status table.',
  `employee` int(11) DEFAULT NULL COMMENT 'Employee requesting the leave request',
  `cause` text DEFAULT NULL COMMENT 'Reason of the leave request',
  `attachment` varchar(255) DEFAULT NULL COMMENT 'Attachment of the leave request',
  `startdatetype` varchar(12) DEFAULT NULL COMMENT 'Morning/Afternoon',
  `enddatetype` varchar(12) DEFAULT NULL COMMENT 'Morning/Afternoon',
  `duration` decimal(10,3) DEFAULT NULL COMMENT 'Length of the leave request',
  `type` int(11) DEFAULT NULL COMMENT 'Identifier of the type of the leave request (Paid, Sick, etc.). See type table.',
  `comments` text DEFAULT NULL COMMENT 'Comments on leave request (JSon)',
  `document` blob DEFAULT NULL COMMENT 'Optional supporting document'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Leave requests';

-- --------------------------------------------------------

--
-- Table structure for table `leaves_history`
--

CREATE TABLE `leaves_history` (
  `id` int(11) NOT NULL,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `employee` int(11) DEFAULT NULL,
  `cause` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL COMMENT 'Attachment of the leave request',
  `startdatetype` varchar(12) DEFAULT NULL,
  `enddatetype` varchar(12) DEFAULT NULL,
  `duration` decimal(10,2) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL COMMENT 'Comments on leave request',
  `document` blob DEFAULT NULL COMMENT 'Optional supporting document',
  `change_id` int(11) NOT NULL,
  `change_type` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `change_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of changes in leave requests table';

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`) VALUES
(1, 'Head Office'),
(2, 'Lahad Datu Region'),
(3, 'Sandakan Region'),
(4, 'Tawau Region'),
(5, 'West Coast Region');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `access_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `scope` varchar(2000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_applications`
--

CREATE TABLE `oauth_applications` (
  `user` int(11) NOT NULL COMMENT 'Identifier of skg-lms user',
  `client_id` varchar(80) NOT NULL COMMENT 'Identifier of an application using OAuth2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of allowed OAuth2 applications';

-- --------------------------------------------------------

--
-- Table structure for table `oauth_authorization_codes`
--

CREATE TABLE `oauth_authorization_codes` (
  `authorization_code` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `redirect_uri` varchar(2000) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `scope` varchar(2000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `client_id` varchar(80) NOT NULL,
  `client_secret` varchar(80) DEFAULT NULL,
  `redirect_uri` varchar(2000) NOT NULL,
  `grant_types` varchar(80) DEFAULT NULL,
  `scope` varchar(100) DEFAULT NULL,
  `user_id` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_jwt`
--

CREATE TABLE `oauth_jwt` (
  `client_id` varchar(80) NOT NULL,
  `subject` varchar(80) DEFAULT NULL,
  `public_key` varchar(2000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `refresh_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `scope` varchar(2000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_scopes`
--

CREATE TABLE `oauth_scopes` (
  `scope` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_users`
--

CREATE TABLE `oauth_users` (
  `username` varchar(255) NOT NULL,
  `password` varchar(2000) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization`
--

CREATE TABLE `organization` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of the department',
  `name` varchar(512) DEFAULT NULL COMMENT 'Name of the department',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Parent department (or -1 if root)',
  `supervisor` int(11) DEFAULT NULL COMMENT 'This user will receive a copy of accepted and rejected leave requests'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tree of the organization';

--
-- Dumping data for table `organization`
--

INSERT INTO `organization` (`id`, `name`, `parent_id`, `supervisor`) VALUES
(1, 'Sawit Kinabalu Group', -1, NULL),
(2, 'Headquaters Region', 1, NULL),
(3, 'Lahad Datu Region', 1, NULL),
(4, 'Tawau Region', 1, NULL),
(5, 'Sandakan Region', 1, NULL),
(6, 'West Coast Region', 1, NULL),
(7, 'Regional Office West Coast', 18, NULL),
(8, 'Langkon Estate', 18, NULL),
(9, 'Pinawantai Estate', 18, NULL),
(10, 'Pitas Estate', 18, NULL),
(11, 'Taritipan Estate', 18, NULL),
(12, 'Lumadan Estate', 18, NULL),
(13, 'Mawao Estate', 18, NULL),
(14, 'Bongawan Estate', 18, NULL),
(15, 'Kimanis Estate', 18, NULL),
(16, 'Langkon Mill', 18, NULL),
(17, 'Lumadan Mill', 18, NULL),
(18, 'ARAS - West Coast', 18, NULL),
(19, 'Kabang Estate', 18, NULL),
(20, 'Pilajau Estate', 18, NULL),
(21, 'Regional Office Tawau', 4, NULL),
(22, 'Sungai Balung Estate', 4, NULL),
(23, 'Sungai Kawa Estate', 4, NULL),
(24, 'Merotai Estate', 4, NULL),
(25, 'Madai Estate', 4, NULL),
(26, 'Pegagau Estate', 4, NULL),
(27, 'Ulu Kalabakan Estate', 4, NULL),
(28, 'Apas Balung Mill', 4, NULL),
(29, 'Serudung Mill', 4, NULL),
(30, 'ARAS - Tawau', 4, NULL),
(31, 'Kunak Mill', 4, NULL),
(32, 'Balung Animal Feeds Mill', 4, NULL),
(33, 'Central Lab', 4, NULL),
(34, 'Bongalio Estate', 4, NULL),
(35, 'Sawit Biotech', 4, NULL),
(36, 'Seeds Processing Unit', 4, NULL),
(37, 'Tawau Seed Garden', 4, NULL),
(38, 'Saplantco Sdn Bhd', 4, NULL),
(39, 'Gomantong Nursery', 4, NULL),
(40, 'Langkon Nursery', 4, NULL),
(41, 'Lumadan Nursery', 4, NULL),
(42, 'Mensuli Nursery', 4, NULL),
(43, 'Sebrang Nursery', 4, NULL),
(44, 'Sungai Balung Nursery', 4, NULL),
(45, 'Kunak Refinery', 4, NULL),
(46, 'Kalabakan Estate', 4, NULL),
(47, 'Sawit Kinabalu Farm Products', 4, NULL),
(48, 'Sawit Kinabalu Farm West', 4, NULL),
(49, 'Business Leadership Academy', 2, NULL),
(50, 'Security Unit', 2, NULL),
(51, 'Integrity &amp; Governance Unit', 2, NULL),
(52, 'Marketing Unit', 2, NULL),
(53, 'Contract &amp; Procurement Unit', 2, NULL),
(54, 'Corporate Communication Unit', 2, NULL),
(55, 'Corporate Planning Unit', 2, NULL),
(56, 'Engineering &amp; Property Development Unit', 2, NULL),
(57, 'Finance Unit', 2, NULL),
(58, 'GMD Office', 2, NULL),
(59, 'Head Office Administration Unit', 2, NULL),
(60, 'Human Resource Unit', 2, NULL),
(61, 'Information Technology Unit', 2, NULL),
(62, 'Internal Audit Unit', 2, NULL),
(63, 'Land Administration Unit', 2, NULL),
(64, 'Legal Unit', 2, NULL),
(65, 'Head of Plantation Unit', 2, NULL),
(66, 'Plantation Advisory &amp; Agri Business Unit', 2, NULL),
(67, 'Sustainability Unit', 2, NULL),
(68, 'Regional Office Sandakan', 5, NULL),
(69, 'Gomantong Estate', 5, NULL),
(70, 'Green Estate', 5, NULL),
(71, 'Sungai Pin Estate', 5, NULL),
(72, 'Sungai Menanggol Estate', 5, NULL),
(73, 'Sepagaya Estate', 5, NULL),
(74, 'Tongod Nucleus Estate', 5, NULL),
(75, 'Luboh Estate', 5, NULL),
(76, 'Sungai-Sungai Estate', 5, NULL),
(77, 'Sepagaya Mill', 5, NULL),
(78, 'ARAS- Sandakan', 5, NULL),
(79, 'Coconut Seed Garden', 5, NULL),
(80, 'Tongod Estate', 5, NULL),
(81, 'Sawit POIC', 5, NULL),
(82, 'Sawit Kinabalu Jetty', 5, NULL),
(83, 'Sawit Bulkers', 5, NULL),
(84, 'Regional Office Lahad Datu', 3, NULL),
(85, 'Matamba Estate', 3, NULL),
(86, 'Seberang Estate', 3, NULL),
(87, 'Mensuli Estate', 3, NULL),
(88, 'Sandau Estate', 3, NULL),
(89, 'Boonrich Estate', 3, NULL),
(90, 'ARAS- Lahad Datu', 3, NULL),
(91, 'Oscar Kinabalu Estate', 3, NULL),
(92, 'Sandau Mill', 3, NULL),
(93, 'Sebrang Mill', 3, NULL),
(94, 'Mechanical Unit', 3, NULL),
(95, 'Sawit Ecoshield Sdn Bhd', 3, NULL),
(96, 'Bagahak 1 Estate', 3, NULL),
(97, 'Bagahak 2 Estate', 3, NULL),
(98, 'Bagahak 3 Estate', 3, NULL),
(99, 'Sawit Kinabalu Farm East', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `org_lists`
--

CREATE TABLE `org_lists` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of a list',
  `user` int(11) NOT NULL COMMENT ' Identifier of skg-lms user owning the list',
  `name` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Custom lists of employees are an alternative to organization';

-- --------------------------------------------------------

--
-- Table structure for table `org_lists_employees`
--

CREATE TABLE `org_lists_employees` (
  `list` int(11) NOT NULL COMMENT 'Id of the list',
  `user` int(11) NOT NULL COMMENT 'id of an employee',
  `orderlist` int(11) NOT NULL COMMENT 'order in the list'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Children table of org_lists (custom list of employees)';

-- --------------------------------------------------------

--
-- Table structure for table `overtime`
--

CREATE TABLE `overtime` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of the overtime request',
  `employee` int(11) NOT NULL COMMENT 'Employee requesting the OT',
  `date` date NOT NULL COMMENT 'Date when the OT was done',
  `duration` decimal(10,3) NOT NULL COMMENT 'Duration of the OT',
  `cause` text NOT NULL COMMENT 'Reason why the OT was done',
  `status` int(11) NOT NULL COMMENT 'Status of OT (Planned, Requested, Accepted, Rejected)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Overtime worked (extra time)';

-- --------------------------------------------------------

--
-- Table structure for table `parameters`
--

CREATE TABLE `parameters` (
  `name` varchar(32) NOT NULL,
  `scope` int(11) NOT NULL COMMENT 'Either global(0) or user(1) scope',
  `value` text NOT NULL COMMENT 'PHP/serialize value',
  `entity_id` text DEFAULT NULL COMMENT 'Entity ID (eg. user id) to which the parameter is applied'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application parameters';

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of the position',
  `name` varchar(64) NOT NULL COMMENT 'Name of the position',
  `description` text NOT NULL COMMENT 'Description of the position'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Position (job position) in the organization';

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `name`, `description`) VALUES
(1, 'E1', 'Senior Executive'),
(2, 'E2', 'Executive'),
(3, 'M1', 'Group Managing Director'),
(4, 'M2', 'Deputy MD'),
(5, 'M3', 'Head Of Division'),
(6, 'M4', 'General Manager'),
(7, 'M5', 'Senior Manager'),
(8, 'M6', 'Manager'),
(9, 'NE1', 'Supervisor'),
(10, 'NE2', 'Senior Assistant'),
(11, 'NE3', 'Assistant'),
(12, 'NE4', 'General Staff');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles in the application (system table)';

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'User'),
(3, 'HR');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Status of the Leave Request (system table)';

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`id`, `name`) VALUES
(1, 'Planned'),
(2, 'Requested'),
(3, 'Accepted'),
(4, 'Rejected'),
(5, 'Cancellation'),
(6, 'Canceled');

-- --------------------------------------------------------

--
-- Table structure for table `types`
--

CREATE TABLE `types` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of the type',
  `name` varchar(128) NOT NULL COMMENT 'Name of the leave type',
  `acronym` varchar(10) DEFAULT NULL COMMENT 'Acronym of the leave type',
  `deduct_days_off` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Deduct days off when computing the balance of the leave type'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of leave types (LoV table)';

--
-- Dumping data for table `types`
--

INSERT INTO `types` (`id`, `name`, `acronym`, `deduct_days_off`) VALUES
(0, '', '', 0),
(1, 'Annual Leave', 'AL', 0),
(2, 'Sick Leave', 'SL', 0),
(3, 'Leave Bank', 'LB', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of the user',
  `firstname` varchar(255) DEFAULT NULL COMMENT 'First name',
  `lastname` varchar(255) DEFAULT NULL COMMENT 'Last name',
  `login` varchar(255) DEFAULT NULL COMMENT 'Identfier used to login (can be an email address)',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email address',
  `password` varchar(512) DEFAULT NULL COMMENT 'Password encrypted with BCRYPT or a similar method',
  `role` int(11) DEFAULT NULL COMMENT 'Role of the employee (binary mask). See table roles.',
  `manager` int(11) DEFAULT NULL COMMENT 'Employee validating the requests of the employee',
  `country` int(11) DEFAULT NULL COMMENT 'Country code (for later use)',
  `organization` int(11) DEFAULT 0 COMMENT 'Entity where the employee has a position',
  `contract` int(11) DEFAULT NULL COMMENT 'Contract of the employee',
  `position` int(11) DEFAULT NULL COMMENT 'Position of the employee',
  `location` int(11) DEFAULT NULL COMMENT 'Location of the employee',
  `datehired` date DEFAULT NULL COMMENT 'Date hired / Started',
  `identifier` varchar(64) NOT NULL COMMENT 'Internal/company identifier',
  `language` varchar(5) NOT NULL DEFAULT 'en' COMMENT 'Language ISO code',
  `ldap_path` varchar(1024) DEFAULT NULL COMMENT 'LDAP Path for complex authentication schemes',
  `active` tinyint(1) DEFAULT 1 COMMENT 'Is user active',
  `timezone` varchar(255) DEFAULT NULL COMMENT 'Timezone of user',
  `calendar` varchar(255) DEFAULT NULL COMMENT 'External Calendar address',
  `random_hash` varchar(24) DEFAULT NULL COMMENT 'Obfuscate public URLs',
  `user_properties` text DEFAULT NULL COMMENT 'Entity ID (eg. user id) to which the parameter is applied',
  `picture` blob DEFAULT NULL COMMENT 'Profile picture of user for tabular calendar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of employees / users having access to skg-lms';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `login`, `email`, `password`, `role`, `manager`, `country`, `organization`, `contract`, `position`, `location`, `datehired`, `identifier`, `language`, `ldap_path`, `active`, `timezone`, `calendar`, `random_hash`, `user_properties`, `picture`) VALUES
(1, 'Super ', 'Admin', 'SA', 'superadmin@email.com', '$2a$08$7lz6h2QY9PqLJvUy6RhwfusbPecUMaQhaQQZA.uOsaMtDAxmXkBvG', 8, 1, NULL, 0, 0, 1, NULL, '2000-01-01', 'Super Admin', 'en', NULL, 1, NULL, NULL, '5g5VUm5ZKf5TkK08yMtuKxe5', NULL, NULL),
(2, 'HR', 'Administrator', 'hr', 'hr@email.com', '$2a$08$FuNqpgbyknoztrkBYcWWO.XLpsYSmbNQow3zS7m1FOZgoCWo9LJv6', 3, 2, NULL, 1, 3, 2, 1, '2024-05-01', 'HR', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'HSDvZh_nopJDvekzoSkFQOug', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actions`
--
ALTER TABLE `actions`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dayoffs`
--
ALTER TABLE `dayoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `contract` (`contract`);

--
-- Indexes for table `delegations`
--
ALTER TABLE `delegations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `entitleddays`
--
ALTER TABLE `entitleddays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract` (`contract`),
  ADD KEY `employee` (`employee`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `excluded_types`
--
ALTER TABLE `excluded_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `employee` (`employee`);

--
-- Indexes for table `leaves_history`
--
ALTER TABLE `leaves_history`
  ADD PRIMARY KEY (`change_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `change_date` (`change_date`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`access_token`);

--
-- Indexes for table `oauth_applications`
--
ALTER TABLE `oauth_applications`
  ADD KEY `user` (`user`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `oauth_authorization_codes`
--
ALTER TABLE `oauth_authorization_codes`
  ADD PRIMARY KEY (`authorization_code`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `oauth_jwt`
--
ALTER TABLE `oauth_jwt`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`refresh_token`);

--
-- Indexes for table `oauth_users`
--
ALTER TABLE `oauth_users`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `organization`
--
ALTER TABLE `organization`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `org_lists`
--
ALTER TABLE `org_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `org_lists_user` (`user`);

--
-- Indexes for table `org_lists_employees`
--
ALTER TABLE `org_lists_employees`
  ADD KEY `org_list_id` (`list`);

--
-- Indexes for table `overtime`
--
ALTER TABLE `overtime`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `employee` (`employee`);

--
-- Indexes for table `parameters`
--
ALTER TABLE `parameters`
  ADD KEY `param_name` (`name`,`scope`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `types`
--
ALTER TABLE `types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager` (`manager`),
  ADD KEY `organization` (`organization`),
  ADD KEY `contract` (`contract`),
  ADD KEY `position` (`position`),
  ADD KEY `location` (`location`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of a contract', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dayoffs`
--
ALTER TABLE `dayoffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `delegations`
--
ALTER TABLE `delegations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of delegation', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `entitleddays`
--
ALTER TABLE `entitleddays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of an entitlement', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `excluded_types`
--
ALTER TABLE `excluded_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of exclusion', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the leave request', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `leaves_history`
--
ALTER TABLE `leaves_history`
  MODIFY `change_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `organization`
--
ALTER TABLE `organization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the department', AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `org_lists`
--
ALTER TABLE `org_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of a list';

--
-- AUTO_INCREMENT for table `overtime`
--
ALTER TABLE `overtime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the overtime request';

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the position', AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `types`
--
ALTER TABLE `types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the type', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the user', AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
