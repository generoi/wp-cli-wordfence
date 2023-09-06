<?php

namespace GeneroWP\WpCliWordfence;

use GeneroWP\WpCliWordfence\Cli\Scanner;
use WP_CLI;

class Plugin
{
    protected static Plugin $instance;

    public static function getInstance(): Plugin
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        if (class_exists(Wp_Cli::class)) {
            /* @phpstan-ignore-next-line */
            WP_CLI::add_command('wordfence', Scanner::class);
        }
    }
}
