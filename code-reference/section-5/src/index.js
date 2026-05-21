import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize, createBlock } from '@wordpress/blocks';
import { Button, SelectControl, __experimentalVStack as VStack } from '@wordpress/components';

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

	// Optional — uncomment to read the registered abilities reactively from
	// the abilities data store. Useful when building UIs that list or branch
	// on what's registered.
	//
	// const abilities = useSelect(
	// 	( select ) => select( abilitiesStore ).getAbilities(),
	// 	[]
	// );
	//
	// const dataAbilities = useSelect(
	// 	( select ) =>
	// 		select( abilitiesStore ).getAbilities( {
	// 			category: 'wp-ai-workshop',
	// 		} ),
	// 	[]
	// );

	const handleClick = async () => {
		setIsLoading( true );

		// `serialize()` turns the array of block objects into a single
		// post_content-style string — the same format WordPress stores in
		// the database, and exactly what our ability's `content` field
		// expects.
		const content = serialize( blocks );

		// `executeAbility` calls the REST endpoint WordPress created from
		// our ability's schema. The second argument maps to the registered
		// `input_schema`. The return value is whatever `output_schema`
		// declares — a string here.
		const summary = await executeAbility( 'wp-ai-workshop/summarization', {
			content,
			length,
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
		<PluginPostStatusInfo className="wp-ai-workshop-summarization-panel">
			<VStack spacing={ 3 } style={ { width: '100%' } }>
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
					style={ { justifyContent: 'center', width: '100%' } }
				>
					{ isLoading ? 'Generating…' : 'Generate AI Summary' }
				</Button>
			</VStack>
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'wp-ai-workshop-summarization', { render: SummarizationPlugin } );
