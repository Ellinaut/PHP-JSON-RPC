# JSON-RPC 2.0 Implementation for PHP

## Features

- [JSON-RPC 2.0 specification](https://www.jsonrpc.org/specification) compliant
- Support for single requests, batch requests and notifications
- **Server implementation** with PSR-11 container integration
- **Client implementation** with pluggable transport layer
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

## Server Usage

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

## Client Usage

### 1. Implement a Transport

```php
<?php

use Ellinaut\JsonRpc\Client\TransportInterface;

class HttpTransport implements TransportInterface
{
    public function __construct(private string $endpoint) {}
    
    public function send(string $json): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $json
            ]
        ]);
        
        return file_get_contents($this->endpoint, false, $context) ?: null;
    }
}
```

### 2. Use the Client

```php
<?php

use Ellinaut\JsonRpc\Client\JsonRpcClient;
use Ellinaut\JsonRpc\Model\Value\Request;

$transport = new HttpTransport('http://your-server/endpoint');
$client = new JsonRpcClient($transport);

// Single request
$request = new Request('math.add', ['a' => 5, 'b' => 3], 1);
$response = $client->send($request);

if ($response) {
    echo "Result: " . $response->data . "\n";
}

// Batch requests
$requests = [
    new Request('math.add', ['a' => 1, 'b' => 2], 1),
    new Request('math.multiply', ['a' => 3, 'b' => 4], 2),
    new Request('notification', ['message' => 'hello'], null) // Notification
];

$responses = $client->sendBatch($requests);
foreach ($responses as $response) {
    echo "Response ID {$response->id}: {$response->data}\n";
}
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

#### Server Components
- **JsonRpcServer**: Main server handling requests and routing to procedures
- **RemoteProcedure**: Interface for implementing RPC methods

#### Client Components  
- **JsonRpcClient**: Client for making JSON-RPC requests with Generator-based batch support
- **TransportInterface**: Pluggable transport layer for different communication methods

#### Shared Components
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

### Key Features

#### Generator-based Batch Processing
The client uses PHP Generators for memory-efficient batch request processing:

```php
$responses = $client->sendBatch($requests); // Returns Generator<Response>
foreach ($responses as $response) {
    // Process each response as it's yielded
    echo $response->data;
}
```

#### Transport Abstraction
The client supports different transport mechanisms through the `TransportInterface`:

- **HTTP Transport**: For REST API communication
- **Socket Transport**: For direct TCP communication  
- **Mock Transport**: For testing
- **Custom Transports**: Implement your own transport layer

#### Error Handling
Both client and server provide comprehensive error handling:

- **Server**: Catches exceptions and converts them to JSON-RPC error responses
- **Client**: Wraps transport errors in `JsonRcpException` for consistent error handling
