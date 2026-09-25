# Game wiki pages

These are the player documentation pages from the original Hacker Experience wiki, in
[DokuWiki](https://www.dokuwiki.org/) syntax (`en/` and `pt-br/` namespaces).

The DokuWiki 2014 engine that used to live in this folder was removed: it was end-of-life and had
known security vulnerabilities. To host the wiki:

1. Install a current DokuWiki release, preferably on its own subdomain.
2. Copy `pages/*` into DokuWiki's `data/pages/` directory.
3. Point `WIKI_URL` in the game's `.env` at it (the in-game help links append page ids such as
   `en:clans`). For example `WIKI_URL=https://wiki.example.com/doku.php?id=`.
