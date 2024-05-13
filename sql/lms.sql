-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2024 at 08:45 AM
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
(0, 'Executive before 13 March 2006', '01/01', '12/31', NULL, NULL, 0),
(1, 'Non-Executive before 13 March 2006', '01/01', '12/31', NULL, NULL, 0),
(2, 'Executive after 13 March 2006', '01/01', '12/31', NULL, NULL, 0),
(3, 'Non-Executive after 13 March 2006', '01/01', '12/31', NULL, NULL, 0);


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
  `user` int(11) NOT NULL COMMENT 'Identifier of Jorani user',
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
(0, 'SKG', -1, NULL),
(1, 'IT', 0, NULL),
(2, 'HR', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `org_lists`
--

CREATE TABLE `org_lists` (
  `id` int(11) NOT NULL COMMENT 'Unique identifier of a list',
  `user` int(11) NOT NULL COMMENT ' Identifier of Jorani user owning the list',
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
(1, 'Employee', 'Department Employee'),
(2, 'Manager', 'Department Manager'),
(3, 'Senior Manager', '');

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
(3, 'Leave Bank', '', 0);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='List of employees / users having access to Jorani';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `login`, `email`, `password`, `role`, `manager`, `country`, `organization`, `contract`, `position`, `datehired`, `identifier`, `language`, `ldap_path`, `active`, `timezone`, `calendar`, `random_hash`, `user_properties`, `picture`) VALUES
(1, 'Super ', 'Admin', 'SA', 'superadmin@email.com', '$2a$08$7lz6h2QY9PqLJvUy6RhwfusbPecUMaQhaQQZA.uOsaMtDAxmXkBvG', 8, 1, NULL, 0, NULL, 1, '2000-01-01', 'Super Admin', 'en', NULL, 1, NULL, NULL, '5g5VUm5ZKf5TkK08yMtuKxe5', NULL, NULL),
(5, 'Tan Min', 'Choon', 'Tan', 'tan@email.com', '$2a$08$aTD1rNlnseRXem/h0G.yY./b4jdGKQhab/XARTiDKsm.swKXJxTZi', 2, 5, NULL, 1, 3, 3, '2024-01-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'n3vgzEPLRrZAL5npJkygzTYk', NULL, NULL),
(6, 'Romina Ewol', 'Mujie', 'Romina', 'romina@email.com', '$2a$08$juHi60YUCon.ow2KlluBeuNv1Qop.xDsxvjquz3H4gC8XZsMW3.qe', 2, 5, NULL, 1, 3, 2, '2024-02-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'ORiMJMB-4-ex6GoQDxR6zT78', NULL, NULL),
(7, 'Teo Tiong ', 'Tze', 'Megan', 'megan@email.com', '$2a$08$UcJPtjOftib3DLMN/zlkf.73c/VdYZ.0ZqirUetcrDOE.dqv3uUAe', 2, 5, NULL, 1, 3, 2, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'F5RX9fIXM64R2DB_1yiNsvyj', NULL, NULL),
(8, 'Poong Tchu ', 'Chuan', 'Sam', 'sam@email.com', '$2a$08$4oFRUKt1xDEygn8uXwbduuVSw0cpC7lpw82KvSf/rYGcsTh96r7bC', 2, 5, NULL, 1, 3, 2, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'JD9JmrSp6m-XspQdJX58Harh', NULL, NULL),
(9, 'Jasneh', 'Abdullah', 'Jasneh', 'jasneh@email.com', '$2a$08$8Lr.KgWOT7WmIn8srb7hKO0cx5jl9PgckHD2wtep/8krQmduL6Zbi', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'Wg5BOARB2BGZHLH_WIoA87IM', NULL, NULL),
(10, 'Siti Khadizah', 'Waly', 'siti', 'siti@email.com', '$2a$08$Hv3Zy.E/61gedg5dOmTDZO0N.nVyir23EwO/mADGPJdQhmxat0LOu', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'zKKJkjoGtsOa2Hl0fmhaDiQR', NULL, NULL),
(11, 'Rindey', 'Jlee', 'rindey', 'rindey@email.com', '$2a$08$T6MtlFX0J3ST403IwKe63.HHyxfcGwD/9vnsdYE4kIgMxVWemFeWy', 2, 5, NULL, 1, 3, 1, NULL, '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'z6nhkUUW8nnxncAoIVH3vojS', NULL, NULL),
(12, 'Teo', 'Ricky', 'ricky', 'ricky@email.com', '$2a$08$QLssNuK9TydUSiu8xYL9iea00t8lpvIflIYlHVFU87/CUr2lInNH6', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, '25LGfH1XqFHxYgs2x6a6d2Bh', NULL, NULL),
(13, 'Georgey', 'Resot', 'georgey', 'georgey@emai.com', '$2a$08$zq5wVBNGbG1d0Kt0a7LrBOfAxEmXgDDKAYUTlhLCPKh9ptonxY7JK', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, '0CHUrdzRDC_U9dwQok-o5LMe', NULL, NULL),
(14, 'Melinda Lee', 'Yen Fen', 'melinda', 'melinda@email.com', '$2a$08$jxuFpzNP/0nGbJyzXsm0eO0Fchc17ZsvJis/Sm20RULB623gq3puS', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'HVIu6_mwSeteakLGjAHVsEo1', NULL, NULL),
(15, 'Ruzaini ', 'Mohd Dalin', 'ruzaini', 'ruzaini', '$2a$08$62RHamLJVTTdJ9E7T.0OLucyJ.gJ/AIvdjKmtcxEaRGzfNjMEpO2C', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, '9OFOq4aTEU7U-9lgTzq2cPxX', NULL, NULL),
(16, 'Zareena Hanis', 'Red', 'zareena', 'zareena@email.com', '$2a$08$cppY6RQLDK5OQYZZHs2vZ.S/OAv7hvLWBmIJQUfMX6PHdo5FDZlw6', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'ir-6RbviPFYAAma_kEwieAG5', NULL, NULL),
(17, 'Ezhan Efizi', 'Nafs', 'ezhan', 'ezhan@email.com', '$2a$08$n8l/lmR.AnLLrXbdyr5Js.Eia1sgvhA7oVOlsg1Ot9a6BvrX9XiwK', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'SiD4PO2oQ2vX8aor2GXtGJOC', NULL, NULL),
(18, 'Nurliah', 'Mohd Jaafar', 'nurliah', 'nurliah@email.com', '$2a$08$0XgiWvqTnK3D.38.0I3NyOodtzz9CCnExDsyeXnUPGfzmoHmZPBs6', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'm00U2hb9sgdL8Qi9cNu6pVf5', NULL, NULL),
(19, 'Saidal ', 'M Jalil', 'saidal', 'saidal@email.com', '$2a$08$swBOHUm89ZDyJ0ABzCfZa.xsQDv4kBkUBLTYVm8qVmFKBhhS75MY2', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, '6Ugq6MFg-8tzvskIVNKQFI15', NULL, NULL),
(20, 'Poh Mei', 'Yean', 'poh', 'poh@email.com', '$2a$08$Z1w2Iy58AJp.n7F4UccVi.1.wTDiWBYLuq.V50RIv5RnCu17.sSn2', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'y4Yqd2P2Jw1KevsOqPlU8nzT', NULL, NULL),
(21, 'Racheal Albert', 'Yeap', 'racheal', 'racheal@email.com', '$2a$08$ybMI3inbkeD5Xexm7AdOOOLe.Mla239oa9zX8me4Hk/EMDiqg6ncm', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'UI4Mi-TWg_5dr3cdqBpzuYAG', NULL, NULL),
(22, 'Azhad', 'Muhammad', 'azhad', 'azhad@email.com', '$2a$08$TGmmM8/YVxdxzqED0ARvYOY6JYJYcc1GIsEbBneVwYpzk63sjgEo2', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'RxCoIlh6kg6ZWiuO_vqxuMns', NULL, NULL),
(23, 'Sean Casey', 'Linggian', 'sean', 'sean@email.com', '$2a$08$rHhGzHfVkuhbYkCfpybSzO6NY7He6g0Ak9z77UA/VW9Mj62H.SGLq', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'iUQxGhpIbl_cCHO4J1UFXX0i', NULL, NULL),
(24, 'Nurallfallinah', 'Jawh', 'nurallfallinah', 'nurallfallinah@email.com', '$2a$08$LDv5LMi5H1IUMdyvGQPDQu.oCPT071YYo/52SfVfvaNLBSVkby3ru', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'Jc9hO71VziQzxMatwU0TTjDi', NULL, NULL),
(25, 'Natasha', 'Mohd Zulk', 'natasha', 'natasha@email.com', '$2a$08$Q5aTeJfG2PoiCsxRnw4CSOgZZ0lIbytCFDeIjZ9mRYIlPb27NO4N.', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, '9Q-uPEAmIc7C6Jgr7NlYrqFd', NULL, NULL),
(26, 'Fadzrul Aiman', 'Madzlan', 'fadzrul', 'fadzrul@email.com', '$2a$08$1NxyYcjGA.sNSou91nLcb.WoTogt9NGxhzs.vrotQPs9Rc.Mw3pme', 2, 5, NULL, 1, 3, 1, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'a1-c8egkNefNQ-6md_q52UZo', NULL, NULL),
(27, 'hr', 'personnel', 'hr', 'hr@email.com', '$2a$08$U5zQXiTf4stBZX/Vy.D9leqYLjdf4T9t8wRV18nL8v5gATvk1uBOC', 3, 27, NULL, 2, 3, 2, '2024-04-01', '', 'en', NULL, 1, 'Asia/Kuala_Lumpur', NULL, 'hTFToSnemWDv3shcx03xKbrR', NULL, NULL);

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
  ADD KEY `position` (`position`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of a contract', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `dayoffs`
--
ALTER TABLE `dayoffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT for table `delegations`
--
ALTER TABLE `delegations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of delegation';

--
-- AUTO_INCREMENT for table `entitleddays`
--
ALTER TABLE `entitleddays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of an entitlement', AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `excluded_types`
--
ALTER TABLE `excluded_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of exclusion', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the leave request', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leaves_history`
--
ALTER TABLE `leaves_history`
  MODIFY `change_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `organization`
--
ALTER TABLE `organization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the department', AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the position', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `types`
--
ALTER TABLE `types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the type', AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the user', AUTO_INCREMENT=28;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
