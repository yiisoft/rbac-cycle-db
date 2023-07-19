<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Throwable;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;
use Yiisoft\Rbac\RuleFactoryInterface;

final class Manager extends \Yiisoft\Rbac\Manager
{
    public function __construct(
        ItemsStorageInterface $itemsStorage,
        AssignmentsStorageInterface $assignmentsStorage,
        RuleFactoryInterface $ruleFactory,
        private DatabaseInterface $database,
        bool $enableDirectPermissions = false,
    ) {
        parent::__construct($itemsStorage, $assignmentsStorage, $ruleFactory, $enableDirectPermissions);
    }

    public function updateRole(string $name, Role $role): self
    {
        $this->database->begin();

        try {
            $manager = parent::updateRole($name, $role);
            $this->database->commit();

            return $manager;
        } catch (Throwable $e) {
            $this->database->rollback();

            throw $e;
        }
    }

    public function updatePermission(string $name, Permission $permission): \Yiisoft\Rbac\Manager
    {
        $this->database->begin();

        try {
            $manager = parent::updatePermission($name, $permission);
            $this->database->commit();

            return $manager;
        } catch (Throwable $e) {
            $this->database->rollback();

            throw $e;
        }
    }
}
