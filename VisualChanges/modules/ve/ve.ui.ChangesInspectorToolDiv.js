/**
 * UserInterface visual changes tool.
 *
 * @class
 * @extends ve.ui.FragmentInspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.ChangesInspectorToolDiv = function VeUiChangesInspectorToolDiv() {
	ve.ui.ChangesInspectorToolDiv.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.ChangesInspectorToolDiv, ve.ui.FragmentInspectorTool );

ve.ui.ChangesInspectorToolDiv.static.name = 'changesDiv';
ve.ui.ChangesInspectorToolDiv.static.group = 'mw';
ve.ui.ChangesInspectorToolDiv.static.icon = 'code';
ve.ui.ChangesInspectorToolDiv.static.title = 'Изменение (блок)';

ve.ui.ChangesInspectorToolDiv.static.modelClasses = [ ve.dm.ChangesAnnotationDiv ];
ve.ui.ChangesInspectorToolDiv.static.commandName = 'changesDiv';

ve.ui.toolFactory.register( ve.ui.ChangesInspectorToolDiv );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'changesDiv', 'changesAction', 'create',
		{ args: [ 'changesDiv' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'changesDivEdit', 'window', 'open',
		{ args: [ 'changesDiv' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.triggerRegistry.register(
    'changesDiv', {
        mac: new ve.ui.Trigger('cmd+alt+b'),
        pc: new ve.ui.Trigger('ctrl+alt+b')
    }
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextChangesDiv', 'changesDiv', '<block', 6 )
);

ve.ui.commandHelpRegistry.register( 'insert', 'change', {
	sequences: [ 'wikitextChangesDiv' ],
	label: 'VisualChanges'
} );
