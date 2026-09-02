# Security

Report vulnerabilities privately through GitHub Security Advisories. Do not include public share tokens or production credentials in an issue.

The API resolves paths through the signed-in user's `IRootFolder` and returns aggregate flags only. It never returns recipient identities, source paths, tokens, or passwords. Indexed tables are disposable cache data.
