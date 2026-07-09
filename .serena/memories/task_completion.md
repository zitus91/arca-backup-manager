Before commit/PR:
1. composer test (or php artisan test)
2. npm run build (production assets)
3. ./vendor/bin/pint --test (or run pint)
4. composer audit && npm audit  (address new vulns)
5. php -l on changed .php if no full suite
6. Manual smoke if external CLIs involved (or rely on mocks in CI)

Post change: update CHANGELOG.md (Keep a Changelog), increment version in README badge/config if release.
Run in worktree or clean branch; never commit vendor/ or node_modules/.