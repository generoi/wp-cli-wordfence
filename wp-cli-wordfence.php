<?php
/*
Plugin Name:        WP CLI Wordfence
Plugin URI:         http://genero.fi
Description:        A Wordfence plugin scanner for WP CLI
Version:            1.0.0
Author:             Genero
Author URI:         http://genero.fi/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/

use GeneroWP\WpCliWordfence\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

Plugin::getInstance();
