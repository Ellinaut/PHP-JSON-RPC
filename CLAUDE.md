# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Start development environment
docker-compose up

# Access PHP container
docker-compose exec php bash

# Run tests
vendor/bin/phpunit

# Run tests with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage/

# Run tests in Docker container
docker-compose run --rm php vendor/bin/phpunit

# Run tests with coverage in Docker
docker-compose run --rm php vendor/bin/phpunit --coverage-html coverage/
```

## Architecture Overview

This is a JSON-RPC 2.0 server implementation for PHP 8+ that follows clean architecture principles.

### Core Components

1. **JsonRpcServer** (src/JsonRpcServer.php): Main server that handles JSON-RPC requests, routes to procedures via PSR-11 container, and manages batch processing.

2. **RemoteProcedure Interface** (src/RemoteProcedure.php): Simple contract for implementing RPC methods with signature:
   ```php
   public function execute(array $params, string|int|float|null $id): mixed;
   ```

3. **Value Objects** (src/Model/Value/):
   - **Request**: Validates and represents JSON-RPC requests
   - **Response**: Handles success/error responses (Note: bug on line 66 - always returns null result)
   - **Error**: JSON-RPC compliant error objects

4. **Exception Hierarchy** (src/Exception/): Specialized exceptions for different JSON-RPC error scenarios, all extending JsonRcpException (typo - should be JsonRpcException).

### Key Design Patterns

- **Dependency Injection**: Server accepts PSR-11 ContainerInterface for procedure registration
- **Value Objects**: Immutable request/response/error objects
- **Exception-based Error Handling**: Maps exceptions to JSON-RPC error responses

### Development Notes

- Uses PHP 8+ features (readonly classes, union types, named arguments)
- No test suite currently exists
- Docker environment runs PHP 8.4-fpm with Composer and intl extension
- Library focuses on JSON-RPC 2.0 spec compliance with support for single/batch requests and notifications