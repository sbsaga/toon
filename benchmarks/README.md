# TOON Benchmarks

This directory contains reproducible benchmark assets for the package.

## Included Files

- `fixtures/paginated-users.json`
- `run.php`

## Why This Exists

- to back README benchmark claims with a real fixture
- to keep performance messaging reproducible
- to make package comparisons easier across releases

## Data Safety

The included fixture is synthetic and repository-generated. It does not use third-party copyrighted sample content.

## Run the Benchmark

```bash
php benchmarks/run.php benchmarks/fixtures/paginated-users.json
```
