CREATE TABLE `yii_rbac_cycle_db_item` (
 `name` varchar (128) NOT NULL,
 `type` varchar (10) NOT NULL,
 `description` varchar (191) NULL,
 `ruleName` varchar (64) NULL,
 `createdAt` int(11) NOT NULL,
 `updatedAt` int(11) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE InnoDB;
CREATE INDEX `idx-yii_rbac_cycle_db_item-type` ON `yii_rbac_cycle_db_item` (`type`);
CREATE TABLE `yii_rbac_cycle_db_item_child` (
  `parent` varchar (128) NOT NULL,
  `child` varchar (128) NOT NULL,
  PRIMARY KEY (`parent`, `child`),
  CONSTRAINT `fk-yii_rbac_cycle_db_item_child-parent` FOREIGN KEY (`parent`) REFERENCES `yii_rbac_cycle_db_item` (`name`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk-yii_rbac_cycle_db_item_child-child` FOREIGN KEY (`child`) REFERENCES `yii_rbac_cycle_db_item` (`name`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE InnoDB;
CREATE INDEX `idx-yii_rbac_cycle_db_item_child-parent` ON `yii_rbac_cycle_db_item_child` (`parent`);
CREATE INDEX `idx-yii_rbac_cycle_db_item_child-child` ON `yii_rbac_cycle_db_item_child` (`child`);
CREATE TABLE `yii_rbac_cycle_db_assignment` (
  `itemName` varchar (128) NOT NULL,
  `userId` varchar (128) NOT NULL,
  `createdAt` int(11) NOT NULL,
  PRIMARY KEY (`itemName`, `userId`)
) ENGINE InnoDB;
