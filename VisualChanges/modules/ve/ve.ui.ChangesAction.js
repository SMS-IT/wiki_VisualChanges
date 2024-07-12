/**
 * VisualChanges action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 */
ve.ui.ChangesAction = function VeUiChangesAction() {
	// Parent constructor
	ve.ui.ChangesAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.ChangesAction, ve.ui.Action );

/* Static Properties */

ve.ui.ChangesAction.static.name = 'changesAction';

/**
 * List of allowed methods for the action.
 *
 * @static
 * @property
 */
ve.ui.ChangesAction.static.methods = [
	'create', 'delete', 'changeChangeStyle', 'enterChange'
];

/* Methods */

/**
 * Creates a new changes.
 *
 * @param {Object} [options] VisualChanges creation options
 * @return {boolean} Action was executed
 */
ve.ui.ChangesAction.prototype.create = function ( options ) {
	var i, type, changeElement, surfaceModel, fragment, data;

	options = options || {};
	type = options.type || 'changesDiv';
	changeElement = { type: type };
	surfaceModel = this.surface.getModel();
	fragment = surfaceModel.getFragment();
	data = [];
	if ( !( fragment.getSelection() instanceof ve.dm.LinearSelection ) ) {
		return false;
	}

	if ( options.attributes ) {
		changeElement.attributes = ve.copy( options.attributes );
	}

	data.push( changeElement );
	data.push(
		{ type: 'paragraph', internal: { generated: 'wrapper' } },
		{ type: '/paragraph' }
	);
	data.push( { type: '/' + type } );
	fragment.insertContent( data, false );
	surfaceModel.setSelection(
		new ve.dm.LinearSelection(fragment.getDocument(), fragment.getSelection().getRange())
	);
	return true;
};

/**
 * Deletes selected rows, columns, or the whole changes.
 *
 * @param {string} mode Deletion mode; 'row' to delete rows, 'col' for columns, 'table' to remove the whole table
 * @return {boolean} Action was executed
 */
ve.ui.ChangesAction.prototype.delete = function ( mode ) {
	var tableNode, minIndex, maxIndex, isFull,
		selection = this.surface.getModel().getSelection();

	if ( !( selection instanceof ve.dm.TableSelection ) ) {
		return false;
	}

	tableNode = selection.getTableNode();
	// Either delete the table or rows or columns
	if ( mode === 'table' ) {
		this.deleteTable( tableNode );
	} else {
		if ( mode === 'col' ) {
			minIndex = selection.startCol;
			maxIndex = selection.endCol;
			isFull = selection.isFullRow();
		} else {
			minIndex = selection.startRow;
			maxIndex = selection.endRow;
			isFull = selection.isFullCol();
		}
		// Delete the whole table if all rows or cols get deleted
		if ( isFull ) {
			this.deleteTable( tableNode );
		} else {
			this.deleteRowsOrColumns( tableNode.matrix, mode, minIndex, maxIndex );
		}
	}
	return true;
};

/**
 * Change cell style
 *
 * @param {string} style Cell style; 'header' or 'data'
 * @return {boolean} Action was executed
 */
ve.ui.ChangesAction.prototype.changeChangeStyle = function ( style ) {
	var i, ranges,
		txBuilders = [],
		surfaceModel = this.surface.getModel(),
		selection = surfaceModel.getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return false;
	}

	ranges = selection.getOuterRanges();
	for ( i = ranges.length - 1; i >= 0; i-- ) {
		txBuilders.push(
			ve.dm.TransactionBuilder.static.newFromAttributeChanges.bind( null,
				surfaceModel.getDocument(), ranges[ i ].start, { style: style }
			)
		);
	}
	txBuilders.forEach( function ( txBuilder ) {
		surfaceModel.change( txBuilder() );
	} );
	return true;
};

/**
 * Enter a table cell for editing
 *
 * @return {boolean} Action was executed
 */
ve.ui.ChangesAction.prototype.enterChange = function () {
	enter
	var tableNode,
		selection = this.surface.getModel().getSelection();

	if ( !( selection instanceof ve.dm.LinearSelection ) ) {
		return false;
	}
	tableNode = this.surface.getView().documentView.getBranchNodeFromOffset( selection.tableRange.start + 1 );
	tableNode.setEditing( true );
	this.surface.getView().focus();
	return true;
};

/* Low-level API */
// TODO: This API does only depends on the model so it should possibly be moved

// /**
 // * Deletes a whole table.
 // *
 // * @param {ve.dm.TableNode} tableNode Table node
 // */
// ve.ui.ChangesAction.prototype.deleteTable = function ( tableNode ) {
	// this.surface.getModel().getLinearFragment( tableNode.getOuterRange() ).delete();
// };

/* Registration */

ve.ui.actionFactory.register( ve.ui.ChangesAction );
