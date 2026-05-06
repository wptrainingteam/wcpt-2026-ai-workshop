import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize, createBlock } from '@wordpress/blocks';
import { Button } from '@wordpress/components';

// `@wordpress/abilities` ships only as a runtime ES module — it lives behind
// WordPress's script module loader and is not available as a classic script
// or as something webpack can resolve at build time.
//
// Our bundle is built as a *classic* script (the default for
// `@wordpress/scripts`). If we wrote `import { executeAbility } from
// '@wordpress/abilities'`, webpack would try to resolve the package at build
// time and fail. Instead, we use a top-level dynamic `import()` and tell
// webpack to leave it alone with `/* webpackIgnore: true */`. The browser
// then fetches the module at runtime via the script module loader — which is
// also why the PHP side enqueues this file with `wp_enqueue_script_module()`
// and declares `@wordpress/abilities` as a script-module dependency.
//
// Needed until https://github.com/WordPress/gutenberg/issues/75196 is fixed.
// The destructure below shows everything the package exposes today.
const {
	registerAbility,
	registerAbilityCategory,
	getAbilities,
	executeAbility,
	store: abilitiesStore,
} = await import( /* webpackIgnore: true */ '@wordpress/abilities' );

const SummarizationPlugin = () => {
	const [ isLoading, setIsLoading ] = useState( false );

	const blocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks(),
		[]
	);

	const { insertBlock } = useDispatch( 'core/block-editor' );

	// You can read abilities reactively from `abilitiesStore` with `useSelect`.
	// Uncomment either example to see how to grab all abilities or filter by
	// category — useful for building UIs that list/branch on what's registered.
	//
	// // Get all abilities reactively
	// const abilities = useSelect(
	// 	( select ) => select( abilitiesStore ).getAbilities(),
	// 	[]
	// );
	//
	// // Filter by category
	// const dataAbilities = useSelect(
	// 	( select ) =>
	// 		select( abilitiesStore ).getAbilities( {
	// 			category: 'wcpt-workshop',
	// 		} ),
	// 	[]
	// );

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

registerPlugin( 'wcpt-summarization', { render: SummarizationPlugin } );
