# Migration Guide

Use this guide to upgrade with low risk and clear rollback options.

## Migration Promise

The package keeps backward-safe runtime defaults:

- default `compatibility_mode` remains `legacy`
- existing core API remains available
- new features are additive and opt-in

## Before You Upgrade

Create a small baseline set:

1. one representative payload per critical flow
2. one integration test that verifies encode/decode behavior
3. one log/prompt path where sensitive field redaction is required

## Upgrade Steps

1. Upgrade package version.
2. Keep `compatibility_mode=legacy`.
3. Run tests and compare output for your baseline payloads.
4. Add replacer rules for sensitive fields where needed.
5. Optionally test `modern` mode in staging only.

## Configuration During Rollout

```php
// config/toon.php
return [
    'compatibility_mode' => 'legacy',
    'strict_mode' => false,
    'delimiter' => 'comma',
];
```

If your workflow requires strict validation:

```php
// only where malformed input must fail hard
'strict_mode' => true,
```

## Legacy vs Modern Decision

Use `legacy` when:

- existing downstream consumers expect current output shape
- snapshot stability is more important than modernization

Use `modern` when:

- you are building new flows
- you want safer nested round trips
- you can validate downstream consumers in controlled rollout

## Safe Trial Plan for Modern Mode

1. keep production on `legacy`
2. run staging tests with `modern`
3. compare representative payload outputs
4. validate consumer parsers and templates
5. switch production after sign-off

## Rollback Plan

If behavior mismatch is detected after upgrade:

1. set `compatibility_mode` back to `legacy`
2. disable newly introduced replacer transforms
3. disable strict decode outside critical validation paths
4. rerun baseline payload tests

In most cases rollback is configuration-first, not code removal.

## Useful Validation Commands

```bash
composer validate --strict
vendor/bin/phpunit --configuration phpunit.xml.dist
composer audit
```

## Related Guides

- [Upgrade safety for v1.3.0](upgrade-safety-v1-3.md)
- [Production playbook](production-playbook.md)
- [Cookbook](cookbook.md)
