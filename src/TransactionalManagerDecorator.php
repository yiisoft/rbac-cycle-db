<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle;

use Cycle\Database\DatabaseInterface;
use Throwable;
use Yiisoft\Rbac\ManagerInterface;
use Yiisoft\Rbac\Permission;
use Yiisoft\Rbac\Role;

class TransactionalManagerDecorator implements ManagerInterface
{
    public function __construct(private ManagerInterface $manager, private DatabaseInterface $database) {
    }

    public function userHasPermission($userId, string $permissionName, array $parameters = []): bool
    {
        return $this->manager->userHasPermission($userId, $permissionName, $parameters);
    }

    public function canAddChild(string $parentName, string $childName): bool
    {
        return $this->manager->canAddChild($parentName, $childName);
    }

    public function addChild(string $parentName, string $childName): ManagerInterface
    {
        $this->manager->addChild($parentName, $childName);

        return $this;
    }

    public function removeChild(string $parentName, string $childName): ManagerInterface
    {
        $this->manager->removeChild($parentName, $childName);

        return $this;
    }

    public function removeChildren(string $parentName): ManagerInterface
    {
        $this->manager->removeChildren($parentName);

        return $this;
    }

    public function hasChild(string $parentName, string $childName): bool
    {
        return $this->manager->hasChild($parentName, $childName);
    }

    public function assign(string $itemName, $userId): ManagerInterface
    {
        $this->manager->assign($itemName, $userId);

        return $this;
    }

    public function revoke(string $itemName, $userId): ManagerInterface
    {
        $this->manager->revoke($itemName, $userId);

        return $this;
    }

    public function revokeAll($userId): ManagerInterface
    {
        $this->manager->revokeAll($userId);

        return $this;
    }

    public function getRolesByUserId($userId): array
    {
        return $this->manager->getRolesByUserId($userId);
    }

    public function getChildRoles(string $roleName): array
    {
        return $this->manager->getChildRoles($roleName);
    }

    public function getPermissionsByRoleName(string $roleName): array
    {
        return $this->manager->getPermissionsByRoleName($roleName);
    }

    public function getPermissionsByUserId($userId): array
    {
        return $this->manager->getPermissionsByUserId($userId);
    }

    public function getUserIdsByRoleName(string $roleName): array
    {
        return $this->manager->getUserIdsByRoleName($roleName);
    }

    public function addRole(Role $role): ManagerInterface
    {
        $this->manager->addRole($role);

        return $this;
    }

    public function removeRole(string $name): ManagerInterface
    {
        $this->manager->removeRole($name);

        return $this;
    }

    public function updateRole(string $name, Role $role): ManagerInterface
    {
        $this->database->begin();

        try {
            $this->manager->updateRole($name, $role);
            $this->database->commit();

            return $this;
        } catch (Throwable $e) {
            $this->database->rollback();

            throw $e;
        }
    }

    public function addPermission(Permission $permission): ManagerInterface
    {
        $this->manager->addPermission($permission);

        return $this;
    }

    public function removePermission(string $permissionName): ManagerInterface
    {
        $this->manager->removePermission($permissionName);

        return $this;
    }

    public function updatePermission(string $name, Permission $permission): ManagerInterface
    {
        $this->database->begin();

        try {
            $this->manager->updatePermission($name, $permission);
            $this->database->commit();

            return $this;
        } catch (Throwable $e) {
            $this->database->rollback();

            throw $e;
        }
    }

    public function setDefaultRoleNames($roleNames): ManagerInterface
    {
        $this->manager->setDefaultRoleNames($roleNames);

        return $this;
    }

    public function getDefaultRoleNames(): array
    {
        return $this->manager->getDefaultRoleNames();
    }

    public function getDefaultRoles(): array
    {
        return $this->manager->getDefaultRoles();
    }

    public function setGuestRoleName(?string $name): ManagerInterface
    {
        $this->manager->setGuestRoleName($name);

        return $this;
    }
}