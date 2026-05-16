# Section 6 — MCP: Exposing the Ability to AI Agents

**This section is presenter-led. Sit back and watch — no coding required.**

> **Presenter setup note.** The workshop blueprint does **not** bundle the [`mcp-adapter`](https://github.com/WordPress/mcp-adapter) package — it's only needed for this demo, not for attendees to follow along. Before running this section, install and activate `mcp-adapter` on your demo site (it's the package that bridges the Abilities API to the `/wp-json/wp-abilities/v1/mcp` MCP endpoint). Attendee sites do not need it.

We've built a working ability callable from the REST API and from the block editor. Now let's see the payoff of one line you wrote back in Section 3 — `'mcp' => array( 'public' => true )` — which makes our ability available to AI agents via the **Model Context Protocol (MCP)**.

## What is MCP?

The [Model Context Protocol](https://modelcontextprotocol.io/) is an open standard that lets AI agents (like Claude Desktop, Cursor, or any MCP-compatible client) discover and call tools on external systems.

The WordPress Abilities API is MCP-compatible by design, but exposure is opt-in. When `meta.mcp.public` is `true` on an ability and the MCP Adapter package is active on the site, the adapter exposes a default MCP server at:

```
/wp-json/wp-abilities/v1/mcp
```

A small but important detail: on the default server, your ability isn't surfaced as its own top-level MCP tool. Instead, the adapter publishes three generic meta-tools — `mcp-adapter/discover-abilities`, `mcp-adapter/get-ability-info`, and `mcp-adapter/execute-ability` — and agents use those to find and run `wcpt/summarization`. The agent's tool list will show the adapter's tools, not yours directly; that's expected.

## Live Demo

Watch as Claude Desktop:

1. Connects to the WordPress site via MCP
2. Discovers the available tools (including our `wcpt/summarization` ability)
3. Calls the ability on a real post — no UI interaction required
4. Returns the summary, which the agent can use however it needs to

This is why the three-layer architecture matters:

- **PHP** defines what the ability does and enforces permissions
- **JS** gives humans a UI to trigger it
- **MCP** makes it available to automated agents via a one-line opt-in (`meta.mcp.public`)

The same Ability. Three different callers.

## What This Unlocks

Once your abilities are registered, they can be used in:

- Editorial workflows automated by an AI assistant
- Custom agents that process content in bulk
- Third-party tools that can discover and compose WordPress capabilities

---

# Ready for the hackathon?
[Section 7: *Optional* Hackathon — Build Your Own Ability](./section-7.md)

# Missing something from the last section?
[Section 5: *Optional* — Same Feature with `@wordpress/abilities`](./section-5.md)

Or, if you skipped the optional section:

[Section 4: Block Editor Integration](./section-4.md)
