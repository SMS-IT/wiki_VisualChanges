/**
 * Creates an ve.ui.ChangesInputWidget object.
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {string} [dirInput='auto'] How to display the directionality input. Options are:
 *      - none: Directionality input is hidden.
 *      - no-auto: Directionality input is visible and options are LTR or RTL.
 *      - auto: Directionality input is visible and options include "auto" in
 *            addition to LTR and RTL.
 * @cfg {boolean} [hideCodeInput] Prevent user from entering a language code as free text
 * @cfg {ve.ui.WindowManager} [dialogManager] Window manager to launch the language search dialog in
 * @cfg {string[]} [availableLanguages] Available language codes to show in search dialog
 */

ve.ui.ChangesInputWidget = function VeUiChangesInputWidget( config ) {
	var taskLayoutConfig, tagLayoutConfig;
	// Configuration initialization
	config = config || {};

	// Parent constructor
	ve.ui.ChangesInputWidget.super.call( this, config );

	// Properties
	this.task = null;
	this.tag = null;
	this.forpage = null;

	this.overlay = new ve.ui.Overlay( { classes: [ 've-ui-overlay-global' ] } );
	this.dialogs = config.dialogManager || new ve.ui.WindowManager( { factory: ve.ui.windowFactory } );

	this.taskTextInput = new OO.ui.TextInputWidget( {
		classes: [ 've-ui-mwExtensionWindow-input' ]
	} );
	this.tagTextInput = new OO.ui.TextInputWidget( {
		classes: [ 've-ui-mwExtensionWindow-input' ]
	} );
	this.forpageTextInput = new OO.ui.TextInputWidget( {
		classes: [ 've-ui-mwExtensionWindow-input' ]
	} );

	taskLayoutConfig = {
		align: 'left',
		label: 'Задача'
	};
	tagLayoutConfig = {
		align: 'left',
		label: 'Тег'
	};
	forpageLayoutConfig = {
		align: 'left',
		label: 'Для страницы (yes)'
	};

	this.taskLayout = new OO.ui.FieldLayout(
		this.taskTextInput, taskLayoutConfig
	);
	this.tagLayout = new OO.ui.FieldLayout(
		this.tagTextInput, tagLayoutConfig
	);
	this.forpageLayout = new OO.ui.FieldLayout(
		this.forpageTextInput, forpageLayoutConfig
	);

	// Events
	this.taskTextInput.connect( this, { change: 'onChange' } );
	this.tagTextInput.connect(this, { change: 'onChange' });
	this.forpageTextInput.connect(this, { change: 'onChange' });

	// Initialization

	this.overlay.$element.append( this.dialogs.$element );
	$( 'body' ).append( this.overlay.$element );

	this.$element
		.addClass( 've-ui-languageInputWidget' )
		.append( this.taskLayout.$element )
		.append( this.tagLayout.$element )
		.append( this.forpageLayout.$element )
		;

};

/* Inheritance */

OO.inheritClass( ve.ui.ChangesInputWidget, OO.ui.Widget );

/* Events */

/**
 * Handle input widget change events.
 */
ve.ui.ChangesInputWidget.prototype.onChange = function () {
	var selectedItem;
	if ( this.updating ) {
		return;
	}

	this.setTask(
		this.taskTextInput.getValue()
	);
	this.setTag(
		this.tagTextInput.getValue()
	);
	this.setForpage(
		this.forpageTextInput.getValue()
	);
};

/**
 * Set language and directionality
 *
 * The inputs value will automatically be updated.
 *
 * @param {string} lang Language code
 * @param {string} dir Directionality
 * @fires change
 */
 // changing parameters of widget
ve.ui.ChangesInputWidget.prototype.setTask = function ( task ) {

	if ( task === this.task ) {
		// No change
		return;
	}

	// Set state flag while programmatically changing input widget values
	this.updating = true;
	if ( task ) {
		task = task || '';
		this.taskTextInput.setValue( task );
	} else {
		this.taskTextInput.setValue( '' );
	}
	this.updating = false;

	this.emit( 'change', task );
	this.task = task;
};

ve.ui.ChangesInputWidget.prototype.setTag = function ( tag ) {
	if ( tag === this.tag ) {
		// No change
		return;
	}

	// Set state flag while programmatically changing input widget values
	this.updating = true;
	if ( tag ) {
		tag = tag || '';
		this.tagTextInput.setValue( tag );
	} else {
		this.tagTextInput.setValue( '' );
	}
	this.updating = false;

	this.emit( 'change', tag );
	this.tag = tag;
};

ve.ui.ChangesInputWidget.prototype.setForpage = function ( forpage ) {
	if ( forpage === this.forpage ) {
		// No change
		return;
	}

	// Set state flag while programmatically changing input widget values
	this.updating = true;
	if ( forpage ) {
		forpage = forpage || '';
		this.forpageTextInput.setValue( forpage );
	} else {
		this.forpageTextInput.setValue( '' );
	}
	this.updating = false;

	this.emit( 'change', forpage );
	this.forpage = forpage;
};


/**
 * Get the task
 *
 * @return {string} task code
 */
 ve.ui.ChangesInputWidget.prototype.getTask = function () {
	 return this.task;
 };

/**
 * Get the tag
 *
 * @return {string} task code
 */
ve.ui.ChangesInputWidget.prototype.getTag = function () {
	return this.tag;
};

/**
 * Get the forpage
 *
 * @return {string} forpage value
 */
ve.ui.ChangesInputWidget.prototype.getForpage = function () {
	return this.forpage;
};
