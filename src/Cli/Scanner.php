<?php

namespace GeneroWP\WpCliWordfence\Cli;

use Exception;
use GeneroWP\WpCliWordfence\Models\Copyright;
use GeneroWP\WpCliWordfence\VulnerabilityScanner;
use GeneroWP\WpCliWordfence\WordfenceApi;
use WP_CLI;

use function WP_CLI\Utils\format_items;
use function WP_CLI\Utils\get_flag_value;

/**
 * @see https://www.wordfence.com/intelligence-documentation/v2-accessing-and-consuming-the-vulnerability-data-feed/
 */
class Scanner
{
    const TRANSIENT_LAST_SCAN_TIME = '_wpcliwf-last-scan-time';

    protected bool $isForce = false;
    protected bool $isVerbose = false;
    protected bool $isSilent = false;

    /**
     * Scan active plugins for vulnerabilities
     *
     * [<Plugin>...]
     * : One or more plugin slugs to check
     *
     * [--format=<format>]
     * : Format to use: ‘table’, ‘json’, ‘csv’, ‘yaml’, ‘ids’, ‘count’ (default: `table`)
     *
     * [--silent]
     * : Only output errors
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
        $this->isSilent = (bool) get_flag_value($flags, 'silent', false);

        $format = get_flag_value($flags, 'format', 'table');

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

        $errors = [];

        try {
            $hasFixableErrors = false;
            foreach ($scanner->next() as $vulnerability => $exception) {
                foreach ($vulnerability->copyrights as $copyright) {
                    $copyrights[$copyright->slug] = $copyright;
                }

                $isPatched = $vulnerability->isPatched();
                if ($isPatched) {
                    $hasFixableErrors = true;
                } elseif ($this->isSilent) {
                    continue;
                }

                $errors[] = [
                    'vulnerability' => $vulnerability->getMessage(),
                    'exception' => $exception->getMessage(),
                    'has patch' => $isPatched ? 'yes' : '',
                    'references' => implode("\n", $vulnerability->references),
                ];
            }

            if ($errors) {
                format_items($format, $errors, array_keys($errors[0]));
            }

            if ($copyrights && ! $this->isSilent) {
                WP_CLI::log('');
                foreach ($copyrights as $copyright) {
                    WP_CLI::log(WP_CLI::colorize('%y' . $copyright->getNotice() . '%n'));
                }
            }

            set_transient(self::TRANSIENT_LAST_SCAN_TIME, time());

            if ($hasFixableErrors) {
                exit(1);
            }
        } catch (Exception $e) {
            WP_CLI::error(WP_CLI::error_to_string($e));
        }
    }

    /**
     * @param mixed $args
     */
    protected function verbose(string $message, ...$args): void
    {
        if ($this->isVerbose && ! $this->isSilent) {
            WP_CLI::log(sprintf($message, ...$args));
        }
    }
}
