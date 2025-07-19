# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Information

- **Package**: ellinaut/json-rpc
- **Version**: 1.0.0
- **PHP**: ^8.4
- **License**: MIT
- **Author**: Philipp Marien

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

# Run specific test file
vendor/bin/phpunit tests/JsonRpcServerTest.php

# Run tests with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage/

# Run tests in Docker container
docker-compose run --rm php vendor/bin/phpunit

# Run tests with coverage in Docker
docker-compose run --rm php vendor/bin/phpunit --coverage-html coverage/
```

## Architecture Overview

This is a JSON-RPC 2.0 server implementation for PHP 8.4+ that follows clean architecture principles with comprehensive test coverage.

### Core Components

1. **JsonRpcServer** (src/Server/JsonRpcServer.php): Main server that handles JSON-RPC requests, routes to procedures via PSR-11 container, and manages batch processing.

2. **RemoteProcedure Interface** (src/Server/RemoteProcedure.php): Simple contract for implementing RPC methods with signature:
   ```php
   public function execute(array $params, string|int|float|null $id): mixed;
   ```

3. **Value Objects** (src/Model/Value/):
   - **Request**: Validates and represents JSON-RPC requests
   - **Response**: Handles success/error responses with proper result/error serialization
   - **Error**: JSON-RPC compliant error objects with standard error codes

4. **Exception Hierarchy** (src/Exception/): Specialized exceptions for different JSON-RPC error scenarios, all extending JsonRcpException (Note: typo in class name - should be JsonRpcException):
   - **InvalidJsonException** (-32700): Parse error
   - **InvalidRequestException** (-32600): Invalid Request
   - **InvalidMethodException** (-32601): Method not found
   - **InvalidParamsException** (-32602): Invalid params
   - **InternalErrorException** (-32603): Internal error

### Key Design Patterns

- **Dependency Injection**: Server accepts PSR-11 ContainerInterface for procedure registration
- **Value Objects**: Immutable request/response/error objects
- **Exception-based Error Handling**: Maps exceptions to JSON-RPC error responses with standard error codes

### Testing

- **Comprehensive test suite** with PHPUnit 11.5 covering all JSON-RPC 2.0 specification scenarios
- **Coverage reporting** to HTML, text, and clover formats
- **Test utilities**: RemoteProcedureMock class for tracking procedure execution
- **Random test order** execution for better test isolation
- **Exception classes excluded** from coverage analysis

### Docker Environment

- **Base**: PHP 8.4-fpm with Xdebug and intl extensions
- **Coverage**: Xdebug configured in coverage mode
- **Tools**: Composer, bash-completion, unzip
- **Working directory**: /app with volume mounting

### Development Notes

- Uses PHP 8.4 features (readonly classes, union types, named arguments)
- Full JSON-RPC 2.0 specification compliance verified through tests
- Supports single requests, batch requests, and notifications
- PSR-11 container integration for dependency injection
- Standard error code mapping for JSON-RPC error scenarios