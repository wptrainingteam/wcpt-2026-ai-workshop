import { registerPlugin } from '@wordpress/plugins';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { serialize, createBlock } from '@wordpress/blocks';
import { Button, SelectControl, __experimentalVStack as VStack } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

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
					length,
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
