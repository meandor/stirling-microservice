# Source Code Directory (`src/`)

## Purpose
The `src/` directory contains the core business logic and architectural components of the Stirling Microservice. It is organized into layers to maintain a strict separation of concerns between HTTP request handling, application orchestration, and data persistence.

## Directory Structure

### `src/Core/`
Contains the fundamental HTTP handling and application lifecycle components.
- **`AppStatus.php`**: Manages the operational state and health status of the application.
- **`Config.php`**: Provides centralized access to application configuration settings.
- **`HttpMethods.php`**: Defines constants for standard HTTP verbs (GET, POST, etc.).
- **`Response.php`**: Handles the construction and dispatching of HTTP responses.
- **`Route.php`**: Represents a single routing rule, including the pattern and callback.
- **`Router.php`**: Manages URI routing, middleware/authentication integration, and request dispatching.

### `src/Database/`
Contains the interfaces and logic for data persistence, following the Data Mapper pattern.
- **`IConnection.php`**: Interface for managing database connections and transaction boundaries.
- **`IDataMapper.php`**: Interface for objects that map raw database data to domain entities.
- **`IEntity.php`**: Interface for domain entities, ensuring they remain decoupled from persistence logic.
- **`IRepository.php`**: Interface for collection-like access to entities, providing a domain-centric view of the data.
