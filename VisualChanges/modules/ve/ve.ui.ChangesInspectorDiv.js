/**
 * Inspector for specifying the visual changes of content.
 *
 * @class
 * @extends ve.ui.NodeInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.ChangesInspectorDiv = function VeUiChangesInspectorDiv() {
	// Parent constructor
	ve.ui.ChangesInspectorDiv.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.ChangesInspectorDiv, ve.ui.NodeInspector );

/* Static properties */

ve.ui.ChangesInspectorDiv.static.name = 'changesDiv';
ve.ui.ChangesInspectorDiv.static.title = 'Изменение (блок)';
ve.ui.ChangesInspectorDiv.static.modelClasses = [ ve.dm.ChangesAnnotationDiv ];

/* Methods */

ve.ui.ChangesInspectorDiv.prototype.getChangeInfo = function () {
	var task = this.taskInput.getTask();
	var tag = this.taskInput.getTag();
	var forpage = this.taskInput.getForpage();
	return ( task || tag ?
		new ve.dm.ChangesAnnotationDiv( {
			type: 'changesDiv',
			attributes: {
				task: task ? task : "",
				tag: tag ? tag : "",
				forpage: forpage ? forpage : "",
			}
		} ) : null
	);
};


/**
 * @inheritdoc
 */
ve.ui.ChangesInspectorDiv.prototype.initialize = function () {
	// Parent method
	ve.ui.ChangesInspectorDiv.super.prototype.initialize.call( this );

	// Properties
	this.taskInput = new ve.ui.ChangesInputWidget( {
		dialogManager: this.manager.getSurface().getDialogs()
	} );

	// Initialization
	this.form.$element.append( this.taskInput.$element );
	this.$content.addClass( 've-ui-ChangesInspectorDiv-content' );
};


/**
 * @inheritdoc
 */
ve.ui.ChangesInspectorDiv.prototype.getSetupProcess = function ( data ) {
	return ve.ui.ChangesInspectorDiv.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var node = data.fragment.surface.getSelectedNode();
			this.taskInput.setTask(node.element.attributes && node.element.attributes.task ? node.element.attributes.task : "");
			this.taskInput.setTag(node.element.attributes && node.element.attributes.tag ? node.element.attributes.tag : "");
			this.taskInput.setForpage(node.element.attributes && node.element.attributes.forpage ? node.element.attributes.forpage : "");
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.ChangesInspectorDiv.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.ChangesInspectorDiv.super.prototype.getTeardownProcess.call(this, data)
	.first( function () {
		if (data.action === 'done') {
			var node = this.fragment.surface.getSelectedNode();
			var changes = this.getChangeInfo();
			node.element.attributes = changes ? changes.element.attributes : undefined;
		}
	}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.ChangesInspectorDiv );
