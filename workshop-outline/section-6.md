# Hackathon Challenge — Build Your Own Ability

You have 2 hours. Use the same three-layer pattern from the workshop to build something new.

**The pattern:**
1. Build the prompt with the PHP AI Client (`wp_ai_client_prompt( $prompt )->generate_text()`) inside an execute callback
2. Register that callback as an ability (`wp_register_ability`) with input/output schemas
3. Call the ability from the block editor in JS (`executeAbility` from `@wordpress/abilities`)
4. **MCP comes free** — the moment your ability is registered, it's exposed as an MCP tool at `/wp-json/wp-abilities/v1/mcp` for Claude Desktop, Cursor, and any other MCP client

## Where to Start

Keep building inside the same workshop plugin you've been working in.

- Create a **new PHP file** in `includes/` for your ability (e.g. `includes/excerpt.php`) and `require_once` it from `plugin.php` alongside `summarizer.php`. Keeps each ability self-contained.
- Add the editor UI in `src/index.js`. You can reuse the existing `PluginPostStatusInfo` slot, or pick a different SlotFill that fits your feature better — see the [Block Editor SlotFills reference](https://developer.wordpress.org/block-editor/reference-guides/slotfills/) (e.g. `PluginDocumentSettingPanel` for a sidebar panel, `PluginSidebar` for a full sidebar).

If your ability returns structured data (an array or object), define the shape in `output_schema` and `json_decode()` the AI response in PHP before returning it. References:

- [Abilities API — registering abilities & schemas](https://developer.wordpress.org/apis/abilities-api/)
- [JSON Schema — type, properties, items, enum](https://json-schema.org/understanding-json-schema/reference/type)

## Debugging

- Check `wp-content/debug.log` for PHP errors and AI client failures
- Check the browser console for JS errors and `executeAbility` rejections
- If you're stuck, grab a facilitator — that's what we're here for

---

## Success Criteria

- [ ] Ability is registered and visible in the Abilities Explorer (Settings → AI → Abilities Explorer)
- [ ] Ability is callable via the REST API
- [ ] A block editor button, panel, or control triggers the ability
- [ ] The result is surfaced usefully in the editor

---

## Build Anything You Want

**Build whatever you want** — the only requirement is that it follows the three-layer pattern above. The ideas below are starting points if you'd like one, not a menu you have to pick from. If you've got your own idea, run with it.

### Ideas to Get You Started

#### Excerpt Generator
Generate a post excerpt from the full content.

- **Ability:** `ai/excerpt-generation`
- **Input:** `content` (string), `max_words` (integer, default: 55)
- **Output:** string

<details>
<summary>Hints</summary>

- Instruct the AI to write in third person, present tense, under the word limit
- In JS, write the result to the excerpt field: `dispatch( 'core/editor' ).editPost( { excerpt: result } )`
- Get the current title for context: `select( 'core/editor' ).getEditedPostAttribute( 'title' )`

</details>

---

#### Tag Suggester
Suggest relevant tags based on post content.

- **Ability:** `ai/tag-suggestions`
- **Input:** `content` (string), `count` (integer, default: 5)
- **Output:** array of strings

<details>
<summary>Hints</summary>

- Have the AI return a JSON array of tag strings
- In PHP, `json_decode( $response, true )` to get the array
- Set `output_schema` to `'type' => 'array'` with `'items' => [ 'type' => 'string' ]`
- In JS, display the suggestions and let the user click to apply each one

</details>

---

#### Meta Description Generator
Generate an SEO-friendly meta description from title and content.

- **Ability:** `ai/meta-description`
- **Input:** `title` (string), `content` (string)
- **Output:** string (target: under 160 characters)

<details>
<summary>Hints</summary>

- Tell the AI to stay under 160 characters and write in active voice
- In JS: `select( 'core/editor' ).getEditedPostAttribute( 'title' )` gets the current title
- Store the result in post meta so it's available to themes and other plugins

</details>

---

#### Title Variations
Generate 3 alternative post titles.

- **Ability:** `ai/title-variations`
- **Input:** `content` (string), `current_title` (string)
- **Output:** array of strings

<details>
<summary>Hints</summary>

- Ask the AI to return exactly 3 titles as a JSON array
- Render the options as clickable items in a panel
- On click: `dispatch( 'core/editor' ).editPost( { title: selectedTitle } )`

</details>

---

#### Tone Analyzer
Classify the tone and reading level of the content.

- **Ability:** `ai/tone-analysis`
- **Input:** `content` (string)
- **Output:** object with `tone` and `reading_level`

<details>
<summary>Hints</summary>

- Return structured JSON: `{ "tone": "professional", "reading_level": "Grade 10" }`
- Set `output_schema` type to `object` with defined properties
- Display the results as a read-only info panel — no user action required

</details>

---

#### Content Translator
Translate post content to another language.

- **Ability:** `ai/translate`
- **Input:** `content` (string), `target_language` (string)
- **Output:** string

<details>
<summary>Hints</summary>

- Add a `<SelectControl>` dropdown for language selection in the sidebar
- The AI handles translation natively — just specify the target language in the prompt
- Consider inserting translated content as a new Group block below the original

</details>

---

#### Comment Moderator
Classify a comment as spam, appropriate, or needs review.

- **Ability:** `ai/moderate-comment`
- **Input:** `comment` (string), `post_context` (string)
- **Output:** object with `decision` (enum: spam/appropriate/review) and `reason` (string)

<details>
<summary>Hints</summary>

- In PHP, get post content with `get_the_content( null, false, $post_id )`
- The `decision` enum gives you a machine-readable result you can act on programmatically

</details>

---

## Stretch Goals

- Register the ability's output as an MCP tool and call it from Claude Desktop or Cursor
- Add configurable options (length, language, tone) via `<SelectControl>` or `<ToggleGroupControl>`
- Store results to post meta for programmatic access
- Support custom post types beyond `post`
- Handle errors gracefully using `createNotice` from `@wordpress/notices`

---

## Reference

**AI & Abilities**

| Resource | URL |
|----------|-----|
| PHP AI Client (SDK) | https://github.com/WordPress/php-ai-client |
| Abilities API (PHP) | https://developer.wordpress.org/apis/abilities-api/ |
| @wordpress/abilities (JS) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/ |
| WordPress/ai reference plugin | https://github.com/WordPress/ai |
| MCP Adapter | https://github.com/WordPress/mcp-adapter |
| JSON Schema reference | https://json-schema.org/understanding-json-schema/reference/type |

**Block Editor / JS**

| Resource | URL |
|----------|-----|
| SlotFills reference | https://developer.wordpress.org/block-editor/reference-guides/slotfills/ |
| Data stores (`core/editor`, `core/block-editor`) | https://developer.wordpress.org/block-editor/reference-guides/data/ |
| `@wordpress/plugins` (`registerPlugin`) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-plugins/ |
| `@wordpress/components` (`Button`, `SelectControl`, `ToggleGroupControl`, `Panel`) | https://developer.wordpress.org/block-editor/reference-guides/components/ |
| `@wordpress/blocks` (`createBlock`, `serialize`) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-blocks/ |
| `@wordpress/notices` (`createNotice`) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-notices/ |
| `@wordpress/data` (`useSelect`, `useDispatch`) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/ |
| `@wordpress/editor` (`PluginPostStatusInfo`, `PluginDocumentSettingPanel`, `PluginSidebar`) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-editor/ |

**WordPress core APIs**

| Resource | URL |
|----------|-----|
| `register_post_meta` (storing results) | https://developer.wordpress.org/reference/functions/register_post_meta/ |
| `register_post_type` (`show_in_rest`) | https://developer.wordpress.org/reference/functions/register_post_type/ |
| `get_the_content` | https://developer.wordpress.org/reference/functions/get_the_content/ |
| REST API handbook | https://developer.wordpress.org/rest-api/ |
| Application Passwords | https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#application-passwords |

**Background reading**

| Resource | URL |
|----------|-----|
| Introducing the AI Client in WordPress 7.0 (Make WP Core, Mar 2026) | https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/ |
| Client-Side Abilities API in WordPress 7.0 (Make WP Core, Mar 2026) | https://make.wordpress.org/core/2026/03/24/client-side-abilities-api-in-wordpress-7-0/ |
| Introducing the WordPress Abilities API (Nov 2025) | https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/ |
| From Abilities to AI Agents: the WordPress MCP Adapter (Feb 2026) | https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/ |
| AI Building Blocks for WordPress (Make WP AI, Jul 2025) | https://make.wordpress.org/ai/2025/07/17/ai-building-blocks |

---

# That's a wrap!

Share what you built. Find us on [WordPress Slack](https://make.wordpress.org/chat/) — Ryan: `@ryanwelcher` · JuanMa: `@juanma`.
