# M-9: one CodeCave status command; store tests offered, not required (owner decision, 2026-10-25)

**Status: decided, not started. Nothing in this repository changes behaviour yet.**

- **Core:** CodeCave_Core gets a read-only status command stub (for example `bin/magento codecave:status`) with an extension point every CodeCave module plugs its own health checks into, so the one core command becomes the full status of the install.
- **Later:** the same results in the admin panel and through MCP (MCP is built later).
- **Who:** the owner starts the core work himself, in `codecaveeu/module-core` (hub project `mod-core`).
- **Every other module:** when it adopts the core stub, it registers its own health checks. Read only: no writes, no mail, no partner calls.
- **Store tests:** a module may ship its whole `Test/` and run it in a guarded hub shop lane before a deploy. Client projects are **not** required to test before every deploy: they are told the capability exists, and the choice is theirs.

This file is the same, at the same path, in every CodeCave module repository, so whoever starts the work finds it.

## The decision

- Decided by the owner in Judge on 2026-10-25 (passkey verified), project `ccmods`.
- Decision page: https://judge.codecave.eu/page/pg_3dvcnzjhrwes ("M-9: how every CodeCave module proves itself on the client store before a deploy"). It holds the options, the reasoning, the runner and guard proposal, the cost estimates and the sources.
- Card: `dc_vhpy3uv7hpdh` (M-9) on that page. It replaces the earlier M-9 card `dc_axpycenymzpv`.
- He checked option **D**, "B and C: lane proof before deploy, self-check after", where:
  - **B** = "Ship Test/ whole; run it in a guarded hub shop lane"
  - **C** = "A read-only self-check command per module"
- His notes change D in two ways:
  - the per-module self-check becomes one shared status command in Core that each module adds to;
  - testing in a lane before a deploy becomes a capability offered to client projects, not a requirement.

The owner's words, verbatim (line breaks as he wrote them):

> checked [D]; notes: B and c BUT strategic changes.
> First. I want a stub of the self check command in the core module that every other module includes. Then each module can append its health to the status stub creating in effect a full status command. That should also be visible in the admin panel and in the mcp (thtis will be built later, log it in core). There is already an established core project on the hub, log this decision there and i will start it myself later.
> Log it for the other modules as well in the same positions
> However dont require client projects to test before every deploy. Mention the capability and leave it to them.

## 1. In the core module (CodeCave_Core)

- **A status command stub, read only.** Example name: `bin/magento codecave:status`; the name is not fixed yet.
- **An extension point** that every CodeCave module plugs its health checks into. Nearly every CodeCave module already requires `codecave/module-core` and lists `CodeCave_Core` in its `etc/module.xml` `<sequence>`, so each can register its checks with the core command.
- **The result:** one command that reports the full status of the install, every installed CodeCave module included.
- **Later, the same results:**
  - in the admin panel;
  - through MCP (`codecave/module-mcp`), built later.
- **Who:** the owner starts this himself.
- **Boundary note:** today Core is "names only" (its `CLAUDE.md`: no PHP class, route, plugin, observer, cron job or table). The status command is Core's first code, so that text changes with this work.

## 2. In every other CodeCave module

When a module adopts the core stub, it registers its own health checks with it. Every check is read only:

- **May read:** config, wiring (module enabled, schema current, DI and plugins), the mailer or partner stack, storage, cron.
- **Never:** writes a row or a file, sends mail, or calls a partner.

Nobody sweeps: each module adopts the stub at its own next release.

## 3. Store tests: a capability, not a rule

- **Offered:** a module may ship its whole `Test/` (Unit, Integration, Contract) in its package and run it in a guarded hub shop lane before a deploy (option B on the decision page).
  - A hub shop lane is a localized copy of the client's store with mail going to Mailpit.
  - The page proposes a guard that refuses production mode, a host that is not a lane, and any firing production marker, before any suite runs.
- **Not required:** client projects are not required to test before every deploy. Each client project is told the capability exists; the choice is theirs.
- **Inert on production:** `setup:di:compile` skips each module's `Test/`, and both hub client shops deploy with `composer install --no-dev`, so production has no PHPUnit.

## 4. What changes later, and what stays

- **Will change:** the module template rule on shipping `Test/` (hard rule 16 and `templates/gitattributes` in the retired ccmods factory, which today ship `Test/Unit`, `Test/Contract` and `Test/TESTING.md`). It changes with this work.
- **Stays for now:** the CodeCave Mail group (`module-mail`, `module-mail-attachments`, `module-mail-klaviyo`) kept `Test/` out of its 2.0.0 packages by the owner's decision M-6 B.

## Status

- Not started. Nothing is built; no code or config changes with this record.
- Background, on hephaestus (not in this repository):
  - `/home/hub/projects/mod-mail-dev/.operation-artifacts/meaning-pages/DECISIONS-ccmods-module-self-test.html` (the decision page's source)
  - `/home/hub/projects/mod-mail-dev/.operation-artifacts/mail-249/scout-testdir/findings.md` (the research behind it)
