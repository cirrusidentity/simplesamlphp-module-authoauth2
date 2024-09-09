<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;

trait ErrorTrait
{
    public function parseError(Request $request): array
    {
        // Do not throw if errors are suppressed by @ operator
        // error_reporting() value for suppressed errors changed in PHP 8.0.0
        $suppressed = PHP_VERSION_ID < 80000
            ? 0
            : E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
        if (error_reporting() == $suppressed) {
            return [];
        }

        return [
            'error',
            'error_description',
        ];
    }
}