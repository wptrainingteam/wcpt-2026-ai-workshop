# Stop Doing It Yourself: Building AI-Powered Admin Tools with the WordPress AI API

A hands-on workshop covering the WordPress 7.0 AI Building Blocks: the PHP AI Client, the Abilities API, and MCP. Designed for delivery as a half-day session at WordCamps, meetups, and similar events.

## Pre-Workshop Setup Checklist

To avoid wifi bottlenecks on the day, please complete this **before you arrive**:

1. **WordPress Studio** — Download and install [WordPress Studio](https://developer.wordpress.com/studio/).

2. **Create a site from the blueprint** — Download the [`blueprint.json`](https://raw.githubusercontent.com/wptrainingteam/wp-ai-workshop/trunk/blueprint.json) file from this repository. In Studio, click **Add site → Start from a blueprint → Choose blueprint file** and select the downloaded file.

3. **Node.js v20+** — Recommended via [NVM](https://github.com/nvm-sh/nvm):

    ```bash
    nvm install 20 && nvm use 20
    ```

4. **Install JS dependencies** in the workshop plugin directory:

    ```bash
    cd /path/to/your/studio/site/wp-content/plugins/wp-ai-workshop-trunk
    npm install
    ```

    > Studio installs the plugin from `trunk.zip`, so the directory ends up named `wp-ai-workshop-trunk` (with the `-trunk` suffix). Use that path here.

5. **Configure an API key** in your Studio site's WordPress admin under **Settings → Connectors**. Any of the following will work:
    - [Anthropic](https://console.anthropic.com/) (Text generation with Claude)
    - [OpenAI](https://platform.openai.com/) (Text and image generation with GPT and DALL·E)
    - [Google](https://aistudio.google.com/api-keys) (Text and image generation with Gemini and Imagen)

**Not using Studio?** For any other local WordPress installation you will need:

-   **[WordPress Beta Tester](https://wordpress.org/plugins/wordpress-beta-tester/)** — install and activate it, then go to **Tools → Beta Testing** and switch to the WordPress 7.0 RC channel to update your site to 7.0 RC4.
-   **[AI plugin](https://wordpress.org/plugins/ai/)** — install and activate from the WordPress plugin directory.
-   **This workshop plugin** — clone the repo into your `wp-content/plugins/` directory and activate it:

    ```bash
    cd /path/to/your/site/wp-content/plugins/
    git clone https://github.com/wptrainingteam/wp-ai-workshop
    cd wp-ai-workshop
    npm install
    wp plugin activate wp-ai-workshop
    ```

---

## Welcome!

Thanks for joining us today! We have 3.5 hours together — about 90 minutes of guided instruction, then a 2-hour hackathon. We'll move at a steady pace, but there's room for questions along the way.

## What Are We Building?

WordPress 7.0 RC4 brings together three AI building blocks for the first time:

-   **PHP AI Client** — new in 7.0 core: a provider-agnostic PHP SDK for talking to AI models (`wp_ai_client_prompt()`)
-   **Abilities API** — server-side PHP shipped in 6.9; the JavaScript client API (`@wordpress/abilities`) is new in 7.0
-   **MCP support** — provided by the [`mcp-adapter`](https://github.com/WordPress/mcp-adapter) package (bundled with the AI plugin), which exposes abilities opted in via `meta.mcp.public` to MCP-compatible agents like Claude Desktop and Cursor

We're going to build a simplified **Content Summarization** plugin from scratch. It's the same feature that ships in the official [WordPress/ai](https://github.com/WordPress/ai) reference plugin — stripped down so every line is understandable.

By the end of the guided section you will have:

-   A WordPress plugin that connects to an AI provider
-   A registered Ability callable via the REST API
-   A block editor button that generates a summary and inserts it as a quote block at the top of the post

Then you'll have **2 hours** to build whatever you want using the same patterns.

## Structure

There are 4 required guided sections (1 tour + 3 coding) plus 3 optional sections — a follow-on coding section that rebuilds the editor integration with `@wordpress/abilities`, an MCP demo, and a 2-hour hackathon. Each section builds on the previous one.

The plugin in the repo root is intentionally a scaffold — empty `includes/` and `src/` directories with just a plugin header. You'll fill it in as the workshop progresses. If you fall behind or want a clean start, the `code-reference/` folders contain the complete state of the code at the end of each coding section.

> **Note on `code-reference/section-2/`:** this snapshot contains a temporary smoke-test function that Section 3 begins by deleting. If you copy section-2 forward as a starting point, remove `wp_ai_workshop_test_ai_connection` (and its `add_action` call) before continuing.

**Section ↔ code-reference mapping:**

| Section                                       | Code reference              | State                                           |
| --------------------------------------------- | --------------------------- | ----------------------------------------------- |
| 1 — Tour AI Experiments Plugin                | _no code_                   | Exploration only                                |
| 2 — Scaffold + AI API                         | `code-reference/section-2/` | Plugin stub + smoke-test AI request             |
| 3 — Register the Ability                      | `code-reference/section-3/` | Ability registered, callable via REST           |
| 4 — Block Editor Integration (`apiFetch`)     | `code-reference/section-4/` | Complete plugin via the auto-generated endpoint |
| 5 — _Optional_ `@wordpress/abilities` rebuild | `code-reference/section-5/` | Same feature, script-module + abilities client  |
| 6 — _Optional_ MCP Demo                       | _no code_                   | Presenter-led demo                              |
| 7 — _Optional_ Hackathon                      | _self-directed_             | Build your own ability                          |

## Adapting This Workshop for Your Event

The repository ships generic — no event-specific branding, no calendar dates, no city references — so it can be delivered as-is. If you want to brand a delivery for a specific WordCamp / meetup / company event, here is the short list of places to tweak. Everything below is optional.

1. **Site banner (`blueprint.json`)** — set the `blogname` option (currently `WordPress AI Workshop`) to whatever you want attendees to see at the top of their Studio site. This is the most visible piece of per-event branding.

2. **Demo post content (`blueprint.json`)** — the pre-seeded `Hello, WordPress!` post is a generic essay about WordPress. Swap it for something local — a few paragraphs about your city, your meetup, or a topic your audience cares about — if you want the summarization output to feel grounded in the moment. Keep the title as-is or rename it; just remember to update the post-title references in `workshop-outline/section-1.md` and `workshop-outline/section-4.md` if you rename.

3. **Category label (`includes/summarizer.php` in Section 3)** — `WordPress AI Workshop` is the label that shows up in **Settings → AI → Abilities Explorer**. Renaming it to your event name is a nice touch but purely cosmetic.

4. **Headings in this README, `facilitator-notes.md`, and `slides-outline.md`** — the title line at the top of each file is intentionally generic. If you want event branding on the title slide / facilitator handout / participant README, add a subheading (e.g. `### Delivered at WordCamp Toronto 2027`) under the existing top-level heading.

5. **Per-event git workflow (suggested)** — keep `trunk` as the canonical generic version. For each delivery, create a tag (e.g. `event/wcyyz-2027`) or branch off `trunk` and apply the four tweaks above. That keeps the canonical repo unbranded and gives you a frozen snapshot of exactly what you delivered.

Nothing in the workshop logic depends on any of this — function names, ability namespaces, REST paths, and JS handles all use the neutral `wp-ai-workshop` / `wp_ai_workshop_` prefix.

---

Let's go! → [Section 1: Tour the AI Experiments Plugin](./workshop-outline/section-1.md)
