# Hackathon Challenge — Build Your Own Ability

You have 2 hours. Use the same three-layer pattern from the workshop to build something new.

**The pattern:**
1. Register an ability in PHP (`wp_register_ability`)
2. Call it from the block editor in JS (`executeAbility`)
3. MCP exposure is free once the ability is registered

---

## Success Criteria

- [ ] Ability is registered and visible in the Abilities Explorer (Settings → AI → Abilities Explorer)
- [ ] Ability is callable via the REST API
- [ ] A block editor button, panel, or control triggers the ability
- [ ] The result is surfaced usefully in the editor

---

## Choose Your Build

### Excerpt Generator
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

### Tag Suggester
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

### Meta Description Generator
Generate an SEO-friendly meta description from title and content.

- **Ability:** `ai/meta-description`
- **Input:** `title` (string), `content` (string)
- **Output:** string (target: under 160 characters)

<details>
<summary>Hints</summary>

- Tell the AI to stay under 160 characters and write in active voice
- In JS: `select( 'core/editor' ).getEditedPostAttribute( 'title' )` gets the current title
- If Yoast or RankMath is installed, check if they expose post meta you can write to

</details>

---

### Title Variations
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

### Tone Analyzer
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

### Content Translator
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

### Comment Moderator
Classify a comment as spam, appropriate, or needs review.

- **Ability:** `ai/moderate-comment`
- **Input:** `comment` (string), `post_context` (string)
- **Output:** object with `decision` (enum: spam/appropriate/review) and `reason` (string)

<details>
<summary>Hints</summary>

- This works great as an MCP tool for bulk moderation — try calling it from Claude Desktop
- In PHP, get post content with `get_the_content( null, false, $post_id )`
- The `decision` enum gives you a machine-readable result you can act on programmatically

</details>

---

## Stretch Goals

- Register your ability as an MCP tool and call it from Claude Desktop or Cursor
- Add configurable options (length, language, tone) via `<SelectControl>` or `<ToggleGroupControl>`
- Support custom post types (make sure they have `show_in_rest: true`)
- Store results to post meta for programmatic access
- Handle errors gracefully using `createNotice` from `@wordpress/notices`

---

## Reference

| Resource | URL |
|----------|-----|
| Abilities API (PHP) | https://developer.wordpress.org/apis/abilities-api/ |
| @wordpress/abilities (JS) | https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/ |
| Block Editor data stores | https://developer.wordpress.org/block-editor/reference-guides/data/ |
| WordPress/ai reference plugin | https://github.com/WordPress/ai |
| Complete workshop plugin | `code-reference/step-4-final/` |
