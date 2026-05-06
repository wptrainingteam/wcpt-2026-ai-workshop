# Section 6 — Hackathon: Build Your Own Ability

You've got **2 hours**. Pick something from the suggestions below (or bring your own idea) and build it using the same three-layer pattern:

1. **PHP** — register an ability that calls the AI client
2. **JS** — add a block editor trigger using `executeAbility()`
3. **MCP** — it's free once the ability is registered

Your goal is a working REST endpoint and at least one JS trigger. Everything beyond that is a stretch goal.

---

## Suggested Builds

### Excerpt Generator
Generate a post excerpt from the full content.

- **Ability name:** `ai/excerpt-generation`
- **Input:** `content` (string), `max_words` (integer, default: 55)
- **Output:** string
- **Hint:** The WordPress excerpt field is `post_excerpt`. You can write to it using `dispatch( 'core/editor' ).editPost( { excerpt: result } )` in JS.

---

### Tag Suggester
Suggest relevant tags based on post content.

- **Ability name:** `ai/tag-suggestions`
- **Input:** `content` (string), `count` (integer, default: 5)
- **Output:** array of strings
- **Hint:** Return JSON from the AI and `json_decode()` in PHP. In JS, use the `core` store to get existing tags and compare.

---

### Meta Description Generator
Generate an SEO-friendly meta description from the post title and content.

- **Ability name:** `ai/meta-description`
- **Input:** `title` (string), `content` (string)
- **Output:** string (max ~160 characters)
- **Hint:** Instruct the AI to stay under 160 characters. In JS, get the title with `select( 'core/editor' ).getEditedPostAttribute( 'title' )`.

---

### Title Variations
Generate 3 alternative titles for a post.

- **Ability name:** `ai/title-variations`
- **Input:** `content` (string), `current_title` (string)
- **Output:** array of strings
- **Hint:** Have the AI return a numbered list, then split on newlines in PHP. Display the options in a panel so the user can click to apply one.

---

### Tone Analyzer
Classify the tone and reading level of the content.

- **Ability name:** `ai/tone-analysis`
- **Input:** `content` (string)
- **Output:** object with `tone` (string) and `reading_level` (string)
- **Hint:** Return structured JSON from the AI. Use `output_schema` with `type: 'object'` and defined properties.

---

### Content Translator
Translate post content to another language.

- **Ability name:** `ai/translate`
- **Input:** `content` (string), `target_language` (string)
- **Output:** string
- **Hint:** Add a language selector dropdown to the sidebar using `<SelectControl>` from `@wordpress/components`.

---

### Comment Moderator
Classify a comment as spam, appropriate, or needs review.

- **Ability name:** `ai/moderate-comment`
- **Input:** `comment` (string), `post_context` (string)
- **Output:** object with `decision` (enum: spam/appropriate/review) and `reason` (string)
- **Hint:** This one works great as an MCP tool for bulk moderation. Try calling it from Claude Desktop after building the ability.

---

## Success Criteria

- [ ] Ability is registered and appears in the Abilities Explorer (Settings → AI → Abilities Explorer)
- [ ] Ability is callable via the REST API (`curl` or Postman)
- [ ] A block editor button or panel triggers the ability
- [ ] The result is surfaced in the editor in a useful way

## Stretch Goals

- Register the ability's output as an MCP tool and call it from Claude Desktop or Cursor
- Add configurable options (length, language, tone) via UI controls
- Support custom post types beyond `post`
- Store the result to post meta for programmatic access
- Handle errors gracefully with editor notices

## Resources

- [Abilities API docs](https://developer.wordpress.org/apis/abilities-api/)
- [@wordpress/abilities JS client](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)
- [Block Editor Data stores](https://developer.wordpress.org/block-editor/reference-guides/data/)
- [WordPress/ai reference plugin](https://github.com/WordPress/ai) — browse other experiments for inspiration
- `code-reference/step-4-final/` — the complete summarization plugin to reference

---

# That's a wrap!

Share what you built. Find us on [WordPress Slack](https://make.wordpress.org/chat/) — Ryan: `@ryanwelcher` · JuanMa: `@juanma`.
