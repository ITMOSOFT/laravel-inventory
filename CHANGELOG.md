# Changelog

## [1.0.1] - Unreleased
- Made initial stock-row creation resilient to concurrent first-time requests by using the database unique constraint with an insert-or-ignore operation.
- Reject inventory operations for unsaved inventory models and stock locations with a clear exception.
- Added regression coverage for repeated stock lookup and unsaved model validation.
- Clarified that inventory operations require persisted Eloquent models.

## [1.0.0] - 2026-11-04
- Initial inventory package release.
- Added locations, polymorphic stock items, movements, and reservations.
- Added receipt, adjustment, reserve, release, and fulfillment operations.
- Added transactional stock updates and Laravel 12/13 CI.
