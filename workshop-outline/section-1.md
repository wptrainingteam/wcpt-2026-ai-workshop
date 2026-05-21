# Section 1 — Tour the AI Experiments Plugin

Before we write a single line of code, let's understand what we're working with. The [WordPress AI plugin](https://github.com/WordPress/ai) is pre-installed on your site. It's the official reference implementation of the WordPress AI Building Blocks — the same APIs we'll be using today.

We're going to explore the Content Summarization experiment it ships with, see it running, read the source, and then understand why we're rebuilding a simpler version from scratch.

## See It Running

1. In your WordPress admin, go to **Settings → Connectors** and confirm your API key is configured. Add one now if not.

2. Go to **Settings → AI → Experiments**
  - find **Enable AI**, and toggle it on.
  - find **Content Summarization**, and toggle it on.

3. Go to **Posts → Hello, WordPress!**

> If you're not using the blueprint, this post won't be pre-seeded — create a new post titled **Hello, WordPress!** with a few paragraphs of placeholder content before continuing. Any text will do; we just need something for the AI to summarize.

4. In the right sidebar, find the **Summary** panel and click **Generate AI Summary**.

A paragraph block containing a plain-text AI summary should appear at the top of your content. That's the shape of feature we're building today — our version will wrap the summary in a quote block instead, but the flow is the same.

## Read the Source

Open the AI plugin directory (`wp-content/plugins/ai/`). The Summarization feature lives in two files:

- **[`includes/Experiments/Summarization/Summarization.php`](https://github.com/WordPress/ai/blob/develop/includes/Experiments/Summarization/Summarization.php)** — handles registration, hooks into the editor, enqueues assets, registers post meta
- **[`includes/Abilities/Summarization/Summarization.php`](https://github.com/WordPress/ai/blob/develop/includes/Abilities/Summarization/Summarization.php)** — defines input/output schema, checks permissions, calls the AI client, returns the result

Open both. Notice the separation:

- The **Experiment** class wires things up
- The **Ability** class does the actual work

Also take a look at:

- **[`includes/Abilities/Summarization/system-instruction.php`](https://github.com/WordPress/ai/blob/develop/includes/Abilities/Summarization/system-instruction.php)** — the prompt sent to the AI
- **[`src/experiments/summarization/index.tsx`](https://github.com/WordPress/ai/blob/develop/src/experiments/summarization/index.tsx)** — the React entry point

## The PHP AI Client API Surface

`wp_ai_client_prompt()` is the entry point you'll use everywhere in this workshop. It returns a fluent builder object — `generate_text()` is the method you'll see most, but it's one of many. Quick orientation before we write any code.

> **No extra plugin to install.** The PHP AI Client and `wp_ai_client_prompt()` ship in **WordPress 7.0 core**, and the Abilities API is in core too (PHP since 6.9, JS in 7.0). The only plugins on your blueprint are the provider plugins (Anthropic / OpenAI / Google) — everything else below is core API.

**Three layers, one builder:**

- The **PHP AI Client SDK** ([`WordPress/php-ai-client`](https://github.com/WordPress/php-ai-client)) defines the builder and every `generate_*` method.
- The **provider plugins** (`ai-provider-for-anthropic`, `ai-provider-for-openai`, `ai-provider-for-google`) implement a `ProviderInterface` plus per-capability model contracts and register themselves with the SDK. They don't add or override builder methods — the SDK routes to them.
- **WordPress 7.0 core** ships a thin snake_case wrapper, `WP_AI_Client_Prompt_Builder`, that converts SDK exceptions into `WP_Error` and uses the WP HTTP API for transport. That wrapper is what `wp_ai_client_prompt()` hands you.

Because the SDK owns the public surface, the same builder call works regardless of which provider you have configured — the SDK routes the request to whichever registered provider declares support for that capability. Use `is_supported_for_*()` first if you're not sure your configured provider can fulfill the request.

**What the builder offers:**

- **Generate** — `generate_text()`, `generate_image()`, `generate_speech()`, `convert_text_to_speech()`, `generate_video()`. Each has a `_result()` variant that returns rich metadata (token counts, finish reason, etc.) and a plural form (`generate_texts( $n )`, …) for multiple candidates.
- **Configure the request** — `using_model()`, `using_provider()`, `using_system_instruction()`, `using_temperature()`, `using_max_tokens()`, `using_top_p()`, `using_function_declarations()`, `using_web_search()`, …
- **Shape the output** — `as_json_response( $schema )`, `as_output_modalities()`, `as_output_media_aspect_ratio()`, …
- **Build conversational input** — `with_text()`, `with_file()`, `with_history()`, `with_message_parts()`.
- **Check capabilities before calling** — `is_supported_for_text_generation()`, `is_supported_for_image_generation()`, `is_supported_for_speech_generation()`, `is_supported_for_video_generation()`, `is_supported_for_embedding_generation()`. Useful when the configured provider may not support what you're about to ask for.

A typical chain:

```php
$summary = wp_ai_client_prompt( 'List three things WordPress is famous for.' )
    ->using_system_instruction( 'Respond in a single short sentence per item, no preamble.' )
    ->using_temperature( 0.4 )
    ->using_max_tokens( 200 )
    ->generate_text();
```

We'll stick with `generate_text()` for the rest of the workshop, but keep the wider surface in the back of your mind for the hackathon.

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
