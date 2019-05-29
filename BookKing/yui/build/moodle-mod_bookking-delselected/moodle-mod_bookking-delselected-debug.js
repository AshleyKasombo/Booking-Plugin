YUI.add('moodle-mod_bookking-delselected', function (Y, NAME) {

var SELECTORS = {
        DELACTION: 'div.commandbar a#delselected',
        SELECTBOX: 'table#slotmanager input.slotselect'
    },
    MOD;
 
M.mod_bookking = M.mod_bookking || {};
MOD = M.mod_bookking.delselected = {};

/**
 * Copy the selected boexs into an input parameter of the respective form
 *
 * @return void
 */
MOD.collect_selection = function(link, baseurl) {

	var sellist = '';
	Y.all(SELECTORS.SELECTBOX).each( function(box) {
		if (box.get('checked')) {
			if (sellist.length > 0) {
				sellist += ',';
			}
			sellist += box.get('value');
		}
	});
	link.setAttribute('href', baseurl+'&items='+sellist);
};

MOD.init = function(baseurl) {
	var link = Y.one(SELECTORS.DELACTION);
	if (link != null) {
		link.on('click', function(e) {
			M.mod_bookking.delselected.collect_selection(link, baseurl);
		});
	}
};

}, '@VERSION@', {"requires": ["base", "node", "event"]});
