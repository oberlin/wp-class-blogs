(function($) {

/**
 * A manager for student blog links contained in a table with one set of fields
 * defining a student link per row and an HTML link that creates another row
 * of fields for defining a student link.
 *
 * @param table {object} a reference to the student-links table DOM element
 * @constructor
 */
var StudentLinksManager = function(tableID) {
	this._$links = $(tableID);
	this._bindEvents();
};

/**
 * CSS identifiers used for managing student links.
 *
 * @type {object}
 * @private
 */
StudentLinksManager.prototype._CSS = {

	// The add-another link
	'addLink': ".add-link",

	// A link to delete a student link
	'deleteLinks': ".delete-link",

	// A wrapper for link input fields
	'links': ".link"
};

/**
 * Register the event handlers used by the link manager to add links.
 *
 * @private
 */
StudentLinksManager.prototype._bindEvents = function() {
	this._$links.find(this._CSS.addLink).click($.proxy(this._addLink, this));
	this._$links.delegate(this._CSS.deleteLinks, 'click', $.proxy(this._deleteLink, this));
};

/**
 * Creates another set of fields for adding a student link.
 *
 * @param e {object} an event object generated by clicking the "add link" button
 * @private
 */
StudentLinksManager.prototype._addLink = function(e) {

	e.preventDefault();

	// Clone the last row of links
	var $newLink = this._$links.find(this._CSS.links + ":visible:last").clone();
	this._$links.append($newLink);
	$newLink.find("input").val('');

	// Update the new row's input attributes, setting any numerical component of
	// field labels or IDs to the new number of rows in the table
	var newID = this._$links.find(this._CSS.links).length - 1;
	var updateFields = {
		'label': ['for'],
		'input': ['name', 'id']
	};
	var fieldName, i, $el, attr;
	for (fieldName in updateFields) {
		if (updateFields.hasOwnProperty(fieldName)) {
			$.each($newLink.find(fieldName), function(i, el) {
				for (i=0; i<updateFields[fieldName].length; i++) {
					$el = $(el);
					attr = $el.attr(updateFields[fieldName][i]);
					$el.attr(updateFields[fieldName][i], attr.replace(/\d+$/, newID));
				}
			});
		}
	};
};

/**
 * Delete a single student link, hiding its fields in the table.
 *
 * @param e {object} an event object generated by clicking the "delete link" link
 * @private
 */
StudentLinksManager.prototype._deleteLink = function(e) {

	e.preventDefault();

	// Clear any inputs in the current row and hide it
	var $link = $(e.target).parents(this._CSS.links);
	$link.find("input").val('');
	$link.hide();
};

/** Set up the student-links manager once the page is ready. */
$(document).ready(function() {
	var manager = new StudentLinksManager("#student-blog-links");
});

})(jQuery);
