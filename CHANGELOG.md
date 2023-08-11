# Yii RBAC Cycle Database Storage Change Log

## 3.0.0 under development

- New #23: Add `DbSchemaManager` and SQL dumps for working with schema (@arogachev)
- Chg #23: Remove CLI dependencies (@arogachev)
- Enh #60: Decouple storages (@arogachev)
- Enh #60: Decouple storages: adjust database tables' schema (@arogachev)
- Enh #60: Decouple storages: allow to manage tables just for 1 storage in `DbSchemaManager` (@arogachev)
- Enh #60: Add `TransactionlManageDecorator` for `Manager` to guarantee data integrity (@arogachev)
- Bug #60: Implement `AssignmentStorage::renameItem()`, fix bug when implicit renaming had no effect (@arogachev)

## 2.0.0 April 20, 2023

- Bug #43: Support nesting level greater than 1 for items (@arogachev)
- Bug #46: Fix not working `--force` flag for `RbacCycleInit` console command (@arogachev)
- Bug #48: Fix various DBMS support (@arogachev)
- Bug #42: Use integer type instead of timestamp (@arogachev)

## 1.0.0 March 10, 2023

- Initial release.
