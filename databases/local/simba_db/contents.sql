USE `%( DB_NAME )%`;



INSERT INTO `hierarchy` (`id`, `type`, `color`, `datetime.insert`) VALUES
(1, 'root', '#b37528', CURRENT_TIMESTAMP),
(2, 'admin', '#4b884b', CURRENT_TIMESTAMP),
(3, 'viewer', '#5c9ad0', CURRENT_TIMESTAMP);