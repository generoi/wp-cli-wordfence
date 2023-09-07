# wp-cli-wordfence

> A WP CLI command packaged as a plugin which uses [Wordfence public vulnerablitiy feed](https://www.wordfence.com/intelligence-documentation/v2-accessing-and-consuming-the-vulnerability-data-feed/) to scan all plugins against known security advisories.
> Note that security advisories without a patch will use exit(0) and thus not detected as an error exit(1)
 
## Requirements

- WP-CLI

## Usage

```
NAME

  wp wordfence scan

DESCRIPTION

  Scan plugins for vulnerabilities

SYNOPSIS

  wp wordfence scan [<Plugin>...] [--email=<email>] [--format=<format>] [--only-errors] [--force] [--verbose]

  [<Plugin>...]
    One or more plugin slugs to check

  [--email=<email>]
    Send vulnerability report to email

  [--format=<format>]
    Format to use: ‘table’, ‘json’, ‘csv’, ‘yaml’, ‘ids’, ‘count’ (default: `table`)

  [--only-errors]
    Only output errors

  [--force]
    Force run even if unchanged

  [--verbose]
    Use verbose output
```

## Example output

```
+----------------------------------------------------------------------------------+---------------------+-----------+-------------------------------------------------------------------------------------+
| vulnerability                                                                    | exception           | has patch | references                                                                          |
+----------------------------------------------------------------------------------+---------------------+-----------+-------------------------------------------------------------------------------------+
| WordPress Core - All Versions - Authenticated(Administrator+) PHP File Upload    | * < 6.2.2 < *       |           | https://www.wordfence.com/threat-intel/vulnerabilities/id/0a6707ef-aab7-449c-8160-0 |
|                                                                                  |                     |           | 34bc188a998?source=api-scan                                                         |
| WordPress Core <= 6.2 - Unauthenticated Blind Server Side Request Forgery        | * < 6.2.2 < *       |           | https://www.wordfence.com/threat-intel/vulnerabilities/id/112ed4f2-fe91-4d83-a3f7-e |
|                                                                                  |                     |           | af889870af4?source=api-scan                                                         |
| Advanced Custom Fields PRO 6.1 - 6.1.7 - Authenticated (Administrator+) Stored C | 6.1 < 6.1.7 < 6.1.7 | yes       | https://www.wordfence.com/threat-intel/vulnerabilities/id/77876d74-5825-4bd8-812e-8 |
| ross-Site Scripting                                                              |                     |           | 7061d0470e6?source=api-scan                                                         |
| WordPress Core - All Known Versions - Cleartext Storage of wp_signups.activation | * < 6.2.2 < *       |           | https://www.wordfence.com/threat-intel/vulnerabilities/id/9fda5e15-fdf9-4b67-93d3-2 |
| _key                                                                             |                     |           | dbfa94aefe9?source=api-scan                                                         |
| WordPress Core - Informational - All known Versions - Weak Hashing Algorithm     | * < 6.2.2 < *       |           | https://www.wordfence.com/threat-intel/vulnerabilities/id/e5dc87cd-4f45-4faf-b1e2-6 |
|                                                                                  |                     |           | 4e94eacb180?source=api-scan                                                         |
+----------------------------------------------------------------------------------+---------------------+-----------+-------------------------------------------------------------------------------------+

Copyright 2012-2023 Defiant Inc.
Defiant hereby grants you a perpetual, worldwide, non-exclusive, no-charge, royalty-free, irrevocable copyright license to reproduce, prepare derivative works of, publicly display, publicly perform, sublicense, and distribute this software vulnerability information. Any copy of the software vulnerability information you make for such purposes is authorized provided that you include a hyperlink to this vulnerability record and reproduce Defiant's copyright designation and this license in any such copy.
https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/
```

## Example Github action

This assumes you're using bedrock and have this package in your composer.json. Open to PRs to make this package self contained so we can install latest version with `wp`

```yaml
name: Vulnerabilty Scan
on:
  schedule:
    - cron: '5 4 * * *'
  workflow_dispatch:
jobs:
  vulnerability-scan:
    name: Run vulnerability scan
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Repository (latest)
        uses: actions/checkout@v3

      - name: Read composer.json to env
        run: |
          echo 'COMPOSER_JSON<<EOF' >> $GITHUB_ENV
          cat ./composer.json >> $GITHUB_ENV
          echo 'EOF' >> $GITHUB_ENV

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ fromJson(env.COMPOSER_JSON).config.platform.php }}

      - name: Start MySQL service
        run: |
          sudo /etc/init.d/mysql start
          mysql -e 'CREATE DATABASE db;' -uroot -proot
          mysql -e "CREATE USER 'db'@'localhost' IDENTIFIED BY 'db';" -uroot -proot
          mysql -e "GRANT ALL PRIVILEGES ON db.* TO 'db'@'localhost' WITH GRANT OPTION;" -uroot -proot

      - name: Install packages
        run: composer install

      - name: Launch web server
        run: ./vendor/bin/wp server &

      - name: Setup .env
        run: |
          cp .env.example .env
          sed -i 's/WP_HOME=.*/WP_HOME=http:\/\/localhost:8080/g' .env
          sed -i 's/DB_HOST=.*/DB_HOST=localhost/g' .env

      - name: Install WordPress
        run: ./vendor/bin/wp core install --url=http://localhost:8080 --title="Bedrock" --admin_user="admin" --admin_password="admin" --admin_email="bedrock@example.test"

      - name: Run scan
        run: |
          ./vendor/bin/wp plugin activate wp-cli-wordfence
          ./vendor/bin/wp wordfence scan --email=foo@example.org
```

## Development

Install dependencies

    composer install

Run the tests

    npm -g i @wordpress/env
    wp-env start
    wp-env run tests-cli --env-cwd=wp-content/plugins/wp-cli-wordfence ./vendor/bin/phpunit
