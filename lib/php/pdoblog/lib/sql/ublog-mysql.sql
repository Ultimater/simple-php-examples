CREATE DATABASE IF NOT EXISTS `ublog`;

GRANT ALL PRIVILEGES ON `ublog`.*
  TO 'ublog'@'localhost'
  IDENTIFIED BY 'changeme';

USE `ublog`;

CREATE TABLE `blog` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO blog (title, content) VALUES ('First Blog Post', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi.');
