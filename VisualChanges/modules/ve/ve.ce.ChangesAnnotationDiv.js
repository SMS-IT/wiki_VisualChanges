/**
 * ContentEditable visual changes block.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.ChangesAnnotationDiv} model Model to observe
 * @param {ve.ce.ContentBranchNode} [parentNode] Node rendering this block
 * @param {Object} [config] Configuration options
 */
 ve.ce.ChangesAnnotationDiv = function VeCeChangesAnnotationDiv() {
	// Parent constructor
	ve.ce.ChangesAnnotationDiv.super.apply( this, arguments );

	// Events
	 this.model.connect( this, {
		 update: 'onUpdate'
	 } );

	// Properties
	this.surface = null;
	this.active = false;
};

/* Inheritance */

OO.inheritClass( ve.ce.ChangesAnnotationDiv, ve.ce.BranchNode );

/* Static Properties */

ve.ce.ChangesAnnotationDiv.static.name = 'changesDiv';
ve.ce.ChangesAnnotationDiv.static.primaryCommandName = 'change';
// во что преобразовать модель
ve.ce.ChangesAnnotationDiv.static.tagName = 'div';
ve.ce.ChangesAnnotationDiv.static.isMultiline = true;

/* Override Methods */

/**
 * @inheritdoc
 */
ve.ce.ChangesAnnotationDiv.prototype.initialize = function () {
	var tag, task, title, forpage;

	// Parent method
	ve.ce.ChangesAnnotationDiv.super.prototype.initialize.call( this );

	task = this.model.getTask();
	tag = this.model.getTag();
	forpage = this.model.getForpage();
	title = this.constructor.static.getDescription(this.model);

	// DOM changes
	this.$element
		.addClass( 'in-develop ve-ce-changesDiv' );

	// Set attributes (keep in sync with #onSetup)
	this.$element.attr( 'task', task );
	this.$element.attr( 'tag', tag );
	this.$element.attr( 'forpage', forpage );
	this.$element.attr( 'title', title );
	this.$element.attr( 'data-title', title );
};

/**
 * Handle model update events.
 *
 * If the style changed since last update the DOM wrapper will be replaced with an appropriate one.
 *
 * @method
 */
ve.ce.ChangesAnnotationDiv.prototype.onUpdate = function () {
	this.updateTagName();
};

ve.ce.ChangesAnnotationDiv.prototype.onSetup = function () {
	// Parent method
	ve.ce.ChangesAnnotationDiv.super.prototype.onSetup.call( this );

	// Exit if already setup or not attached
	if ( this.isSetup || !this.root ) {
		return;
	}
	this.surface = this.getRoot().getSurface();

	// Overlay
	this.$selectionBox = $( '<div>' ).addClass( 've-ce-changesNodeOverlay-selection-box' );
	this.$selectionBoxAnchor = $( '<div>' ).addClass( 've-ce-changesNodeOverlay-selection-box-anchor' );

	this.$overlay = $( '<div>' )
		.addClass( 've-ce-changesNodeOverlay oo-ui-element-hidden' )
		.append( [
			this.$selectionBox,
			this.$selectionBoxAnchor,
		] );
	this.surface.surface.$blockers.append( this.$overlay );


	/////!!!!!
	var task = this.model.getTask();
	var tag = this.model.getTag();
	var res = '';
	if (task != "") res += '#' + task;
	if (tag != "") res += ' Тэг: ' + tag;
	this.$xxx = $( '<span class="tagtask">' + res + '</span>' );
	this.$element.prepend(this.$xxx);


	// Events
	this.$element.on( {
		'dblclick.ve-ce-changesDiv': this.onVChangeMouseDown.bind( this )
	} );
	this.$overlay.on( {
		'dblclick.ve-ce-changesDiv': this.onVChangeMouseDown.bind( this )
	} );
};

/**
 * Handle mouse down or touch start events
 *
 * @param {jQuery.Event} e Mouse down or touch start event
 */
ve.ce.ChangesAnnotationDiv.prototype.onVChangeMouseDown = function ( e ) {
	var cellNode, startCell, endCell, selection, newSelection,
		node = this;

	cellNode = this.getChangeNodeFromEvent( e );
	if ( !cellNode ) {
		return;
	}
	newSelection = new ve.dm.LinearSelection(
		this.getModel().getDocument(),
		this.getModel().getOuterRange()
	);
	this.surface.getModel().setSelection( newSelection );
	// e.preventDefault(); // may be need... don't know
};

/**
 * Get a table cell node from a mouse event
 *
 * Works around various issues with touch events and browser support.
 *
 * @param {jQuery.Event} e Mouse event
 * @return {ve.ce.ChangesAnnotationDiv|null} node
 */
ve.ce.ChangesAnnotationDiv.prototype.getChangeNodeFromEvent = function ( e ) {
	var touch;

	// 'touchmove' doesn't give a correct e.target, so calculate it from coordinates
	if ( e.type === 'touchstart' && e.originalEvent.touches.length > 1 ) {
		// Ignore multi-touch
		return null;
	} else if ( e.type === 'touchmove' ) {
		if ( e.originalEvent.touches.length > 1 ) {
			// Ignore multi-touch
			return null;
		}
		touch = e.originalEvent.touches[ 0 ];
		return this.getChangeNodeFromPoint( touch.clientX, touch.clientY );
	} else {
		return this.getNearestChangeNode( e.target );
	}
};


/**
 * Get the cell node from a point
 *
 * @param {number} x X offset
 * @param {number} y Y offset
 * @return {ve.ce.TableCellNode|null} Table cell node, or null if none found
 */
ve.ce.ChangesAnnotationDiv.prototype.getChangeNodeFromPoint = function ( x, y ) {
	return this.getNearestChangeNode(
		this.surface.getElementDocument().elementFromPoint( x, y )
	);
};

/**
 * Get the nearest cell node in this table to an element
 *
 * If the nearest cell node is in another table, return null.
 *
 * @param {HTMLElement} element Element target to find nearest cell node to
 * @return {ve.ce.TableCellNode|null} Table cell node, or null if none found
 */
ve.ce.ChangesAnnotationDiv.prototype.getNearestChangeNode = function ( element ) {
	var ignoreSet = new Set(['td', 'tr']);
	var $element = $( element );
	if ($element.data('view').parent && ignoreSet.has($element.data('view').parent.tagName))
		return undefined;
	return $element.closest("div.ve-ce-changesDiv").data('view');
};


/**
 * @inheritdoc
 */
ve.ce.ChangesAnnotationDiv.prototype.onTeardown = function (data) {
	// Parent method
	ve.ce.ChangesAnnotationDiv.super.prototype.onTeardown.call( this );
	// Events
	this.$element.off( '.ve-ce-changesDiv' );
	this.$overlay.off( '.ve-ce-changesDiv' );
	this.surface.getModel().disconnect( this );
	this.surface.disconnect( this );
	this.$overlay.remove();
};


/* Static Methods */

/**
 * @inheritdoc
 */
ve.ce.ChangesAnnotationDiv.static.getDescription = function ( model ) {
	var task = ( model.getAttribute( 'task' ) || '' );
	var tag = ( model.getAttribute( 'tag' ) || '' );
	var forpage = ( model.getAttribute( 'forpage' ) || '' );

	$res = "";
	if (task != "") {
		$res += "Задача: #" + task;
	}
	if (tag != ""){
		if ($res != "") $res += "\r\n";
		$res += "Тег: " + tag;
	}
	if (forpage != ""){
		if ($res != "") $res += "\r\n";
		$res += "Для страницы: " + forpage;
	}

	return ve.msg( 'visualeditor-languageannotation-description', $res );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.ChangesAnnotationDiv );
