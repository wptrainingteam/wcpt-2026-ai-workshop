# Stop Doing It Yourself: Building AI-Powered Admin Tools with the WordPress AI API

### WordCamp Portugal 2026

**Presented by Ryan Welcher & JuanMa Garrido — Developer Advocates, Automattic**

## Pre-Workshop Setup Checklist

To avoid wifi bottlenecks on the day, please complete this **before you arrive**:

1. **WordPress Studio** — Download and install [WordPress Studio](https://developer.wordpress.com/studio/).

2. **Create a site from the blueprint** — Download the [`blueprint.json`](https://raw.githubusercontent.com/wptrainingteam/wcpt-2026-ai-workshop/trunk/blueprint.json) file from this repository. In Studio, click **Add site → Start from a blueprint → Choose blueprint file** and select the downloaded file.

   This will automatically install WordPress 7.0 RC2, the AI plugin, and the workshop plugin with everything activated and ready to go.

3. **Node.js v20+** — Recommended via [NVM](https://github.com/nvm-sh/nvm):

   ```bash
   nvm install 20 && nvm use 20
   ```

4. **Install dependencies** in the workshop plugin directory:

   ```bash
   cd /path/to/your/studio/site/wp-content/plugins/wcpt-2026-ai-workshop
   npm install
   composer install
   ```

5. **Configure an API key** under **Settings → Connectors**. Any of the following will work:
   - [Anthropic](https://console.anthropic.com/) (Text generation with Claude)
   - [OpenAI](https://platform.openai.com/) (Text and image generation with GPT and DALL·E)
   - [Google](https://aistudio.google.com/api-keys) (Text and image generation with Gemini and Imagen)

**Not using Studio?** For any other local WordPress installation you will need:

- **[WordPress Beta Tester](https://wordpress.org/plugins/wordpress-beta-tester/)** — install and activate it, then go to **Tools → Beta Testing** and switch to the WordPress 7.0 RC channel to update your site to 7.0 RC2.
- **[AI plugin](https://wordpress.org/plugins/ai/)** — install and activate from the WordPress plugin directory.
- **This workshop plugin** — clone the repo into your `wp-content/plugins/` directory and activate it:

  ```bash
  cd /path/to/your/site/wp-content/plugins/
  git clone https://github.com/wptrainingteam/wcpt-2026-ai-workshop
  wp plugin activate wcpt-2026-ai-workshop
  ```

---

## Welcome!

Thanks for joining us today! We have 4 hours together, and we'll move at a steady pace — but there's plenty of room for questions along the way.

## What Are We Building?

WordPress 7.0 RC2 ships three new AI building blocks:

- **PHP AI Client** — a provider-agnostic PHP SDK for talking to AI models
- **Abilities API** — a standard registry for discoverable, REST-accessible AI capabilities
- **MCP support** — abilities auto-exposed as tools for AI agents like Claude and Cursor

We're going to build a simplified **Content Summarization** plugin from scratch. It's the same feature that ships in the official [WordPress/ai](https://github.com/WordPress/ai) reference plugin — stripped down so every line is understandable.

By the end of the guided section you will have:

- A WordPress plugin that connects to an AI provider
- A registered Ability callable via the REST API
- A block editor button that generates a summary and inserts it as a paragraph block

Then you'll have **2 hours** to build whatever you want using the same patterns.

## Structure

There are 5 guided sections followed by a 2-hour hackathon. Each section builds on the previous one.

For each section, `code-reference/step-N/` contains the complete state of the code at the end of that step — use it freely if you fall behind or want a clean start.

You can jump to any step using git:

```bash
git checkout step-2-abilities
```

| Tag                | State                                  |
| ------------------ | -------------------------------------- |
| `step-1-scaffold`  | Plugin stub + first AI request working |
| `step-2-abilities` | Ability registered, callable via REST  |
| `step-3-js`        | Full Block Editor integration          |
| `step-4-final`     | Complete plugin                        |

Let's go! → [Section 1: Tour the AI Experiments Plugin](./workshop-outline/section-1.md)
