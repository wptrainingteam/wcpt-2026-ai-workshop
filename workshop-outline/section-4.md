# Section 4 — Block Editor Integration

The REST endpoint works. Now let's wire up a button in the block editor that calls our ability and inserts the summary as a paragraph block. We'll use `@wordpress/abilities` — the JavaScript client for the Abilities API — so we don't have to construct the REST request manually.

> **End-of-section reference:** `code-reference/section-4/`

Reference: [@wordpress/abilities](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)

## Start the Build

1. In your terminal, start the build process in watch mode:

```bash
npm start
```

Keep this running throughout the section. It will recompile whenever you save a file.

2. Before WordPress can run our JavaScript, we need to register and enqueue the built file in the block editor.

This enqueue looks a little weirder than usual — there are three things happening here that need explanation. Add this to the bottom of `includes/summarizer.php`:

```php
function wcpt_enqueue_script_modules() {
	$screen          = get_current_screen();
	$allowed_screens = array( 'post', 'page' );
	if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
		return;
	}

	$asset_file = plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$assets = require $asset_file;

	// Should be removed once 7.0 is released.
	wp_enqueue_script_module( '@wordpress/core-abilities' );
	wp_enqueue_script_module( '@wordpress/abilities' );

	wp_enqueue_script_module(
		'wcpt-summarization',
		plugins_url( 'build/index.js', __DIR__ ),
		array( '@wordpress/abilities' ),
		$assets['version']
	);
}
add_action( 'admin_enqueue_scripts', 'wcpt_enqueue_script_modules' );
```

**Why a script module enqueue for a non-module bundle?**

`@wordpress/scripts` builds our file as a *classic* JavaScript script — not an ES module. Normally that would mean a `wp_enqueue_script()` call. But `@wordpress/abilities` is published only through the WordPress script module loader: it isn't available as a classic script, and webpack can't resolve it at build time.

To declare `@wordpress/abilities` as a dependency we have to register our file with `wp_enqueue_script_module()` so WordPress's module loader can wire the dependency graph. So we end up with a classic-script *body* enqueued through the script-module *system*. The runtime `await import()` we'll add in `src/index.js` next is what actually pulls the abilities module in at runtime.

**Why `admin_enqueue_scripts` instead of `enqueue_block_editor_assets`?**

Script-module enqueueing for this scenario currently only registers correctly on the `admin_enqueue_scripts` hook. Using `enqueue_block_editor_assets` skips the script-module loader path we need. The `get_current_screen()` check narrows execution to post and page edit screens so we don't load on every admin page.

**Why the two extra enqueue calls?**

`wp_enqueue_script_module( '@wordpress/core-abilities' )` and `wp_enqueue_script_module( '@wordpress/abilities' )` are a temporary shim — once WordPress 7.0 ships these will be auto-registered with the script module loader and the two lines can be deleted.

3. Open `src/index.js`. This is our JavaScript entry point.

## Register a Plugin

We'll use the `registerPlugin` API and the `PluginPostStatusInfo` SlotFill to add our button to the post sidebar.

4. Add the imports and register the plugin. We're front-loading every import we'll need across the rest of the section — `useState`, `useSelect`, `useDispatch`, `serialize`, and `createBlock` won't get used until later steps, so don't worry if your linter complains about unused imports for a few minutes.

```javascript
import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize, createBlock } from '@wordpress/blocks';
import { Button } from '@wordpress/components';

// `@wordpress/abilities` ships only as a runtime ES module via the WordPress
// script module loader — it's not available as a classic script and webpack
// can't resolve it at build time. We use a top-level dynamic `import()` and
// tell webpack to leave it alone with `/* webpackIgnore: true */`. The
// browser fetches the module at runtime via the script module loader, which
// is the same reason the PHP side enqueues this file with
// `wp_enqueue_script_module()` and declares `@wordpress/abilities` as a
// dependency.
//
// Needed until https://github.com/WordPress/gutenberg/issues/75196 is fixed.
const {
	registerAbility,
	registerAbilityCategory,
	getAbilities,
	executeAbility,
	store: abilitiesStore,
} = await import( /* webpackIgnore: true */ '@wordpress/abilities' );

const SummarizationPlugin = () => {
	return (
		<PluginPostStatusInfo>
			<Button variant="primary">
				Generate AI Summary
			</Button>
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'wcpt-summarization', { render: SummarizationPlugin } );
```

The destructure shows everything `@wordpress/abilities` exposes today: ability/category registration, `getAbilities()` for reading what's registered, `executeAbility()` for running one, and the `abilitiesStore` data store for reactive reads with `useSelect`. We'll only use `executeAbility` below.

5. Open **Posts → Hello, Portugal!** (pre-seeded by the blueprint). You should see a **Generate AI Summary** button in the right sidebar. It doesn't do anything yet — let's fix that.

## Get the Post Content

6. Use `useSelect` to get the current blocks from the editor store, then serialize them to a string the AI can read:

```javascript
const SummarizationPlugin = () => {
	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	return (
		<PluginPostStatusInfo>
			<Button variant="primary">
				Generate AI Summary
			</Button>
		</PluginPostStatusInfo>
	);
};
```

## Call the Ability

7. Add loading state and call `executeAbility` when the button is clicked:

```javascript
const SummarizationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );

	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	const handleClick = async () => {
		setIsLoading( true );
		const content = serialize( blocks );

		const summary = await executeAbility( 'wcpt/summarization', {
			content,
			length: 'medium',
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

8. Back in the **Hello, Portugal!** post, click the button and check the browser console. You should see your summary logged. 🎉

## Insert the Summary Block

9. Use `useDispatch` to get the `insertBlock` action, then create a paragraph block with the summary and insert it at position 0 (the top of the content):

```javascript
const SummarizationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );

	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	const { insertBlock } = useDispatch( 'core/block-editor' );

	const handleClick = async () => {
		setIsLoading( true );
		const content = serialize( blocks );

		const summary = await executeAbility( 'wcpt/summarization', {
			content,
			length: 'medium',
		} );

		insertBlock( createBlock( 'core/paragraph', { content: summary } ), 0 );
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

10. Click the button. A paragraph block containing the AI-generated summary should appear at the top of your post content. 🔥🔥🔥

---

<details>
<summary><strong>Alternative: calling the ability with <code>apiFetch</code></strong></summary>

`executeAbility` is a thin convenience wrapper over the REST endpoint WordPress automatically created for our ability in Section 3. If you don't want to use the script module loader — or you're working in a context where `@wordpress/abilities` isn't available — you can call the same endpoint directly with `@wordpress/api-fetch`:

```javascript
import apiFetch from '@wordpress/api-fetch';

const response = await apiFetch( {
	path: '/wp-abilities/v1/abilities/wcpt/summarization/run',
	method: 'POST',
	data: {
		input: {
			content,
			length: 'medium',
		},
	},
} );

const summary = response.output;
```

Notes:

- Input goes inside an `input` wrapper — that's the REST contract. `executeAbility` removes that boilerplate.
- The response is an object; the ability's return value is on `response.output`.
- `apiFetch` is a classic script (`wp-api-fetch`), so you can drop the `webpack.config.js` module config and use a plain `wp_enqueue_script` call with `wp-api-fetch` and `wp-element` etc. in the dependencies array.
- You lose the typed error codes (`ability_permission_denied`, `ability_invalid_input`, `ability_invalid_output`) that `executeAbility` surfaces — `apiFetch` rejects with the raw REST error shape instead.

Same ability. Same result. Lower-level access if you need it.

</details>

---

# Ready to move on?
[Section 5: MCP — Exposing the Ability to AI Agents](./section-5.md)

# Missing something from the last section?
[Section 3: Registering the Summarization Ability](./section-3.md)
