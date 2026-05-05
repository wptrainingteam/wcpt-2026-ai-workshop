# Section 4 — Block Editor Integration

The REST endpoint works. Now let's wire up a button in the block editor that calls our ability and inserts the summary as a paragraph block. We'll use `@wordpress/abilities` — the JavaScript client for the Abilities API — so we don't have to construct the REST request manually.

Reference: [@wordpress/abilities](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)

## Start the Build

1. In your terminal, start the build process in watch mode:

```bash
npm start
```

Keep this running throughout the section. It will recompile whenever you save a file.

2. Open `src/index.js`. This is our JavaScript entry point.

## Register a Plugin

We'll use the `registerPlugin` API and the `PluginPostStatusInfo` SlotFill to add our button to the post sidebar.

3. Add the imports and register the plugin:

```javascript
import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize } from '@wordpress/blocks';
import { createBlock } from '@wordpress/blocks';
import { executeAbility } from '@wordpress/abilities';
import { Button } from '@wordpress/components';

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

4. Create or edit a post. You should see a **Generate AI Summary** button in the right sidebar. It doesn't do anything yet — let's fix that.

## Get the Post Content

5. Use `useSelect` to get the current blocks from the editor store, then serialize them to a string the AI can read:

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

6. Add loading state and call `executeAbility` when the button is clicked:

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

		const summary = await executeAbility( 'ai/summarization', {
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

7. Write some content in a post, click the button, and check the browser console. You should see your summary logged. 🎉

## Insert the Summary Block

8. Use `useDispatch` to get the `insertBlock` action, then create a paragraph block with the summary and insert it at position 0 (the top of the content):

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

		const summary = await executeAbility( 'ai/summarization', {
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

9. Click the button. A paragraph block containing the AI-generated summary should appear at the top of your post content. 🔥🔥🔥

---

# Ready to move on?
[Section 5: MCP — Exposing the Ability to AI Agents](./section-5.md)

# Missing something from the last section?
[Section 3: Registering the Summarization Ability](./section-3.md)
