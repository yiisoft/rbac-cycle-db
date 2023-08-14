CREATE TABLE "public"."yii_rbac_cycle_db_auth_item" (
  "name" character varying (128) NOT NULL,
  "type" character varying (10) NOT NULL,
  "description" character varying (191) NULL,
  "ruleName" character varying (64) NULL,
  "createdAt" integer NOT NULL,
  "updatedAt" integer NOT NULL,
  PRIMARY KEY ("name")
);
CREATE INDEX "idx-yii_rbac_cycle_db_auth_item-type" ON "public"."yii_rbac_cycle_db_auth_item" ("type");
CREATE TABLE "public"."yii_rbac_cycle_db_auth_item_child" (
  "parent" character varying (128) NOT NULL,
  "child" character varying (128) NOT NULL,
  PRIMARY KEY ("parent", "child"),
  CONSTRAINT "fk-yii_rbac_cycle_db_auth_item_child-parent" FOREIGN KEY ("parent") REFERENCES "yii_rbac_cycle_db_auth_item" ("name") ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT "fk-yii_rbac_cycle_db_auth_item_child-child" FOREIGN KEY ("child") REFERENCES "yii_rbac_cycle_db_auth_item" ("name") ON DELETE NO ACTION ON UPDATE NO ACTION
);
CREATE INDEX "idx-yii_rbac_cycle_db_auth_item_child-parent" ON "public"."yii_rbac_cycle_db_auth_item_child" ("parent");
CREATE INDEX "idx-yii_rbac_cycle_db_auth_item_child-child" ON "public"."yii_rbac_cycle_db_auth_item_child" ("child");
CREATE TABLE "public"."yii_rbac_cycle_db_auth_assignment" (
  "itemName" character varying (128) NOT NULL,
  "userId" character varying (128) NOT NULL,
  "createdAt" integer NOT NULL,
  PRIMARY KEY ("itemName", "userId")
);
