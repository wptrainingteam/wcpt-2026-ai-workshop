# Slides Outline — Stop Doing It Yourself

### WordPress AI Building Blocks Workshop

---

## Title Slide

- Stop Doing It Yourself: Building AI-Powered Admin Tools with the WordPress AI API
- WordPress AI Building Blocks Workshop | Saturday 3:00 PM
- Ryan Welcher — Developer Advocate, Automattic
- JuanMa Garrido — Developer Advocate, Automattic

---

## Who Are We?

- **Ryan Welcher** — Developer Advocate at Automattic
  - Working on WordPress developer tools and AI integrations
  - Active in #core-editor and #core-ai on WordPress Slack
- **JuanMa Garrido** — Developer Advocate at Automattic
  - WordPress Slack: @JuanMa

---

## What We're Building Today

- A Content Summarization plugin — from scratch
- Based on the same feature in the official WordPress/ai plugin
- Simpler version so the pattern is clear
- [Show screenshot of the finished result — summary block inserted in editor]

---

## The Three Building Blocks

- **PHP AI Client** (new in core 7.0) — `wp_ai_client_prompt()` — talk to any AI provider with one API
- **Abilities API** — `wp_register_ability()` — PHP/REST in 6.9, JavaScript client API in 7.0
- **MCP** — via the `mcp-adapter` package (bundled with the AI plugin) — abilities exposed to agents via a `meta.mcp.public` opt-in

---

## Provider Support

- Anthropic (Claude)
- OpenAI (GPT and DALL·E)
- Google (Gemini and Imagen)
- Same code works with all of them

---

## Setup

- WordPress Studio — [developer.wordpress.com/studio](https://developer.wordpress.com/studio/)
- WordPress 7.0 RC (required — AI APIs ship in 7.0)
- WP Playground fallback: [link]
- API key: Anthropic / OpenAI / Google
- Clone the repo, `npm install`

---

## Workshop Structure

- 5 guided sections (1 tour + 3 coding + 1 MCP demo, ~90 min)
- 10 min break
- 2 hour hackathon
- `code-reference/section-N/` available for each coding section if you fall behind

---

## Section 1: Tour the AI Experiments Plugin

### What Is It?

- Official WordPress/ai plugin — reference implementation
- Ships Content Summarization, Excerpt Generation, Alt Text, and more
- Built on the same APIs we'll use

### The Separation Pattern

- **Experiment** class — wires up hooks, assets, REST routes
- **Ability** class — defines schema, permissions, executes AI call
- This pattern keeps concerns clean and testable

### Why We Rebuild It

- Production code is correct but dense
- Workshop version isolates the core pattern
- Every line will be explainable

---

## Section 2: Scaffold + AI API

### The Plugin Header

- `Requires at least: 7.0`
- Guard clause: `function_exists( 'wp_ai_client_prompt' )`

### wp_ai_client_prompt()

```php
$response = wp_ai_client_prompt( 'Your prompt here' )->generate_text();
```

- Provider-agnostic — works with Anthropic, OpenAI, Google
- Returns a string or WP_Error

### Confirm It Works

- Display result as admin notice
- Check Settings → Connectors if you get an error

---

## Section 3: The Abilities API

### What Is an Ability?

- A named, discoverable, executable unit of AI functionality
- Defined with a JSON Schema input/output contract
- Automatically exposed as a REST endpoint

### Register a Category

```php
add_action( 'wp_abilities_api_categories_init', function() {
    wp_register_ability_category( 'wp-ai-workshop', [ 'label' => 'WordPress AI Workshop' ] );
});
```

### Register the Ability

```php
add_action( 'wp_abilities_api_init', function() {
    wp_register_ability( 'wp-ai-workshop/summarization', [
        'category'            => 'wp-ai-workshop',
        'input_schema'        => [ /* content, length */ ],
        'output_schema'       => [ 'type' => 'string' ],
        'permission_callback' => fn() => current_user_can( 'edit_posts' ),
        'execute_callback'    => 'wp_ai_workshop_execute_summarization',
    ]);
});
```

### Test It — from the wp-admin console

```js
const summary = await wp.apiFetch( {
    path: '/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run',
    method: 'POST',
    data: { input: { content: '...', length: 'short' } },
} );

console.log( 'Summary:', summary );
```

- No application password needed — cookie + nonce
- Same `input` wrapper + plain-string response our JS will use in Section 4

---

## Section 4: Block Editor Integration

### The Pattern

- `registerPlugin` — registers our code with the block editor
- `PluginPostStatusInfo` SlotFill — renders into the sidebar
- `useSelect` — reads post content from the editor store
- `apiFetch` — calls the auto-generated REST endpoint (same one we hit from the console in Section 3)
- `insertBlock` — inserts the summary as a paragraph block

### apiFetch()

```javascript
import apiFetch from "@wordpress/api-fetch";

const summary = await apiFetch( {
    path: "/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run",
    method: "POST",
    data: { input: { content, length: "medium" } },
} );
```

- Plain `wp_enqueue_script` — no script-module loader
- Same `input` wrapper + plain-string response as the console test in Section 3

### The Full Component

- [Show final code slide — see section-4.md for complete snippet]

---

## Section 5: *Optional* — `@wordpress/abilities` rebuild

### Same Feature, WP-Native Client

- One REST endpoint, two JS clients
- `executeAbility( 'wp-ai-workshop/summarization', { content, length } )` — hides the `input` wrapper on the request, surfaces typed errors
- Trade-off: script-module enqueue + `await import( /* webpackIgnore: true */ '@wordpress/abilities' )`

### When to Pick Which

| Need… | Pick… |
|---|---|
| Simplest plumbing | `apiFetch` |
| Typed errors + reactive abilities store | `@wordpress/abilities` |

---

## Section 6: *Optional* — MCP

### What Is MCP?

- Model Context Protocol — open standard for AI agent tool use
- Any MCP-compatible client (Claude Desktop, Cursor) can discover + call tools
- The MCP Adapter publishes abilities opted in via `meta.mcp.public` at `/wp-json/wp-abilities/v1/mcp`

### The Key Point

- One line of opt-in: `'mcp' => array( 'public' => true )`
- The Abilities API + MCP Adapter handle discovery and invocation
- Same ability. Three callers: human UI, REST API, AI agent

### Live Demo

- [Claude Desktop calls wp-ai-workshop/summarization on a post]

---

## Section 7: *Optional* — Hackathon

### Build Your Own Ability

- Same three-layer pattern: PHP → Ability → JS trigger
- Up to 2 hours (optional — stop here if you'd rather)
- Pick from the suggested builds or bring your own idea

### Suggested Builds

- Excerpt Generator
- Tag Suggester
- Meta Description Generator
- Title Variations
- Tone Analyzer
- Content Translator
- Comment Moderator

### Success Criteria

- Working REST endpoint
- At least one JS trigger in the block editor

---

## Wrap-Up

### What We Built

- A plugin that calls AI providers via a unified PHP client
- A registered Ability with a REST endpoint — automatically
- A block editor button calling that endpoint via `apiFetch` (with an optional `@wordpress/abilities` rebuild for the curious)
- An MCP-accessible tool — for free

### Where to Go Next

- [wordpress.github.io/ai](https://github.com/WordPress/ai) — reference plugin
- `#core-ai` on WordPress Slack
- [developer.wordpress.org/apis/abilities-api/](https://developer.wordpress.org/apis/abilities-api/)
- [@wordpress/abilities docs](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)

### Questions?

- Ryan — WordPress Slack: @ryanwelcher
- JuanMa — WordPress Slack: @JuanMa
