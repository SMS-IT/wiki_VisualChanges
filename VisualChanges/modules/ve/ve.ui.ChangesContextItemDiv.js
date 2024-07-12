/**
 * Context item for a visual changes.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.Context} context Context item is in
 * @param {ve.dm.Model} model Model item is related to
 * @param {Object} config Configuration options
 */

ve.ui.ChangesContextItemDiv = function VeUiChangesContextItemDiv( context, model, config ) {

	// Parent constructor
	ve.ui.ChangesContextItemDiv.super.call( this, context, model, config );

	// Initialization
	this.$element.addClass( 've-ui-changesContextItemDiv' );
};

/* Inheritance */

OO.inheritClass( ve.ui.ChangesContextItemDiv, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.ChangesContextItemDiv.static.name = 'changesDiv';

// todo: иконка всплывающего бокса
ve.ui.ChangesContextItemDiv.static.icon = 'code';

ve.ui.ChangesContextItemDiv.static.label = "Изменение (блок)";

ve.ui.ChangesContextItemDiv.static.modelClasses = [ ve.dm.ChangesAnnotationDiv ];

ve.ui.ChangesContextItemDiv.static.embeddable = false;

ve.ui.ChangesContextItemDiv.static.commandName = 'changesDiv';

/**
 * @inheritdoc
 */
ve.ui.ChangesContextItemDiv.static.isCompatibleWith = function ( model ) {
	return model instanceof ve.dm.ChangesAnnotationDiv;
};


/* Methods */

/**
 * @inheritdoc
 */
ve.ui.ChangesContextItemDiv.prototype.isDeletable = function () {
	return true;
};

/**
 * Handle edit button click events.
 *
 * @localdoc Executes the command related to #static-commandName on the context's surface
 *
 * @protected
 */
ve.ui.ChangesContextItemDiv.prototype.onEditButtonClick = function () {

	var command = this.context.getSurface().commandRegistry.lookup( "changesDivEdit" );;

	if ( command ) {
		command.execute( this.context.getSurface() );
		this.emit( 'command' );
	}
};

/**
 * @inheritdoc
 */
ve.ui.ChangesContextItemDiv.prototype.getDescription = function () {
	return ve.ce.ChangesAnnotationDiv.static.getDescription( this.model );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.ChangesContextItemDiv );
