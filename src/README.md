# src/

`stats.php` - the production extract (bhstats1, Nov 11 2026). Sanitization
scope: hosts -> `example.com`, log-filter needle `bryanhamilton` ->
`example.com`. Everything else verbatim as it runs, including the bhglow1
inline style/script block (self-contained, guarded by `window.__BHGLOW1`).

Deploy: drop in your docroot, visit `/stats.php`. Logs expected at
`$home/logs/*` or `$home/access-logs/*` (cPanel Raw Access). Cache file:
`.stats-cache.json` (10-minute TTL) written next to the script.

Demo: [docs/index.html](../docs/index.html).
