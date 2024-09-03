# SRF Polis Bundle

Integrates the [SRG SSR Polis API](https://developer.srgssr.ch/api-catalog/srgssr-polis) into Symfony, compatible with Symfony 7.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quality Assurance](#quality-assurance)

## Requirements
- PHP 8.3
- Composer 2
- Symfony 7
- Docker

## Installation

### Docker

Build and start container
```sh
$ docker compose build
$ docker compose up
```

Start a shell in the started container and install dependecies using composer:
```sh
$ composer install
```

## Quality Assurance

This Package provides a baseline of common used QA Code tools which you can run simply by custom composer script commands.
```sh
$ composer cs-fix
$ composer analyse
```

## License

Copyright (c) 2024, SRF under [MIT](LICENSE) License

## Contributing

All feedback / bug reports / pull requests are welcome.
