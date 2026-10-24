# Database Module (`src/Database/`)

## Purpose

The `src/Database/` directory contains the interfaces that define the data persistence layer. The module implements a *
*Data Mapper** pattern to decouple domain entities from the underlying database schema, facilitating better testing and
flexibility in data storage implementation.

## Files

### `IConnection.php`

Interface for managing the database connection. It includes requirements for a singleton pattern to manage the database
link.

### `IDataMapper.php`

Interface for the Data Mapper, acting as a factory responsible for converting raw database arrays into domain-specific
`IEntity` objects.

### `IEntity.php`

Interface for domain entities. An entity is a plain object that holds the state of a data record, accessed via methods
like `getKey()` and `getProperties()`.

### `IRepository.php`

Interface for the Repository pattern. It provides a collection-like interface for accessing entities, delegating the
actual data mapping to an `IDataMapper`.
