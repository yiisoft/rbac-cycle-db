CREATE TABLE `auth_item` (
 `name` varchar (128) NOT NULL,
 `type` varchar (10) NOT NULL,
 `description` varchar (191) NULL,
 `ruleName` varchar (64) NULL,
 `createdAt` int(11) NOT NULL,
 `updatedAt` int(11) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE InnoDB;
CREATE INDEX `idx-auth_item-type` ON `auth_item` (`type`);
CREATE TABLE `auth_item_child` (
  `parent` varchar (128) NOT NULL,
  `child` varchar (128) NOT NULL,
  PRIMARY KEY (`parent`, `child`),
  CONSTRAINT `fk-auth_item_child-parent` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk-auth_item_child-child` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE InnoDB;
CREATE INDEX `idx-auth_item_child-parent` ON `auth_item_child` (`parent`);
CREATE INDEX `idx-auth_item_child-child` ON `auth_item_child` (`child`);
CREATE TABLE `auth_assignment` (
  `itemName` varchar (128) NOT NULL,
  `userId` varchar (128) NOT NULL,
  `createdAt` int(11) NOT NULL,
  PRIMARY KEY (`itemName`, `userId`)
) ENGINE InnoDB;
