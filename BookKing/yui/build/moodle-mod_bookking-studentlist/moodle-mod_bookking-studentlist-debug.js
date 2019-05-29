YUI.add('moodle-mod_bookking-studentlist', function (Y, NAME) {


var CSS = {
	EXPANDED: 'expanded',
	COLLAPSED: 'collapsed'
};

M.mod_bookking = M.mod_bookking || {};
MOD = M.mod_bookking.studentlist = {};

MOD.setState = function(id, expanded) {
	image = Y.one('#'+id);
	content = Y.one('#list'+id);
	if (expanded) {
		content.removeClass(CSS.COLLAPSED);
		content.addClass(CSS.EXPANDED);
		image.set('src', M.util.image_url('t/expanded'));
    } else {
		content.removeClass(CSS.EXPANDED);
		content.addClass(CSS.COLLAPSED);
		image.set('src', M.util.image_url('t/collapsed'));
	}
};

MOD.toggleState = function(id) {
	content = Y.one('#list'+id);
	isVisible = content.hasClass(CSS.EXPANDED);
	this.setState(id, !isVisible);
};

MOD.init = function(imageid, expanded) {
	this.setState(imageid, expanded);
	Y.one('#'+imageid).on('click', function(e){
		M.mod_bookking.studentlist.toggleState(imageid);
	});
};


}, '@VERSION@', {"requires": ["base", "node", "event", "io"]});
