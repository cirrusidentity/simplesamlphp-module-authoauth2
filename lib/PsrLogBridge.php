<?php

namespace SimpleSAML\Module\authoauth2;

use Psr\Log\LogLevel;
use SAML2\Compat\Ssp\Logger;

/**
 * The current version of the PSR compat logging has bugs in their log method
 * that prevents logging. See https://github.com/simplesamlphp/saml2/issues/130
 */
class PsrLogBridge extends Logger
{

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        switch ($level) {
            case LogLevel::ALERT:
                $this->alert($message, $context);
                break;
            case LogLevel::CRITICAL:
                $this->critical($message, $context);
                break;
            case LogLevel::DEBUG:
                $this->debug($message, $context);
                break;
            case LogLevel::EMERGENCY:
                $this->emergency($message, $context);
                break;
            case LogLevel::ERROR:
                $this->error($message, $context);
                break;
            case LogLevel::INFO:
                $this->info($message, $context);
                break;
            case LogLevel::NOTICE:
                $this->notice($message, $context);
                break;
            case LogLevel::WARNING:
                $this->warning($message, $context);
        }
    }
}
