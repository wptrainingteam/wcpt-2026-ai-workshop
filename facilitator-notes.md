# Facilitator Notes — WordCamp Portugal 2026

**Session:** Stop Doing It Yourself: Building AI-Powered Admin Tools with the WordPress AI API
**Duration:** 3 hours 30 minutes | Saturday 3:00 PM WEST
**Audience:** Intermediate–Advanced WordPress plugin developers

---

## Before the Workshop

- Confirm your demo machine is running WordPress Studio with WP 7.0 RC
- Have the WordPress/ai plugin installed and Summarization experiment enabled
- Have your own API key configured and tested — confirm a summary generates
- Have Claude Desktop (or Cursor) connected to the site via MCP for the Section 5 demo
- Load the WP Playground blueprint link and confirm it works as a fallback
- Have the `wcpt-2026-content-summarization` repo cloned and dependencies installed
- Cue up the source files you'll be navigating in Section 1

---

## Timing Overview

| Section | Title | Target Time | Cumulative |
|---------|-------|-------------|------------|
| 1 | Tour AI Experiments Plugin | 15 min | 0:15 |
| 2 | Scaffold + AI API | 25 min | 0:40 |
| 3 | Register the Ability | 25 min | 1:05 |
| 4 | Block Editor Integration | 25 min | 1:30 |
| 5 | MCP Demo | 10 min | 1:40 |
| Break | — | 10 min | 1:50 |
| 6 | Hackathon | 2 hrs | 3:50 |

The 3:50 total gives a small buffer for questions. If sections run long, trim Section 5 first — it's demo-only and the audience has already seen the pattern.

---

## Section-by-Section Notes

### Section 1 — Tour the AI Experiments Plugin (15 min)

**Goal:** Set context for what we're rebuilding and why.

**Talking points:**
- The WordPress/ai plugin is the official reference implementation — maintained by Automattic and the WordPress AI team
- Note the two-class pattern: Experiment (wiring) vs. Ability (logic) — this separation will make sense when we build our own
- Emphasize: "Production code is correct. Workshop code is clear. Both patterns are valid."

**Common sticking points:**
- API key not configured → walk them to Settings → Connectors
- Summary button not showing → confirm the experiment is toggled on AND a provider is active

---

### Section 2 — Scaffold + AI Client (25 min)

**Goal:** Working plugin that makes a live AI request.

**Talking points:**
- `wp_ai_client_prompt()` abstracts all providers — the same code works with Anthropic, OpenAI, Google
- The guard clause (`function_exists`) is the right pattern for APIs still landing in core
- "We're removing the test function after confirming it works — this is scaffolding, not production code"

**Common sticking points:**
- `wp_ai_client_prompt` not found / fatal error → WP 7.0 RC not active, or the function isn't available yet
- WP_Error returned → API key invalid or provider unreachable
- Composer autoloader issues → run `composer install` again from the plugin root

---

### Section 3 — Register the Ability (25 min)

**Goal:** Ability registered and callable via REST API.

**Talking points:**
- The `wp_abilities_api_init` hook is the right time to register — don't call `wp_register_ability()` directly outside a hook
- The input/output schema uses JSON Schema format — same standard used across the WP REST API
- "WordPress created this REST endpoint for us — we didn't write a single `register_rest_route()` call"
- Show the Abilities Explorer (Settings → AI → Abilities Explorer) to visualize what was registered

**Common sticking points:**
- `curl` authentication failing → make sure they're using an Application Password, not their login password
- REST endpoint 404 → Abilities API not available, check WP version
- `is_wp_error` returning true → usually a rate limit or invalid key, not a code error

---

### Section 4 — Block Editor Integration (25 min)

**Goal:** Button in sidebar that generates and inserts a summary block.

**Talking points:**
- `PluginPostStatusInfo` is a SlotFill — it renders into a designated slot in the editor sidebar without modifying core templates
- `executeAbility()` from `@wordpress/abilities` handles the REST call, authentication, and error handling — much cleaner than raw `apiFetch`
- `insertBlock` at position `0` puts it at the top of the content, matching what the reference plugin does

**The "weird" enqueue + dynamic import — be ready to explain this clearly:**

This is the part that confuses people. Walk through it deliberately:

- `@wordpress/scripts` builds our file as a *classic* script, not an ES module. But `@wordpress/abilities` is published *only* through the WordPress script module loader — it isn't a classic script and webpack can't resolve it at build time.
- To make `@wordpress/abilities` available we have to enqueue our file with `wp_enqueue_script_module()` and declare `@wordpress/abilities` as a script-module dependency. So we end up with a classic-script body enqueued through the script-module system. Weird, but correct.
- On the JS side we use a top-level `await import( /* webpackIgnore: true */ '@wordpress/abilities' )`. The `webpackIgnore` comment tells webpack "don't resolve this at build time — leave it alone." The browser then fetches it at runtime via the script module loader.
- We hook on `admin_enqueue_scripts` (not `enqueue_block_editor_assets`) because script-module enqueueing for this scenario only registers correctly on that hook today. The `get_current_screen()` check keeps us off non-edit admin pages.
- The two `wp_enqueue_script_module( '@wordpress/core-abilities' / '@wordpress/abilities' )` shim calls go away once 7.0 ships and these auto-register.

If someone asks "why not just import it normally?" the one-liner is: *the package isn't classic-script-compatible and webpack can't see it; the script module loader is the only way in.*

**Common sticking points:**
- Button appears but nothing happens → check browser console for JS errors, likely a build wasn't triggered (`npm start` not running)
- `executeAbility` is undefined → either the dynamic `await import()` failed (check the Network tab for a 404 on `@wordpress/abilities`) or they kept a static `import { executeAbility } from '@wordpress/abilities'` and webpack tried to resolve it at build time. Confirm they're using the `await import( /* webpackIgnore: true */ ... )` pattern.
- Webpack build error mentioning `@wordpress/abilities` not found → the `/* webpackIgnore: true */` comment is missing or malformed (must be inside the `import()` parentheses, not on a preceding line).
- Browser console shows a module resolution error for `@wordpress/abilities` → script module loader path didn't run. Confirm the enqueue uses `wp_enqueue_script_module()` (not classic `wp_enqueue_script`), is hooked on `admin_enqueue_scripts` (not `enqueue_block_editor_assets`), and the `@wordpress/core-abilities` + `@wordpress/abilities` shim enqueues are present.
- Code on `enqueue_block_editor_assets` "looks right" but nothing loads → wrong hook for this scenario; move to `admin_enqueue_scripts` with the screen check.
- Block inserts but is empty → `serialize( blocks )` returned empty string — confirm there's actual content in the editor

**Fallback if script modules misbehave on the day:**
If an attendee's environment refuses to load the script module, point them to the `apiFetch` alternative in the `<details>` block at the end of `section-4.md`. It uses classic script enqueue and calls the REST endpoint directly — same behaviour, no module loader required.

---

### Section 5 — MCP Demo (10 min)

**Goal:** Show that registered abilities are already MCP-accessible — no extra code.

**Talking points:**
- MCP is an open protocol — not WordPress-specific, not Anthropic-specific
- "You wrote zero MCP code. The Abilities API did it for you."
- Point to the MCP endpoint URL: `/wp-json/wp-abilities/v1/mcp`

**If the demo fails:**
- Have a screen recording ready as a backup
- Walk through the concept verbally with the endpoint URL — the idea lands even without a live demo

---

### Break (10 min)

Announce the hackathon suggestions before the break so attendees can think about what they want to build. Point them to `workshop-outline/section-6.md`.

---

### Section 6 — Hackathon (2 hours)

**Goal:** Attendees build their own ability using the same pattern.

**Facilitation tips:**
- Walk the room — help individually rather than calling out solutions to the whole group
- If someone is stuck on the PHP side, suggest starting with `curl` to confirm the ability works before touching JS
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
