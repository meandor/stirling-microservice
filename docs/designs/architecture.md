# Architecture Specification: Stirling Microservice

## Overview

The Stirling Microservice is a lightweight PHP-based microservice framework designed with a clear separation between
core routing/HTTP concerns and data persistence. It utilizes a layered architecture to ensure scalability and
testability.

## Component Breakdown

### 1. Core Module (`src/Core/`)

The Core module manages the lifecycle of an incoming HTTP request and the dispatching of responses.

* **`AppStatus.php`**: Manages the operational state of the application (e.g., Bootstrapping, Running, Shutting Down).
* **`Config.php`**: Centralized, immutable configuration management.
* **`Router.php`**: Responsible for mapping URI patterns to specific handlers via a registered route collection.
* **`Route.php`**: Value object representing a single routing rule, including HTTP methods and middleware requirements.
* **`HttpMethods.php`**: Defines standard HTTP verb constants.
* **`Response.php`**: Encapsulates the outgoing HTTP response data, including status codes and headers.

**Middleware & Pipeline:**
The `Router` utilizes a middleware pipeline pattern. Each request passes through a stack of interceptors (e.g.,
Authentication, Logging, Content Negotiation) before reaching the final handler.

### 2. Database Module (`src/Database/`)

The Data layer implements a robust Data Mapper pattern to decouple business entities from the persistence mechanism.

* **`IEntity.php`**: Interface for domain entities (POPOs - Plain Old PHP Objects). Entities hold no state related to
  the database.
* **`IDataMapper.php`**: Interface for objects responsible for the translation between entities and the database schema.
* **`IRepository.php`**: Interface for collection-like access to entities, providing a domain-centric view of data.
* **`IConnection.php`**: Interface for managing the database connection and transaction boundaries.

**Data Integrity:**
All database operations are intended to be wrapped in transactions managed at the Service Layer level to ensure
atomicity.

## Design Patterns

* **Data Mapper Pattern**: Decouples domain entities from persistence.
* **Dependency Injection (DI)**: A central Container is used to inject `IConnection` into Mappers and Services into
  Controllers.
* **Chain of Responsibility**: Used for the middleware pipeline in the `Core` module.
* **Dependency Inversion**: All components rely on interfaces (`IEntity`, `IRepository`, etc.) rather than concrete
  implementations.

## Error Handling

The system uses a hierarchical Exception strategy:

1. **Domain Exceptions**: Thrown by Services/Mappers.
2. **Infrastructure Exceptions**: Thrown by Core/Database (e.g., `ConnectionException`).
3. **Global Error Handler**: A `Core` component that catches all exceptions and transforms them into standardized
   `Response` objects (e.g., 404 for `RouteNotFound`, 500 for `DatabaseException`).

## Infrastructure

* **Language**: PHP
* **Dependency Management**: Composer
* **Testing**: PHPUnit
