<?php

declare(strict_types=1);

namespace Yiisoft\Rbac\Cycle\Tests\Base;

use Psr\Log\AbstractLogger;
use Stringable;

class Logger extends AbstractLogger
{
    public function log($level, string|Stringable $message, array $context = []): void
    {
        var_dump($message);
    }
}
