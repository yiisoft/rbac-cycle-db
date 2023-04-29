<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Command;

use Yiisoft\Rbac\Command\RbacDbInit;

/**
 * Command for creating RBAC related database tables using Cycle ORM.
 */
final class RbacCycleInit extends RbacDbInit
{
    protected static $defaultName = 'rbac/cycle/init';

    protected function configure(): void
    {
        parent::configure();

        $this->setHelp('This command creates schemas for RBAC using Cycle DBAL');
    }
}
