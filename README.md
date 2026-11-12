# one-file-analytics

**Your entire analytics stack. One PHP file.**

I run a portfolio and a small SaaS on $3/month shared hosting. I wanted to know three things: how many *humans* visited, which referrers earned their link, and whether today's numbers were lying to me.

The industry answer is a JS tag, a database, a consent banner, and a third-party that owns the data. My answer was one PHP file that reads the raw access logs cPanel was already writing anyway.

```
no javascript   no database   no cookies   no consent banner
```

## How it thinks

- **Raw logs are the source.** Parses cPanel combined-format access logs (plain + `.gz`) straight from disk. Nothing is instrumented; everything is *witnessed*.
- **Bots are filtered by behavior, not by list.** The tell is the asset load: a real visit loads the page shell and fires assets; a scanner hits one URL and vanishes. Sessions that never load CSS get counted as noise.
- **Nothing identifying is stored.** IPs are hashed with a daily salt. The salt rotates, so the hash never survives the day. GDPR-adjacent by accident, which is the best kind.
- **Today is flagged, not trusted.** My favorite bug survived two weeks: every historical number looked plausible and today's was confidently wrong. Now the dashboard says so out loud instead of averaging a lie into the trend.
- **The charts are divs.** Referrer bars are `<div>`s with width percentages, so a bar is only ever as long as the real number. No chart library, no canvas, no rounding.

## The scars (why the guards exist)

- `filesize()` on a gzip stream returns the *compressed* size - my parser silently stopped reading the newest lines and every chart still looked healthy. Fixed by parsing the stream, not trusting the wrapper.
- An orphaned `fclose()` on first render 500'd the whole dashboard. Fixed by making every failure a *verdict* instead of an exception.
- A snapshot cache serves the parsed report for 10 minutes. The footer tells you the cache age and how many log files were parsed, because a number without provenance is just a vibe.

## Status

`docs/` runs a self-contained demo with synthetic data (no fetches, safe for GitHub Pages). The production-verbatim `src/stats.php` extraction lands via the paste workflow - see `src/README.md`.

## License

MIT. See [LICENSE](LICENSE).
