<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;

class Logger implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var bool
     */
    public bool $enableDebugLog;

    /**
     * Log constructor.
     *
     * @param LoggerInterface $log
     * @param State $state
     */
    public function __construct(LoggerInterface $log, State $state)
    {
        $this->log = $log;

        $this->enableDebugLog = ($state->getMode() !== State::MODE_PRODUCTION);
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log->emergency('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log->alert('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log->critical('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log->error('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log->warning('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->log->notice('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = []): void
    {
        $this->log->info('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = []): void
    {
        if ($this->enableDebugLog) {
            $this->log->debug('[TweakWise] ' . $message, $context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $this->log->log($level, '[TweakWise] ' . $message, $context);
    }

    /**
     * Log exception message in Tweakwise tag and throw exception
     *
     * @param Exception $exception
     * @throws Exception
     */
    public function throwException(Exception $exception)
    {
        $this->log->error($exception->getMessage());
        throw $exception;
    }
}
