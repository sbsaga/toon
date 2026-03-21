# Contributing

Thanks for considering a contribution.

## Local Setup

```bash
composer install
composer test
```

## Pull Request Expectations

- keep the public API stable unless the change is intentionally breaking
- add or update tests for behavior changes
- keep README and docs aligned with actual package behavior
- avoid marketing claims that are not backed by tests or fixtures

## Documentation Changes

If a change affects output behavior, also update:

- `README.md`
- `docs/spec-compatibility.md`
- `docs/migration.md` when upgrade behavior changes

## Release Hygiene

Before cutting a release:

1. Run `composer test`.
2. Verify benchmark output with `php benchmarks/run.php benchmarks/fixtures/paginated-users.json`.
3. Update `CHANGELOG.md`.
4. Draft release notes from `.github/release-template.md`.
