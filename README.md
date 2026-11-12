# one-file-analytics

**Your entire analytics stack. One PHP file.**

I run a portfolio and a small SaaS on $3/month shared hosting. I wanted to know which referrers actually earn their link and which pages pull people in. The industry answer is a JS tag, a database, a consent banner, and a third-party that owns the data. My answer was one PHP file that reads the raw access logs cPanel was already writing anyway.

```
no analytics tag   no database   no cookies   no consent banner
```

## How it thinks

- **Raw logs are the source.** Parses cPanel combined-format access logs (plain + `.gz`) straight from disk. Nothing is instrumented; everything is *witnessed*.
- **Newest first, on a budget.** Log files are sorted and tail-read under an 8 MB cap, so the gauge stays live even when the log directory is a month of gzips.
- **The charts are divs.** Referrer bars are `<div>`s with width percentages, so a bar is only ever as long as the real number. No chart library, no canvas, no rounding.
- **Provenance in the footer.** A snapshot cache (`.stats-cache.json`, 10-minute TTL) serves the parsed report, and the footer tells you its age and how many log files were parsed - because a number without provenance is just a vibe.
- **Honest about gaps.** "direct / none" is not loyalty - chat-app webviews strip the referrer header, and the gauge shows that wall as-is instead of inventing a source. Internal hits are labeled `internal`, not laundered into a channel.

## The scars (why the guards exist)

- `filesize()` on a gzip stream returns the *compressed* size - an earlier version silently stopped reading the newest lines and every chart still looked healthy. `$readTail` now streams the tail, gzip or not.
- An orphaned `fclose()` on first render 500'd the whole dashboard. Fixed by making every failure a *verdict* instead of an exception.
- The no-op `if` that used to sit in the line loop is gone from this copy; the needle checks are the kind of thing that survives a 2 a.m. edit.

## Status

`src/stats.php` is the production extract (hosts sanitized to `example.com`, everything else verbatim as it runs). `docs/` runs a self-contained demo with synthetic data. Drop `stats.php` in your docroot, enable Raw Access logs in cPanel, done.

## License

MIT. See [LICENSE](LICENSE).
