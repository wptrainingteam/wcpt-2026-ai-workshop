# Facilitator Notes — WordPress AI Building Blocks Workshop

**Session:** Stop Doing It Yourself: Building AI-Powered Admin Tools with the WordPress AI API
**Duration:** 3 hours 30 minutes | Saturday 3:00 PM WEST
**Audience:** Intermediate–Advanced WordPress plugin developers

---

## Before the Workshop

- Confirm your demo machine is running WordPress Studio with WP 7.0 RC2
- Have the WordPress/ai plugin installed and Summarization experiment enabled
- Have your own API key configured and tested — confirm a summary generates
- Have Claude Desktop (or Cursor) connected to the site via MCP for the Section 6 demo (optional)
- Load the WP Playground blueprint link and confirm it works as a fallback
- Have the `wp-ai-workshop` repo cloned and dependencies installed
- Cue up the source files you'll be navigating in Section 1

---

## Timing Overview

| Section | Title | Target Time | Cumulative |
|---------|-------|-------------|------------|
| 1 | Tour AI Experiments Plugin | 10 min | 0:10 |
| 2 | Scaffold + AI API | 20 min | 0:30 |
| 3 | Register the Ability | 20 min | 0:50 |
| 4 | Block Editor Integration (`apiFetch`) | 20 min | 1:10 |
| 5 | *Optional* — `@wordpress/abilities` rebuild | 10 min (or skip) | 1:20 |
| 6 | *Optional* — MCP Demo | 10 min (or skip) | 1:30 |
| Break | — | 5 min | 1:35 |
| 7 | *Optional* — Hackathon | up to 2 hrs | 3:35 |

Required path: Sections 1–4. Sections 5, 6, and the hackathon are bonus material. If the room is moving fast, walk through Section 5 to show the WP-native alternative; if it's running tight, skip straight from Section 4 to the MCP demo or the wrap-up. Trim Sections 5/6 first if you need to recover time — attendees have a complete, working feature at the end of Section 4.

---

## Section-by-Section Notes

### Section 1 — Tour the AI Experiments Plugin (10 min)

**Goal:** Set context for what we're rebuilding and why.

**Talking points:**
- The WordPress/ai plugin is the official reference implementation — maintained by Automattic and the WordPress AI team
- Note the two-class pattern: Experiment (wiring) vs. Ability (logic) — this separation will make sense when we build our own
- Emphasize: "Production code is correct. Workshop code is clear. Both patterns are valid."

**Common sticking points:**
- API key not configured → walk them to Settings → Connectors
- Summary button not showing → confirm the experiment is toggled on AND a provider is active

---

### Section 2 — Scaffold + AI Client (20 min)

**Goal:** Working plugin that makes a live AI request.

**Talking points:**
- `wp_ai_client_prompt()` abstracts all providers — the same code works with Anthropic, OpenAI, Google
- The guard clause (`function_exists`) is the right pattern for APIs still landing in core
- "We're removing the test function after confirming it works — this is scaffolding, not production code"

**Common sticking points:**
- `wp_ai_client_prompt` not found / fatal error → WP 7.0 RC not active, or the function isn't available yet
- WP_Error returned → API key invalid or provider unreachable

---

### Section 3 — Register the Ability (20 min)

**Goal:** Ability registered and callable via REST API.

**Talking points:**
- The `wp_abilities_api_init` hook is the right time to register — don't call `wp_register_ability()` directly outside a hook
- The input/output schema uses JSON Schema format — same standard used across the WP REST API
- "WordPress created this REST endpoint for us — we didn't write a single `register_rest_route()` call"
- Show the Abilities Explorer (Settings → AI → Abilities Explorer) to visualize what was registered

**Common sticking points:**
- `wp.apiFetch is not a function` in the console → they're on the front end or inside the post-editor iframe. Have them switch to **Posts → All Posts** (or any plain wp-admin page) and rerun.
- REST endpoint 404 → Abilities API not available, check WP version
- `apiFetch` returns `undefined` or the request 4xxs → they forgot the `input` wrapper in the `data` payload, or `length` isn't one of the enum values. Check the Network tab for the response body.
- `is_wp_error` returning true on the server → usually a rate limit or invalid key, not a code error

---

### Section 4 — Block Editor Integration (20 min)

**Goal:** Button in sidebar that generates and inserts a summary block, talking to the same REST endpoint they hit from the console in Section 3.

**Talking points:**
- `PluginPostStatusInfo` is a SlotFill — it renders into a designated slot in the editor sidebar without modifying core templates
- `apiFetch` is the same thing as `wp.apiFetch` from Section 3, now imported into the plugin's JS. Same path, same `input` wrapper on the request, same plain-string response (because our `output_schema` is `type: 'string'`).
- The enqueue is a plain `wp_enqueue_script()`. `@wordpress/scripts` writes the dependency array into `index.asset.php` — `wp-api-fetch`, `wp-plugins`, `wp-editor`, etc. all come along for free. No script-module loader, no dynamic import, no shim enqueues.
- `insertBlock` at position `0` puts it at the top of the content, matching what the reference plugin does
- We hook on `enqueue_block_editor_assets` — it only fires in the block editor, so no screen check is needed.

**Why we lead with `apiFetch` instead of `@wordpress/abilities`:**

The first delivery of this workshop ran the abilities-package version here and the script-module dance lost the room. The simpler tracer bullet — plain enqueue, one classic import, one REST call — gets to a working AI feature in the editor with much less ceremony. If someone asks "is this the WordPress way?", say: it *is* one of two supported paths, and Section 5 shows the other one with its tradeoffs.

**Common sticking points:**
- Button appears but nothing happens → check browser console for JS errors, likely a build wasn't triggered (`npm start` not running)
- `summary` is `undefined` or the request 4xxs → they forgot the `input` wrapper in the `data` payload, or `length` isn't one of the enum values. Show them the Network tab response body.
- 403 / `rest_forbidden` → user lacks `edit_posts`. The permission_callback we wrote in Section 3 is doing its job.
- Block inserts but is empty → `serialize( blocks )` returned empty string — confirm there's actual content in the editor

---

### Section 5 — *Optional* `@wordpress/abilities` rebuild (10 min)

**Goal:** Show the WordPress-native client for the same feature and explain why we didn't lead with it. Skip if running tight on time — attendees already have a working feature.

**Talking points:**
- We're calling the *same* REST endpoint. The difference is purely on the JavaScript side: `executeAbility` removes the `input` wrapper on the request and surfaces typed errors (`ability_permission_denied`, `ability_invalid_input`, `ability_invalid_output`).
- It also gives you a `useSelect`-able data store of registered abilities and a JS API for registering them — useful if your UI needs to branch on what's available.
- Trade-off: the package is published only as a runtime ES module via the WordPress script module loader. That forces the more involved enqueue + the top-level `await import( /* webpackIgnore: true */ ... )`.

**The "weird" enqueue + dynamic import — be ready to explain this clearly:**

Walk through it deliberately:

- `@wordpress/scripts` builds our file as a *classic* script, not an ES module. But `@wordpress/abilities` is published *only* through the WordPress script module loader — it isn't a classic script and webpack can't resolve it at build time.
- To make `@wordpress/abilities` available we have to enqueue our file with `wp_enqueue_script_module()` and declare `@wordpress/abilities` as a script-module dependency. So we end up with a classic-script body enqueued through the script-module system. Weird, but correct.
- On the JS side we use a top-level `await import( /* webpackIgnore: true */ '@wordpress/abilities' )`. The `webpackIgnore` comment tells webpack "don't resolve this at build time — leave it alone." The browser then fetches it at runtime via the script module loader.
- The two `wp_enqueue_script_module( '@wordpress/core-abilities' / '@wordpress/abilities' )` shim calls go away once 7.0 ships and these auto-register.

If someone asks "why not just import it normally?" the one-liner is: *the package isn't classic-script-compatible and webpack can't see it; the script module loader is the only way in.*

**Common sticking points:**
- `executeAbility` is undefined → either the dynamic `await import()` failed (check the Network tab for a 404 on `@wordpress/abilities`) or they kept a static `import { executeAbility } from '@wordpress/abilities'` and webpack tried to resolve it at build time. Confirm they're using the `await import( /* webpackIgnore: true */ ... )` pattern.
- Webpack build error mentioning `@wordpress/abilities` not found → the `/* webpackIgnore: true */` comment is missing or malformed (must be inside the `import()` parentheses, not on a preceding line).
- Module loader misbehaves on a particular machine → fall back to the Section 4 `apiFetch` path, which they already have working.

---

### Section 6 — *Optional* MCP Demo (10 min)

**Goal:** Show the payoff of the `meta.mcp.public` opt-in attendees added in Section 3 — the ability is reachable by an external AI agent without any further code.

**Talking points:**
- MCP is an open protocol — not WordPress-specific, not Anthropic-specific
- "One line of opt-in — `meta.mcp.public => true` — and the Abilities API + MCP Adapter handle the rest."
- Point to the MCP endpoint URL: `/wp-json/wp-abilities/v1/mcp`
- Heads-up for attendees: on the default MCP server the ability is reached through the adapter's `discover-abilities` / `execute-ability` meta-tools — agents won't see `wp-ai-workshop/summarization` listed directly in `tools/list`. Worth saying out loud before showing the Claude Desktop tool list, so it doesn't look broken.

**If the demo fails:**
- Have a screen recording ready as a backup
- Walk through the concept verbally with the endpoint URL — the idea lands even without a live demo

---

### Break (5 min)

Announce the (optional) hackathon suggestions before the break so attendees can think about what they want to build. Point them to `workshop-outline/section-7.md`.

---

### Section 7 — *Optional* Hackathon (up to 2 hours)

**Goal:** Attendees build their own ability using the same pattern. Optional — anyone who'd rather wrap up here has already shipped a working AI feature.

**Facilitation tips:**
- Walk the room — help individually rather than calling out solutions to the whole group
- If someone is stuck on the PHP side, suggest hitting their new ability from the wp-admin console with `wp.apiFetch` (the same pattern from Section 3) to confirm it works before touching JS
- If someone finishes early, push them toward the MCP stretch goal or connecting it to Claude Desktop
- Save 5 minutes at the end to call on 2-3 people to share what they built

**Common questions:**
- "Can I use a different post type?" → Yes — just make sure it `show_in_rest: true`
- "How do I return structured data?" → Return JSON from the AI and `json_decode()` in the execute callback; update `output_schema` to `type: 'object'`
- "How do I write to a post field?" → `dispatch( 'core/editor' ).editPost( { excerpt: result } )` for excerpt, similar pattern for other fields

---

## API Key Fallback Plan

If multiple attendees can't get API keys working:
1. Direct them to the WP Playground blueprint — the demo site has a pre-configured provider
2. If Automattic arranged a shared key, distribute it via the session Slack channel or whiteboard

---

## Wrap-Up

- Point attendees to the [WordPress/ai GitHub repo](https://github.com/WordPress/ai) for the production version of everything we built
- Mention the `#core-ai` channel on WordPress Slack for ongoing discussion
- Encourage them to submit experiments back to the AI plugin
