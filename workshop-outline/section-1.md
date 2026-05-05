# Section 1 — Tour the AI Experiments Plugin

Before we write a single line of code, let's understand what we're working with. The [WordPress AI plugin](https://github.com/WordPress/ai) is pre-installed on your site. It's the official reference implementation of the WordPress AI Building Blocks — the same APIs we'll be using today.

We're going to explore the Content Summarization experiment it ships with, see it running, read the source, and then understand why we're rebuilding a simpler version from scratch.

## See It Running

1. In your WordPress admin, go to **Settings → AI**.

2. Select the **Providers** tab and confirm your API key is configured. Add one now if not.

3. Go back to **Settings → AI → Experiments**, find **Content Summarization**, and toggle it on.

4. Go to **Posts → Add New**. Write at least 3–4 paragraphs of content.

5. In the right sidebar, find the **Summary** panel and click **Generate AI Summary**.

A paragraph block containing a plain-text AI summary should appear at the top of your content. That is what we are building today.

## Read the Source

Open the AI plugin directory (`wp-content/plugins/ai/`). The Summarization feature lives in two files:

- **`includes/Experiments/Summarization/Summarization.php`** — handles registration, hooks into the editor, enqueues assets, registers post meta
- **`includes/Abilities/Summarization/Summarization.php`** — defines input/output schema, checks permissions, calls the AI client, returns the result

Open both. Notice the separation:
- The **Experiment** class wires things up
- The **Ability** class does the actual work

Also take a look at:
- **`includes/Abilities/Summarization/system-instruction.php`** — the prompt sent to the AI
- **`src/experiments/summarization/index.tsx`** — the React entry point

## Why Are We Rebuilding It?

The real plugin is correct, extensible, and production-safe. That's intentional — but it means there's a lot of code defending against edge cases, filtering at every layer, and TypeScript abstractions layered on top of each other.

Our version will be:
- A single PHP file for all plugin logic
- A single JS file for the block editor integration
- No abstractions beyond what the APIs require

Same patterns. Much clearer signal. You can always go back to the reference plugin to see how to productionize what we build today.

---

# Ready to move on?
[Section 2: Scaffold the Plugin & Connect to the AI API](./section-2.md)
