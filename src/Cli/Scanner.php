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
    protected bool $onlyErrors = false;

    /**
     * Scan active plugins for vulnerabilities
     *
     * [<Plugin>...]
     * : One or more plugin slugs to check
     *
     * [--email=<email>]
     * : Send vulnerability report to email
     *
     * [--format=<format>]
     * : Format to use: ‘table’, ‘json’, ‘csv’, ‘yaml’, ‘ids’, ‘count’ (default: `table`)
     *
     * [--only-errors]
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
        $this->onlyErrors = (bool) get_flag_value($flags, 'only-errors', false);

        $format = get_flag_value($flags, 'format', 'table');
        $email = get_flag_value($flags, 'email');

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
                } elseif ($this->onlyErrors) {
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

            if ($copyrights && ! $this->onlyErrors) {
                WP_CLI::log('');
                foreach ($copyrights as $copyright) {
                    WP_CLI::log(WP_CLI::colorize('%y' . $copyright->getNotice() . '%n'));
                }
            }

            set_transient(self::TRANSIENT_LAST_SCAN_TIME, time());

            if ($email && $hasFixableErrors) {
                wp_mail(
                    $email,
                    sprintf('%s - Vulnerabilities found', get_bloginfo('name')),
                    $this->buildEmailMessage($errors, $copyrights),
                    ['Content-Type: text/html; charset=UTF-8'],
                );
            }

            if ($hasFixableErrors) {
                exit(1);
            }
        } catch (Exception $e) {
            WP_CLI::error(WP_CLI::error_to_string($e));
        }
    }

    /**
     * @param array<string,string>[] $errors
     * @param Copyright[] $copyrights
     */
    protected function buildEmailMessage(array $errors, array $copyrights): string
    {
        $head = sprintf('<th>%s</th>', implode('</th><th>', array_keys($errors[0])));
        $body = array_reduce($errors, function (string $carry, array $error) {
            return $carry . sprintf('<tr><td>%s</td></tr>', implode('</td><td>', array_values($error)));
        }, '');

        $errorsTable = sprintf('
            <table>
                <thead>
                    <tr>%s</tr>
                </thead>
                <tbody>
                    %s
                </tbody>
            </table>
        ', $head, $body);

        $copyrightsRows = array_reduce($copyrights, function (string $carry, Copyright $copyright) {
            return $carry . sprintf(
                '<li><strong><a href="%s">%s</a></strong><br />%s</li>',
                $copyright->licenseUrl,
                $copyright->notice,
                $copyright->license,
            );
        }, '');
        $copyrightsList = $copyrightsRows ? sprintf('<ul>%s</ul>', $copyrightsRows) : '';

        return sprintf('%s%s', $errorsTable, $copyrightsList);
    }

    /**
     * @param mixed $args
     */
    protected function verbose(string $message, ...$args): void
    {
        if ($this->isVerbose && ! $this->onlyErrors) {
            WP_CLI::log(sprintf($message, ...$args));
        }
    }
}
