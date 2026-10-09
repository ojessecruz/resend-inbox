# Contributing to Resend Inbox

Thanks for your interest. This package is maintained by one person, so these rules keep reviews short and the package focused. Pull requests that don't follow them may be closed without review.

## Before you open a pull request

- **Open an issue first** for anything other than a small, obvious bug fix or a typo. Wait for the maintainer to agree on the change before writing code. Unannounced features are usually declined.
- **One change per pull request.** No unrelated refactors, renames or reformatting.
- **No new dependencies** (runtime or dev) without agreement in the issue.
- Security problems are never reported in public issues: see [SECURITY.md](SECURITY.md).

## Scope of this package

This is the framework-agnostic core. It **decides** (threading, replies, auto-reply detection, delivery status order, webhook parsing) and never **executes** framework work.

- No framework code: no Laravel, Symfony or any `illuminate/*` / `symfony/*` component beyond `symfony/mime`. `tests/ArchTest.php` and PHPStan enforce this.
- Storage is the caller's job: new lookups go through interfaces like `MessageLookup`, never a database or ORM.
- Classes are `final` and declare `strict_types`; value objects are `readonly`.
- Integrations (Laravel, a future Symfony bundle) live in their own repositories. Pull requests that add framework glue here will be closed.

## Requirements for every pull request

- Tests with Pest for the change: the bug fix comes with a test that fails without it.
- `composer test` passes on PHP 8.3, 8.4 and 8.5, with `--prefer-lowest` too.
- `composer analyse` passes (PHPStan at the level in `phpstan.neon.dist`). Don't add baseline entries or `@phpstan-ignore` to make it pass.
- `composer format` was run (Laravel Pint, `pint.json`). CI checks the style but doesn't fix it for you.
- The README is updated when behavior or configuration changes.
- Don't edit `CHANGELOG.md` or the version: the maintainer does that at release time.

## Commits and merging

- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/): `fix: …`, `feat: …`, `docs: …`, `test: …`, `refactor: …`, `chore: …`. A breaking change has `!` after the type and explains the migration path in the body.
- `main` is protected: changes only land through pull requests, with every check green and the maintainer's approval.
- Pull requests are squash-merged, so the title must be a valid Conventional Commit message.
- Releases (tags `v*`) are made only by the maintainer.

## Development

```bash
composer install
composer test
composer analyse
composer format
```

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE.md).
