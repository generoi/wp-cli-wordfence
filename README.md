# wp-cli-wordfence

> A Wordfence plugin scanner for WP CLI

## Requirements

- WP-CLI

## Usage

    wp wordfence scan
    wp wordfence scan 'gravityforms'
    wp wordfence scan --force --verbose

## Development

Install dependencies

    composer install

Run the tests

    npm -g i @wordpress/env
    wp-env start
    wp-env run tests-cli --env-cwd=wp-content/plugins/wp-cli-wordfence ./vendor/bin/phpunit
