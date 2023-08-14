CREATE TABLE "yii_rbac_cycle_db_auth_item" (
  "name" text (128) NOT NULL,
  "type" text (10) NOT NULL,
  "description" text (191) NULL,
  "ruleName" text (64) NULL,
  "createdAt" integer NOT NULL,
  "updatedAt" integer NOT NULL,
  PRIMARY KEY ("name")
);
CREATE INDEX IF NOT EXISTS "idx-yii_rbac_cycle_db_auth_item-type" ON "yii_rbac_cycle_db_auth_item" ("type");
CREATE TABLE "yii_rbac_cycle_db_auth_item_child" (
  "parent" text (128) NOT NULL,
  "child" text (128) NOT NULL,
  PRIMARY KEY ("parent", "child"),
  FOREIGN KEY ("parent") REFERENCES "yii_rbac_cycle_db_auth_item" ("name") ON DELETE NO ACTION ON UPDATE NO ACTION,
  FOREIGN KEY ("child") REFERENCES "yii_rbac_cycle_db_auth_item" ("name") ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE INDEX IF NOT EXISTS "idx-yii_rbac_cycle_db_auth_item_child-parent" ON "yii_rbac_cycle_db_auth_item_child" ("parent");
CREATE INDEX IF NOT EXISTS "idx-yii_rbac_cycle_db_auth_item_child-child" ON "yii_rbac_cycle_db_auth_item_child" ("child");
CREATE TABLE "yii_rbac_cycle_db_auth_assignment" (
  "itemName" text (128) NOT NULL,
  "userId" text (128) NOT NULL,
  "createdAt" integer NOT NULL,
  PRIMARY KEY ("itemName", "userId")
);
