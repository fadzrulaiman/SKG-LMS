-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2024 at 10:02 AM
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
('mn73bd4d427p2ovtj3fl3t6mbn8gk1cg', '10.13.1.51', 1713170101, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333137303130313b6c6173745f706167657c733a33393a2268747470733a2f2f31302e31332e312e35312f534b472d4c4d532f68722f656d706c6f79656573223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a225163717a494f7673676c357658644173352b3764536f72623163733d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b),
('acg2iipmp2d9sfrabteh6iof0ie9e235', '10.13.1.51', 1713170636, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333137303633363b6c6173745f706167657c733a33393a2268747470733a2f2f31302e31332e312e35312f534b472d4c4d532f6f7267616e697a6174696f6e223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a225163717a494f7673676c357658644173352b3764536f72623163733d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b),
('8lee4df1iqc6idh6p0a8s1i445ao2ui4', '10.13.1.51', 1713170666, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333137303633363b6c6173745f706167657c733a34303a2268747470733a2f2f31302e31332e312e35312f534b472d4c4d532f6c65617665732f637265617465223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a225163717a494f7673676c357658644173352b3764536f72623163733d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b),
('vk7i291b0pjmuc769oe3pii8oqa3cqev', '::1', 1713228790, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333232383739303b6c6173745f706167657c733a32353a22687474703a2f2f6c6f63616c686f73742f534b472d4c4d532f223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a2248343269356c354b4d7173324533452f4a4441577954516c4f513d3d223b),
('buun6ln2nkho7dbekq7472sd7q9687hj', '10.13.110.60', 1713229523, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333232393532333b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a226833562b6f724e354363786433383077456a6433687377376c413d3d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34313a2268747470733a2f2f31302e31332e3131302e36302f534b472d4c4d532f75736572732f656469742f33223b6c6173745f706167655f706172616d737c733a303a22223b),
('catum3s7kba2ft7h9fe8m9alqr6no8k9', '10.13.110.60', 1713229831, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333232393833313b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a226833562b6f724e354363786433383077456a6433687377376c413d3d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34313a2268747470733a2f2f31302e31332e3131302e36302f534b472d4c4d532f68722f656d706c6f79656573223b6c6173745f706167655f706172616d737c733a303a22223b),
('r90t8t5df1ss3pth43sfug54t7vfa481', '10.13.110.60', 1713229866, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333232393833313b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32383a226833562b6f724e354363786433383077456a6433687377376c413d3d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34383a2268747470733a2f2f31302e31332e3131302e36302f534b472d4c4d532f656e7469746c6564646179732f757365722f32223b6c6173745f706167655f706172616d737c733a303a22223b),
('tfdu1ui74r7l8po3j1bur3feshibcfi1', '10.13.1.61', 1713231905, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233313930353b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22677279636b43344253773457486b513d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33333a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c6561766573223b6c6173745f706167655f706172616d737c733a303a22223b),
('lpjto71fhap9up3onb24jd8kv83o0dpj', '10.13.1.61', 1713230381, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233303337313b6c6173745f706167657c733a33353a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f7265717565737473223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32303a222b6144745a364647724d41455461305a6267453d223b6c6f67696e7c733a393a2269746d616e61676572223b69647c693a333b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a373a224d414e41474552223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a226d7669594b673574585242444161302d4f735f6e4b30385f223b6c6f676765645f696e7c623a313b),
('b4nsfl3nfrofou1jlind71boaiaav7ka', '10.13.1.61', 1713232208, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233323230383b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22677279636b43344253773457486b513d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34383a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c65617665732f63616e63656c6c6174696f6e2f31223b6c6173745f706167655f706172616d737c733a303a22223b6d73677c733a35313a225468652063616e63656c6c6174696f6e207265717565737420686173206265656e207375636365737366756c6c792073656e74223b5f5f63695f766172737c613a313a7b733a333a226d7367223b733a333a226e6577223b7d),
('hg81drva5n1fl0kp16g8haaosuv2vf28', '10.13.1.61', 1713233489, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233333438393b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22677279636b43344253773457486b513d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33333a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c6561766573223b6c6173745f706167655f706172616d737c733a303a22223b),
('dlrqo0g3qcuucmiai5kkquoqa547mube', '10.13.1.61', 1713233800, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233333830303b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22677279636b43344253773457486b513d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34323a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f7265706f7274732f62616c616e6365223b6c6173745f706167655f706172616d737c733a303a22223b),
('dobb2mrmk4aumh9u3vr704badvgqe9lu', '10.13.1.61', 1713234113, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233343131333b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22677279636b43344253773457486b513d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34313a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f61646d696e2f73657474696e6773223b6c6173745f706167655f706172616d737c733a303a22223b),
('vn110cful2b4i2p63l1j6mpim7etm1ci', '10.13.1.61', 1713234544, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233343534343b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a22677279636b43344253773457486b513d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33333a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c6561766573223b6c6173745f706167655f706172616d737c733a303a22223b),
('rdc1407al0uo35vrjm098ak85vah6sug', '10.13.1.61', 1713234981, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233343938313b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a226a44654b70537a7a7276555a52673d3d223b6c6f67696e7c733a323a225341223b69647c693a313b66697273746e616d657c733a363a22537570657220223b6c6173746e616d657c733a353a2241646d696e223b69735f6d616e616765727c623a313b69735f61646d696e7c623a303b69735f68727c623a313b6d616e616765727c693a313b72616e646f6d5f686173687c733a32343a2235673556556d355a4b6635546b4b3038794d74754b786535223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33393a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f75736572732f637265617465223b6c6173745f706167655f706172616d737c733a303a22223b),
('pqeb09o5ecvv1v7edo04lk0esblsahit', '10.13.1.61', 1713235967, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233353936373b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a226a44654b70537a7a7276555a52673d3d223b6c6f67696e7c733a323a225341223b69647c693a313b66697273746e616d657c733a363a22537570657220223b6c6173746e616d657c733a353a2241646d696e223b69735f6d616e616765727c623a313b69735f61646d696e7c623a303b69735f68727c623a313b6d616e616765727c693a313b72616e646f6d5f686173687c733a32343a2235673556556d355a4b6635546b4b3038794d74754b786535223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33393a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f75736572732f637265617465223b6c6173745f706167655f706172616d737c733a303a22223b),
('3qr3jo2sm2ko787t8c9c49s8ocva0s1b', '10.13.1.61', 1713236505, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233363530353b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32303a2262506643696a71486b73434266504a53524b633d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a34303a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c65617665732f637265617465223b6c6173745f706167655f706172616d737c733a303a22223b),
('vtv8k29p7u8bhll74h6rcu8ag1trso7u', '10.13.1.61', 1713236550, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233363530353b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32303a2262506643696a71486b73434266504a53524b633d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33333a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c6561766573223b6c6173745f706167655f706172616d737c733a303a22223b6d73677c733a34373a22546865206c65617665207265717565737420686173206265656e207375636365737366756c6c792064656c65746564223b5f5f63695f766172737c613a313a7b733a333a226d7367223b733a333a226f6c64223b7d),
('836nu2aorviepiruu5ddmho0hqrdbbk0', '10.13.1.61', 1713236809, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333233363536333b6c6173745f706167657c733a33393a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f75736572732f637265617465223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a226f535732445074775748714a5733773d223b6c6f67696e7c733a373a2269747374616666223b69647c693a343b66697273746e616d657c733a323a224954223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a303b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a333b72616e646f6d5f686173687c733a32343a223745373844516f74636a64684745553953687674554b4c6b223b6c6f676765645f696e7c623a313b),
('7tkb11dg0kfjqduoi91b27j3jq8pt6ta', '10.13.1.61', 1713249608, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333234393630383b6c6173745f706167657c733a33333a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f6c6561766573223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32303a22786577677052794b5666454f55796e64324e4d34223b),
('7j73rs10ahblvj2a1k01u7amifp3ievb', '10.13.1.61', 1713249928, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333234393932383b6c6173745f706167657c733a34323a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f63616c656e6461722f796561722f32223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a227a7661544c6847517362564a3833514a223b6c6f67696e7c733a323a225341223b69647c693a313b66697273746e616d657c733a363a22537570657220223b6c6173746e616d657c733a353a2241646d696e223b69735f6d616e616765727c623a313b69735f61646d696e7c623a303b69735f68727c623a313b6d616e616765727c693a313b72616e646f6d5f686173687c733a32343a2235673556556d355a4b6635546b4b3038794d74754b786535223b6c6f676765645f696e7c623a313b),
('n9afvl8pli6gtlsi7shkat0urbjuer7o', '10.13.1.61', 1713250068, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333234393932383b6c6173745f706167657c733a33313a2268747470733a2f2f31302e31332e312e36312f534b472d4c4d532f686f6d65223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a227a7661544c6847517362564a3833514a223b6c6f67696e7c733a323a225341223b69647c693a313b66697273746e616d657c733a363a22537570657220223b6c6173746e616d657c733a353a2241646d696e223b69735f6d616e616765727c623a313b69735f61646d696e7c623a303b69735f68727c623a313b6d616e616765727c693a313b72616e646f6d5f686173687c733a32343a2235673556556d355a4b6635546b4b3038794d74754b786535223b6c6f676765645f696e7c623a313b),
('8376eks9bhggc5bga6pr83b52jr5s7ln', '::1', 1713254005, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333235343030343b6c6173745f706167657c733a32353a22687474703a2f2f6c6f63616c686f73742f534b472d4c4d532f223b6c6173745f706167655f706172616d737c733a303a22223b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a31363a226950425539587647427131777a673d3d223b),
('9lkaip475fjud41g9eigfj4vnbg663kp', '10.13.1.104', 1713254302, 0x5f5f63695f6c6173745f726567656e65726174657c693a313731333235343031343b6c616e67756167655f636f64657c733a323a22656e223b6c616e67756167657c733a373a22656e676c697368223b73616c747c733a32343a225147504e5a63502b634e474a772b34553857372b61694d3d223b6c6f67696e7c733a323a226872223b69647c693a323b66697273746e616d657c733a323a224852223b6c6173746e616d657c733a353a225354414646223b69735f6d616e616765727c623a313b69735f61646d696e7c623a313b69735f68727c623a313b6d616e616765727c693a323b72616e646f6d5f686173687c733a32343a22363242384e4c4333306d776257634b5f5f5f4167696e5544223b6c6f676765645f696e7c623a313b6c6173745f706167657c733a33333a2268747470733a2f2f31302e31332e312e3130342f534b472d4c4d532f7573657273223b6c6173745f706167655f706172616d737c733a303a22223b);

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
(2, 'Manager', 'Department Manager');

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
(0, 'Annual Leave', 'AL', 0),
(1, 'Sick Leave', 'SL', 0),
(2, 'Leave Bank', '', 0);

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
(1, 'Super ', 'Admin', 'SA', 'superadmin@email.com', '$2a$08$7lz6h2QY9PqLJvUy6RhwfusbPecUMaQhaQQZA.uOsaMtDAxmXkBvG', 8, 1, NULL, 0, NULL, 1, '2000-01-01', 'Super Admin', 'en', NULL, 1, NULL, NULL, '5g5VUm5ZKf5TkK08yMtuKxe5', NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of a contract', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dayoffs`
--
ALTER TABLE `dayoffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id of exclusion';

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the position', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `types`
--
ALTER TABLE `types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the type', AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier of the user', AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
