YUI.add("moodle-mod_bookking-saveseen",function(e,t){var n={CHECKBOXES:"table#slotmanager form.studentselectform input.studentselect"},r;M.mod_bookking=M.mod_bookking||{},r=M.mod_bookking.saveseen={},r.save_status=function(t,n,r,i){e.io(M.cfg.wwwroot+"/mod/bookking/ajax.php",{data:{action:"saveseen",id:t,appointmentid:n,seen:r,sesskey:M.cfg.sesskey},timeout:5e3,on:{start:function(e){i.show()},success:function(e,t){window.setTimeout(function(){i.hide()},250)},failure:function(e,t){var n={name:t.status+" "+t.statusText,message:t.responseText};return i.hide(),new M.core.exception(n)}},context:this})},r.init=function(t){e.all(n.CHECKBOXES).each(function(n){n.on("change",function(r){var i=M.util.add_spinner(e,n.ancestor("div"));M.mod_bookking.saveseen.save_status(t,n.get("value"),n.get("checked"),i)})})}},"@VERSION@",{requires:["base","node","event"]});
