# JSON-RPC 2.0 Implementation for PHP

## Features

- [JSON-RPC 2.0 specification](https://www.jsonrpc.org/specification) compliant
- Support for single requests, batch requests and notifications
- PSR-11 container integration for dependency injection
- Clean exception-based error handling
- Immutable value objects for requests, responses, and errors

## Requirements

- PHP 8.4 or higher
- Composer
- PSR-11 compatible container (e.g., PHP-DI, Symfony DependencyInjection)

## Installation

```bash
composer req ellinaut/json-rpc
```

## Quick Start

### 1. Implement a Remote Procedure

```php
<?php

use YourNamespace\Server\RemoteProcedure;

class MathAddProcedure implements RemoteProcedure
{
    public function execute(array $params, string|int|float|null $id): mixed
    {
        if (!isset($params['a'], $params['b'])) {
            throw new InvalidParamsException('Missing required parameters: a, b');
        }
        
        return $params['a'] + $params['b'];
    }
}
```

### 2. Set Up the Server

```php
<?php

use YourNamespace\Server\JsonRpcServer;
use Psr\Container\ContainerInterface;

// Assuming you have a PSR-11 container
$container = new YourContainer();
$container->set('math.add', new MathAddProcedure());

$server = new JsonRpcServer($container);

// Handle incoming request
$jsonInput = file_get_contents('php://input');
$response = $server->handle($jsonInput);

header('Content-Type: application/json');
echo $response;
```

### 3. Make Requests

```bash
curl -X POST http://your-server/endpoint \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "math.add",
    "params": {"a": 5, "b": 3},
    "id": 1
  }'
```

## Development

### Using Docker

Start the development environment:

```bash
docker-compose up
```

Access the PHP container:

```bash
docker-compose exec php bash
```

### Running Tests

```bash
# Local
vendor/bin/phpunit

# With coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage/

# In Docker
docker-compose run --rm php vendor/bin/phpunit

# With coverage in Docker
docker-compose run --rm php vendor/bin/phpunit --coverage-html coverage/
```

## Architecture

### Core Components

- **JsonRpcServer**: Main server handling requests and routing to procedures
- **RemoteProcedure**: Interface for implementing RPC methods
- **Value Objects**: Immutable Request, Response, and Error objects
- **Exceptions**: Specialized JSON-RPC error exceptions

### Error Handling

The library provides specific exceptions for different JSON-RPC error scenarios:

- `InvalidJsonException`: Malformed JSON
- `InvalidRequestException`: Invalid request structure
- `InvalidMethodException`: Method not found
- `InvalidParamsException`: Invalid method parameters
- `InternalErrorException`: Internal server errors

All exceptions extend `JsonRcpException` and are automatically converted to proper JSON-RPC error responses.
