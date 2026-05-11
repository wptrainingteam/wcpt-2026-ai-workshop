# Slides Outline — Stop Doing It Yourself

### WordCamp Portugal 2026

---

## Title Slide

- Stop Doing It Yourself: Building AI-Powered Admin Tools with the WordPress AI API
- WordCamp Portugal 2026 | Saturday 3:00 PM
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
- **MCP** — via the `mcp-adapter` package (bundled with the AI plugin) — abilities auto-exposed to agents

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
    wp_register_ability_category( 'wcpt', [ 'label' => 'WC Portugal 2026' ] );
});
```

### Register the Ability

```php
add_action( 'wp_abilities_api_init', function() {
    wp_register_ability( 'wcpt/summarization', [
        'category'            => 'wcpt',
        'input_schema'        => [ /* content, length */ ],
        'output_schema'       => [ 'type' => 'string' ],
        'permission_callback' => fn() => current_user_can( 'edit_posts' ),
        'execute_callback'    => 'wcpt_execute_summarization',
    ]);
});
```

### Test It

```bash
curl -X POST "/wp-json/wp-abilities/v1/abilities/wcpt/summarization/run" \
  -u "admin:app-password" \
  -d '{"input": {"content": "...", "length": "short"}}'
```

---

## Section 4: Block Editor Integration

### The Pattern

- `registerPlugin` — registers our code with the block editor
- `PluginPostStatusInfo` SlotFill — renders into the sidebar
- `useSelect` — reads post content from the editor store
- `executeAbility` — calls the ability (no manual REST request needed)
- `insertBlock` — inserts the summary as a paragraph block

### executeAbility()

```javascript
import { executeAbility } from "@wordpress/abilities";

const summary = await executeAbility("wcpt/summarization", {
  content,
  length: "medium",
});
```

- Handles REST call, authentication, error handling
- Returns the ability output directly

### The Full Component

- [Show final code slide — see section-4.md for complete snippet]

---

## Section 5: MCP

### What Is MCP?

- Model Context Protocol — open standard for AI agent tool use
- Any MCP-compatible client (Claude Desktop, Cursor) can discover + call tools
- WordPress exposes registered abilities at `/wp-json/wp-abilities/v1/mcp`

### The Key Point

- You wrote zero MCP code
- The Abilities API did it for you
- Same ability. Three callers: human UI, REST API, AI agent

### Live Demo

- [Claude Desktop calls wcpt/summarization on a post]

---

## Section 6: Hackathon

### Build Your Own Ability

- Same three-layer pattern: PHP → Ability → JS trigger
- 2 hours
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
- A block editor button powered by `@wordpress/abilities`
- An MCP-accessible tool — for free

### Where to Go Next

- [wordpress.github.io/ai](https://github.com/WordPress/ai) — reference plugin
- `#core-ai` on WordPress Slack
- [developer.wordpress.org/apis/abilities-api/](https://developer.wordpress.org/apis/abilities-api/)
- [@wordpress/abilities docs](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)

### Questions?

- Ryan — WordPress Slack: @ryanwelcher
- JuanMa — WordPress Slack: @JuanMa
