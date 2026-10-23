# Project instructions

@/Users/mohamedselem/.codex/RTK.md

## Design and UI

For public website UI work, read and follow [the project design guidelines](docs/design-guidelines.md). They record the user-approved design direction and rejected experiments. They are flexible guidance, not a mandatory visual template. The user's latest instruction takes precedence. Do not reintroduce removed external design-skill constraints or install design skills without being asked.

Explain meaningful design decisions concisely and proceed within the authorized scope; do not ask for repeated approval of routine details. Preserve CMS content, publication and preview behavior, custom section ordering, and unrelated local changes. Verify the rendered result and the interactions affected by the change. Rebuild generated site CSS after source styling edits.

## Server access requires explicit permission

Never connect to any remote server without the user's explicit permission for that specific server and purpose. This includes SSH, remote shells, diagnostic requests, log reads, deployment tools, and trying alternate servers after a failed connection. Existing SSH aliases, credentials, previous access, or a general debugging request do not constitute permission. Local repository inspection and local checks may continue without remote access.

## Delegation

For non-trivial work, consider bounded delegation when it saves time or lead-model tokens without reducing quality. Lightweight subagents are authorized for well-defined work. The lead owns architecture, integration, and final review; review changes and verify behavior rather than relying on agent reports alone. Avoid overlapping edits, duplicate investigation, and delegation for trivial edits. Delegation does not authorize remote access, external writes, secrets, paid providers, or external CLIs.

## API tooling

Postman CLI is installed on this device and may be used for API engineering work, subject to the same access permissions.
