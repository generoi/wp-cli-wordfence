<?php

namespace GeneroWP\WpCliWordfence\Cli;

use Exception;
use GeneroWP\WpCliWordfence\Models\Record;
use GeneroWP\WpCliWordfence\Models\Copyright;
use GeneroWP\WpCliWordfence\VulnerabilityScanner;
use GeneroWP\WpCliWordfence\WordfenceApi;
use WP_CLI;

use function WP_CLI\Utils\get_flag_value;

/**
 * @see https://www.wordfence.com/intelligence-documentation/v2-accessing-and-consuming-the-vulnerability-data-feed/
 */
class Scanner
{
    const TRANSIENT_LAST_SCAN_TIME = '_wpcliwf-last-scan-time';

    protected bool $isForce = false;
    protected bool $isVerbose = false;

    /**
     * Scan active plugins for vulnerabilities
     *
     * [<Plugin>...]
     * : One or more plugin slugs to check
     *
     * [--force]
     * : Force run even if unchanged
     *
     * [--verbose]
     * : Use verbose output
     *
     * @param string[] $slugs
     * @param array<string,mixed> $flags
     */
    public function scan(array $slugs, array $flags): void
    {
        $this->isForce = (bool) get_flag_value($flags, 'force', false);
        $this->isVerbose = (bool) get_flag_value($flags, 'verbose', false);

        $headers = [];
        $lastTimeRun = (int) get_transient(self::TRANSIENT_LAST_SCAN_TIME);
        if (! $this->isForce && $lastTimeRun) {
            $headers['If-Modified-Since'] = gmdate('D, d M Y H:i:s', $lastTimeRun);
        }

        $scanner = new VulnerabilityScanner(
            new WordfenceApi($headers)
        );

        if ($slugs) {
            $scanner->setSoftwareLimit($slugs);
        }

        /** @var array<string,Copyright> copyrights */
        $copyrights = [];

        try {
            foreach ($scanner->next() as $vulnerability => $exception) {
                foreach ($vulnerability->copyrights as $copyright) {
                    $copyrights[$copyright->slug] = $copyright;
                }

                if ($vulnerability->isPatched()) {
                    $this->reportError($exception->getMessage(), $vulnerability);
                } else {
                    $this->reportWarning('Unpatched vulnarability', $vulnerability);
                }
            }

            if ($copyrights) {
                WP_CLI::log('');
                foreach ($copyrights as $copyright) {
                    WP_CLI::log(WP_CLI::colorize('%y' . $copyright->getNotice() . '%n'));
                }
            }

            set_transient(self::TRANSIENT_LAST_SCAN_TIME, time());
        } catch (Exception $e) {
            WP_CLI::error(WP_CLI::error_to_string($e));
        }
    }

    /**
     * @param mixed $args
     */
    protected function verbose(string $message, ...$args): void
    {
        if ($this->isVerbose) {
            WP_CLI::log(sprintf($message, ...$args));
        }
    }

    protected function reportError(string $message, Record $record): void
    {
        $refences = $record->references ? "\n\t" . implode("\n\t", $record->references) : '';

        WP_CLI::error(sprintf(
            "%s %s%s",
            $message,
            $record->getMessage(),
            $refences
        ), false);
    }


    protected function reportWarning(string $message, Record $record): void
    {
        $refences = $record->references ? "\n\t" . implode("\n\t", $record->references) : '';

        WP_CLI::log(sprintf(
            '%s %s%s',
            WP_CLI::colorize('%y' . $message . ':%n'),
            $record->title,
            $refences,
        ));
    }
}
