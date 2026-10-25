# Sensitive Configuration Masking on Status Page

* **Author:** Daniel Schruhl
* **Status:** Draft
* **Date:** 2025-05-22

## Overview

The application status page provides visibility into system configuration settings. Currently, this page displays all
configuration values in plain text. To prevent accidental leakage of sensitive credentials to users viewing the status
page, we need a mechanism to mask sensitive values.

## The Problem

Sensitive information, such as passwords, tokens, and secret keys, is currently visible on the application's status
page, which means anyone who can view the status page can read those credentials.

## Goals

* Implement server-side masking of configuration values to ensure sensitive data is never transmitted to the client in
  plain text.
* Support case-insensitive detection of sensitive keys based on specific patterns (e.g., "password", "secret", "
  token", "key", "credential").
* Mask sensitive values using a consistent, non-informative string of asterisks (e.g., `********`).
* A status API response (via `AppStatus::getStatusArray()` and `buildStatusPage()`) contains no plaintext value for
  any key classified as sensitive, verified by automated tests.

## Non-Goals

* Providing any mechanism for users or administrators to unmask or view the actual sensitive values.
* Performing masking on the client side (browser).
* Implementing dynamic masking based on the actual length of the secret.
* Protecting sensitive values on paths other than the status page (logging, error output, other endpoints) as this is
  out of scope for this change.

## Technical Plan (Architecture)

The proposed solution intercepts the configuration data at the status payload assembly, before it is serialized for the
API response. This ensures that sensitive values never reach the transport layer (HTTP response).

We will implement a `ConfigurationMasker` utility class. It takes the configuration map and applies a masking rule to
any key classified as sensitive.

**Masking Logic:**
* Split each configuration key on `_`, `-`, and lowercase-to-uppercase camelCase boundaries.
* Lowercase each resulting segment.
* Classify a key as sensitive if any segment contains one of the patterns: `password`, `secret`, `token`, `key`,
  `credential`.
* Replace the value of sensitive keys with the fixed string `********`.
* Recursively apply this logic to nested configuration structures.

**Worked examples:**

| Key | Masked? | Reason |
| --- | --- | --- |
| `db_password` | Yes | segment `password` matches |
| `secretKey` | Yes | segment `secret` matches |
| `api-key` | Yes | segment `key` matches |
| `keyboard_layout` | No | no segment contains a sensitive pattern |
| `credential_store_url` | Yes | segment `credential` matches |

### Data Flow

```text
[Stirling\Core\Config]  (holds raw configuration)
           |
           | (AppStatus::getStatusArray() assembles the payload,
           |  attaching "configuration" => $config->getConfigMap())
           |
           | (Masking logic applied via ConfigurationMasker)
           v
[ConfigurationMasker]   (returns masked data)
           |
           | (Returns masked payload)
           v
[AppStatus::buildStatusPage()]  (emits JSON with status header)
           |
           v
[Client (Status Page)]
```

### Proposed Components

1.  **Masking Utility**: A new class `Stirling\Core\ConfigurationMasker` (namespace `Stirling\Core`) dedicated to the masking logic. It will use a default set of patterns but allows the consuming application to provide its own override.
2.  **Integration Point**: `src/Core/AppStatus.php` — the configuration map is passed through the
    `ConfigurationMasker` in `AppStatus::getStatusArray()` before the payload is returned.

## Risks & Mitigations

* **False-positive masking**: Segment-based matching reduces, but does not eliminate, false positives (e.g., a key
  `cacheKey` is masked). Accepted trade-off: over-masking is safer than under-masking, and the status page retains
  all non-sensitive configuration.
* **Incomplete pattern list**: The pattern list is not exhaustive; a sensitive value stored under a key such as
  `admin_passphrase` would not be masked. Mitigation: the patterns are a documented, single-location list that can
  be extended by the consuming application.
* **Leak paths outside the status page**: Sensitive values may still be exposed via logging, error messages, or
  other endpoints. Out of scope for this change.
* **No length leakage**: The fixed `********` string reveals nothing about the length or content of the original
  value. This is intentional.

## Alternatives Considered

### Alternative 1: Client-side Masking

* **Pros**: Simpler implementation on the backend.
* **Cons**: Insecure. The raw sensitive values are still sent over the network and can be easily inspected using browser developer tools.

### Alternative 2: Dynamic Masking Based on Length

* **Pros**: Provides a more accurate representation of the masked content's size.
* **Cons**: Information leakage. Revealing the length of a secret can assist attackers in targeted brute-force or other cryptographic attacks.

### Alternative 3: Role-based Unmasking

* **Pros**: Allows authorized administrators to view credentials when needed.
* **Cons**: Requires an authorization layer that does not currently exist in the library, plus a server-side endpoint to reveal values.

--------------------------------------------------------------------------------

## Non-Functional Requirements

* **Security**: Raw sensitive values must never leave the server in the status payload. Masking is applied before
  serialization in `getStatusArray()`, so both the array API and the JSON page are protected.
* **Performance**: Masking performs a single pass over a small configuration map and adds negligible overhead to the
  status page.
* **Backward Compatibility**: The change only affects the displayed values of sensitive keys in the status payload.
  Structure, key names, and all other endpoints of the library are unchanged.

--------------------------------------------------------------------------------

### Additional Resources

* `src/Core/AppStatus.php` — status payload assembly and JSON page output
* `src/Core/Config.php` — configuration source (`getConfigMap()`)

--------------------------------------------------------------------------------

## Detailed Implementation

### Target Files to Create/Modify

#### 1. `src/Core/ConfigurationMasker.php` (Create)

* **Type / Scope**: PHP Class
* **Proposed Changes**: Implementation of the `ConfigurationMasker` class in namespace `Stirling\Core`. It exposes a
  method that accepts a configuration structure and returns a masked copy. It splits each key on `_`, `-`, and
  camelCase boundaries, lowercases each segment, and masks the value if any segment matches `password`, `secret`,
  `token`, `key`, or `credential`. It will support an optional custom pattern list for overriding defaults.

#### 2. `src/Core/AppStatus.php` (Modify)

* **Type / Scope**: PHP Class
* **Proposed Changes**: In `getStatusArray()`, pass the result of `$config->getConfigMap()` through
  `ConfigurationMasker` before assigning it to the `"configuration"` entry.

#### 3. `tests/Core/ConfigurationMaskerTest.php` (Create)

* **Type / Scope**: Unit Test (PHPUnit)
* **Proposed Changes**: Unit tests for `ConfigurationMasker` covering: flat arrays, nested arrays, all five patterns,
  case-insensitive matching, segment-boundary behavior, and non-sensitive keys.

#### 4. `tests/Core/AppStatusTest.php` (Modify)

* **Type / Scope**: Integration Test (PHPUnit)
* **Proposed Changes**: Add tests verifying that `getStatusArray()` and the JSON output of `buildStatusPage()`
  contain `********` for configuration keys classified as sensitive, and that non-sensitive configuration values are
  returned unchanged.

### Verification Plan

*   **Compilation/Build**: `composer dump-autoload`
*   **Static Analysis**: `vendor/bin/phpstan analyse src`
*   **Code Metrics**: `vendor/bin/phpmd src/Core`
*   **Mutation Testing**: `vendor/bin/infection`
*   **Unit Tests**: `vendor/bin/phpunit tests/Core/ConfigurationMaskerTest.php`
*   **Integration Tests**: `vendor/bin/phpunit tests/Core/AppStatusTest.php`
*   **E2E/Manual Verification**: Inspect the JSON output of the application status endpoint using `curl` to ensure that sensitive values are successfully masked.

--------------------------------------------------------------------------------

<!-- Provenance: Indicates initial structural drafting via EGM. -->

*Drafted with EGM*
