# Section 4 — Block Editor Integration

The REST endpoint works. Now let's wire up a button in the block editor that calls our ability and inserts the summary as a paragraph block. We already used `wp.apiFetch` from the console in Section 3 to hit the auto-generated REST endpoint — we'll use the same `@wordpress/api-fetch` package from our plugin's JavaScript here. Same endpoint, same `input` wrapper on the request, same plain-string response.

> **Stuck? Completed code for this section lives at `code-reference/section-4/`** — open it to compare against your own work, not to copy from.

Reference: [@wordpress/api-fetch](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/)

## Start the Build

1. In your terminal, start the build process in watch mode:

```bash
npm start
```

Keep this running throughout the section. It will recompile whenever you save a file.

2. Before WordPress can run our JavaScript, we need to register and enqueue the built file in the block editor. Add this to the bottom of `includes/summarizer.php`:

```php
/**
 * Register and enqueue the editor script.
 *
 * `@wordpress/scripts` builds `src/index.js` into `build/index.js` as a
 * classic script and writes a sibling `build/index.asset.php` containing
 * the WordPress package dependencies webpack detected (e.g. `wp-plugins`,
 * `wp-editor`, `wp-api-fetch`) plus a content hash for cache-busting.
 *
 * We pass that dependency array straight to `wp_enqueue_script()` so any
 * new `@wordpress/...` import you add to `index.js` gets enqueued
 * automatically next time the build runs.
 */
function wp_ai_workshop_enqueue_editor_assets() {
	$asset_file = plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$assets = require $asset_file;

	wp_enqueue_script(
		'wp-ai-workshop-summarization',
		plugins_url( 'build/index.js', __DIR__ ),
		$assets['dependencies'],
		$assets['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'wp_ai_workshop_enqueue_editor_assets' );
```

A regular `wp_enqueue_script()` — that's it. No script-module loader, no shim enqueues for `@wordpress/abilities`. We're calling the REST endpoint directly with `@wordpress/api-fetch`, which is a plain classic script and gets picked up by webpack like every other `@wordpress/*` import.

3. Open `src/index.js`. This is our JavaScript entry point.

## Register a Plugin

We'll use the [`registerPlugin`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-plugins/#registerplugin) API and the `PluginPostStatusInfo` SlotFill to add our button to the post sidebar.

4. Add the imports and register the plugin. We're front-loading every import we'll need across the rest of the section — `SelectControl` in particular won't get used until the length-picker step near the end, so don't worry if your linter complains about an unused import for a few minutes.

```javascript
import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize, createBlock } from '@wordpress/blocks';
import { Button, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const SummarizationPlugin = () => {
	return (
		<PluginPostStatusInfo>
			<Button variant="primary">Generate AI Summary</Button>
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'wp-ai-workshop-summarization', { render: SummarizationPlugin } );
```

5. Open **Posts → Hello, WordPress!** (pre-seeded by the blueprint). You should see a **Generate AI Summary** button in the right sidebar. It doesn't do anything yet — let's fix that.

## Get the Post Content

6. Use `useSelect` to get the current blocks from the editor store, then serialize them to a string the AI can read:

```javascript
const SummarizationPlugin = () => {
	// Read the post's current blocks reactively from the editor data store.
	// `useSelect` re-runs whenever the underlying state changes, so `blocks`
	// always reflects what's in the editor right now.
	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	return (
		<PluginPostStatusInfo>
			<Button variant="primary">Generate AI Summary</Button>
		</PluginPostStatusInfo>
	);
};
```

## Call the Ability

7. Add loading state and call `apiFetch` when the button is clicked. Same endpoint we hit from the console in Section 3, same `input` wrapper, same plain-string response:

```javascript
const SummarizationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );

	// Read the post's current blocks reactively from the editor data store.
	// `useSelect` re-runs whenever the underlying state changes, so `blocks`
	// always reflects what's in the editor right now.
	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	const handleClick = async () => {
		setIsLoading( true );

		// `serialize()` turns the array of block objects into a single
		// post_content-style string — the same format WordPress stores in
		// the database, and exactly what our ability's `content` field
		// expects.
		const content = serialize( blocks );

		// `apiFetch` hits the REST endpoint WordPress created from our
		// ability's schema. The `input` wrapper is the REST contract the
		// Abilities API generates; the ability's return value (a string,
		// per our `output_schema`) comes back as the response body — so
		// `apiFetch` resolves directly to the summary string.
		const summary = await apiFetch( {
			path: '/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run',
			method: 'POST',
			data: {
				input: {
					content,
					length: 'medium',
				},
			},
		} );

		console.log( 'Summary:', summary );
		setIsLoading( false );
	};

	return (
		<PluginPostStatusInfo>
			<Button
				variant="primary"
				onClick={ handleClick }
				isBusy={ isLoading }
			>
				{ isLoading ? 'Generating…' : 'Generate AI Summary' }
			</Button>
		</PluginPostStatusInfo>
	);
};
```

8. Back in the **Hello, WordPress!** post, click the button and check the browser console. You should see your summary logged. 🎉

> **Watch the REST call.** Open the browser DevTools **Network** tab, filter on `abilities`, and click the button again. You'll see a `POST` to `/wp-json/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run` — the exact endpoint WordPress generated from the schema you registered in Section 3, carrying the `{ input: { content, length } }` payload. No bespoke REST route written; the Abilities API generated it from your `input_schema`.

## Insert the Summary Block

9. Use `useDispatch` to get the `insertBlock` action, then create a paragraph block with the summary and insert it at position 0 (the top of the content):

```javascript
const SummarizationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );

	// Read the post's current blocks reactively from the editor data store.
	// `useSelect` re-runs whenever the underlying state changes, so `blocks`
	// always reflects what's in the editor right now.
	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	// `useDispatch` gives us the action creators for a store. We only need
	// `insertBlock` here — the write counterpart to the read above.
	const { insertBlock } = useDispatch( 'core/block-editor' );

	const handleClick = async () => {
		setIsLoading( true );

		// `serialize()` turns the array of block objects into a single
		// post_content-style string — the same format WordPress stores in
		// the database, and exactly what our ability's `content` field
		// expects.
		const content = serialize( blocks );

		const summary = await apiFetch( {
			path: '/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run',
			method: 'POST',
			data: {
				input: {
					content,
					length: 'medium',
				},
			},
		} );

		// Build the inner paragraph first, then wrap it in a quote so the
		// summary is visually distinct from the post's regular content.
		const paragraphBlock = createBlock( 'core/paragraph', {
			content: summary,
		} );

		const quoteBlock = createBlock(
			'core/quote',
			{ citation: 'WordPress AI Summarizer' },
			[ paragraphBlock ]
		);

		// Insert at index 0 — the very top of the post content.
		insertBlock( quoteBlock, 0 );
		setIsLoading( false );
	};

	return (
		<PluginPostStatusInfo>
			<Button
				variant="primary"
				onClick={ handleClick }
				isBusy={ isLoading }
			>
				{ isLoading ? 'Generating…' : 'Generate AI Summary' }
			</Button>
		</PluginPostStatusInfo>
	);
};
```

10. Click the button. A quote block containing the AI-generated summary (with the citation "WordPress AI Summarizer") should appear at the top of your post content. 🔥🔥🔥

## Let the User Pick the Summary Length

Our ability already accepts a `length` input — short, medium, or long — but we've been hardcoding `'medium'` from JavaScript. Let's expose that choice in the UI so the post author can pick.

We'll do this in two passes: render the control first, then wire it up. Splitting it makes it easy to tell the difference between "did my UI render" and "did my data flow work" if something goes sideways.

### Render the Control

11. Add a `length` state and render the dropdown above the button. We already imported `SelectControl` at the top of the file:

```javascript
const SummarizationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );
	// Holds the user's choice from the SelectControl. Defaults to medium —
	// the same default declared in our `input_schema` back in Section 3.
	const [ length, setLength ] = useState( 'medium' );

	// Read the post's current blocks reactively from the editor data store.
	// `useSelect` re-runs whenever the underlying state changes, so `blocks`
	// always reflects what's in the editor right now.
	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	// `useDispatch` gives us the action creators for a store. We only need
	// `insertBlock` here — the write counterpart to the read above.
	const { insertBlock } = useDispatch( 'core/block-editor' );

	const handleClick = async () => {
		setIsLoading( true );

		// `serialize()` turns the array of block objects into a single
		// post_content-style string — the same format WordPress stores in
		// the database, and exactly what our ability's `content` field
		// expects.
		const content = serialize( blocks );

		const summary = await apiFetch( {
			path: '/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run',
			method: 'POST',
			data: {
				input: {
					content,
					length: 'medium',
				},
			},
		} );

		// Build the inner paragraph first, then wrap it in a quote so the
		// summary is visually distinct from the post's regular content.
		const paragraphBlock = createBlock( 'core/paragraph', {
			content: summary,
		} );

		const quoteBlock = createBlock(
			'core/quote',
			{ citation: 'WordPress AI Summarizer' },
			[ paragraphBlock ]
		);

		// Insert at index 0 — the very top of the post content.
		insertBlock( quoteBlock, 0 );
		setIsLoading( false );
	};

	return (
		<PluginPostStatusInfo>
			<SelectControl
				label="Summary length"
				value={ length }
				// Disable the dropdown mid-request so the user can't change
				// length while a generation is already in flight.
				disabled={ isLoading }
				options={ [
					{ label: 'Short', value: 'short' },
					{ label: 'Medium', value: 'medium' },
					{ label: 'Long', value: 'long' },
				] }
				onChange={ setLength }
			/>
			<Button
				variant="primary"
				onClick={ handleClick }
				isBusy={ isLoading }
			>
				{ isLoading ? 'Generating…' : 'Generate AI Summary' }
			</Button>
		</PluginPostStatusInfo>
	);
};
```

12. Reload the editor. The dropdown appears above the button, and changing it updates the displayed value. Click **Generate AI Summary** — you'll still get a medium-length summary, because the call hasn't been wired to the new state yet. That's the next step.

### Wire It Through

13. Replace the hardcoded `'medium'` in the `apiFetch` call with the `length` state:

```javascript
const summary = await apiFetch( {
	path: '/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run',
	method: 'POST',
	data: {
		input: {
			content,
			length,
		},
	},
} );
```

14. Pick **Short** and click Generate — you should get a one-sentence summary. Pick **Long** and try again — noticeably longer. Same ability, same content, different input field.

> Notice we never had to validate `length` on the JavaScript side. Because we registered an `enum` in the ability's input schema back in Section 3, WordPress validates every request before our PHP callback runs. If we passed `'gigantic'`, the REST layer would reject it with `ability_invalid_input` and we'd never burn an AI call. Schema-first design pays off.

---

# Ready to move on?

[Section 5: *Optional* — Same Feature with `@wordpress/abilities`](./section-5.md)

Or skip ahead:

[Section 6: MCP — Exposing the Ability to AI Agents](./section-6.md)

# Missing something from the last section?

[Section 3: Registering the Summarization Ability](./section-3.md)
