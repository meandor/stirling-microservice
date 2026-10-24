# Core Module (`src/Core/`)

## Purpose

The `src/Core/` directory contains the fundamental components responsible for managing the application lifecycle and
handling HTTP requests and responses. It provides the backbone of the microservice, including routing, configuration,
status monitoring, and basic HTTP abstractions.

## Files

### `AppStatus.php`

Manages the application's health and operational status. It allows for the registration of custom status checks and can
generate a JSON representation of the system's current health.

### `Config.php`

Handles application configuration by loading settings from a JSON file. It provides a singleton instance to access
configuration properties throughout the application.

### `HttpMethods.php`

An abstract class used to provide constant definitions for standard HTTP methods (GET, POST, PUT, DELETE, OPTIONS).

### `Response.php`

Represents an HTTP response. It encapsulates the status code and the response body (entity), providing methods to
retrieve the status and get a JSON-serialized version of the entity.

### `Route.php`

A value object representing a single routing rule, containing the HTTP method, the regex-based endpoint pattern, and the
callback function to execute.

### `Router.php`

The central component for request dispatching. It manages the route collection, handles request URI parsing, supports
middleware-like authentication wrappers, and dispatches matching requests to their respective callbacks.
