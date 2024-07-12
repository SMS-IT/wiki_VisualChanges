/**
 * DataModel visual changes.
 *
 * Represents `<change>` tags with 'task' and 'tag' properties.
 *
 * @class
 * @extends ve.dm.BranchNode
 * @constructor
 * @param {Object} element
 */

ve.dm.ChangesAnnotationDiv = function VeDmChangesAnnotationDiv() {
	// Parent constructor
	ve.dm.ChangesAnnotationDiv.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ChangesAnnotationDiv, ve.dm.BranchNode );

/* Prototypes */

/* Static Properties */

ve.dm.ChangesAnnotationDiv.static.name = 'changesDiv';
ve.dm.ChangesAnnotationDiv.static.extensionName = 'change';
ve.dm.ChangesAnnotationDiv.static.matchTagNames = [ 'change' ];
ve.dm.ChangesAnnotationDiv.static.tagName = 'change';
ve.dm.ChangesAnnotationDiv.static.preserveHtmlAttributes = false;

/* функция поиска элемента */
ve.dm.ChangesAnnotationDiv.static.matchFunction = function ( domElement ) {
	//return domElement.innerText.indexOf('\n') != -1;
	// если это тег change значит то, что нам нужно
	 return true;
};

ve.dm.ChangesAnnotationDiv.static.toDataElement = function ( domElements, converter ) {
	var element = ve.dm.ChangesAnnotationDiv.parent.static.toDataElement.call( this, domElements[0], converter );
	$node = JSON.parse(domElements[0].getAttribute( 'data-mw' ));
	var dataElement = null;
	//todo можно убрать первый if, он на него попадать не должен
	if ($node) {
		if ($node && $node.name == 'change'){
			dataElement = {
				type: this.name,
				attributes: {
					task: $node.attrs.task ? $node.attrs.task.replace('#', '') : '',
					tag: $node.attrs.tag ? $node.attrs.tag : '',
					forpage: $node.attrs.forpage ? $node.attrs.forpage : ''
				}
			};
		}
	} else {
		$node = JSON.parse(domElements[0].getAttribute('data-parsoid'));
		if ($node) {
			dataElement = {
				type: this.name,
				attributes: {
					task: $node.sa && $node.sa.task ? $node.sa.task : "",
					tag: $node.sa && $node.sa.tag ? $node.sa.tag : "",
					forpage: $node.sa && $node.sa.forpage ? $node.sa.forpage : ""
				}
			};
		}
	}

	element = Object.assign(element, dataElement);
	return element;
};

ve.dm.ChangesAnnotationDiv.static.toDomElements = function ( dataElement, doc ) {
	var domElement = doc.createElement( 'change' );
	var attrs = {
		task: dataElement.attributes && dataElement.attributes.task ? dataElement.attributes.task : '',
		tag: dataElement.attributes && dataElement.attributes.tag ? dataElement.attributes.tag : '',
		forpage: dataElement.attributes && dataElement.attributes.forpage ? dataElement.attributes.forpage : ''
	};
	ve.setDomAttributes(domElement, attrs);

	return [ domElement ];
};

/* Methods */

/**
 * Get the task
 *
 * @return {number} Rows spanned
 */
ve.dm.ChangesAnnotationDiv.prototype.getTask = function () {
	return this.element.attributes && this.element.attributes.task ? this.element.attributes.task : "";
};

/**
 * Get the tag
 *
 * @return {number} Columns spanned
 */
ve.dm.ChangesAnnotationDiv.prototype.getTag = function () {
	return this.element.attributes && this.element.attributes.tag ? this.element.attributes.tag : "";
};

/**
 * Get the forpage
 *
 * @return {number} Columns spanned
 */
ve.dm.ChangesAnnotationDiv.prototype.getForpage = function () {
	return this.element.attributes && this.element.attributes.forpage ? this.element.attributes.forpage : "";
};


/* Registration */

ve.dm.modelRegistry.register( ve.dm.ChangesAnnotationDiv );
