var empty = true;
var textId = '';
var token = '';
var queueName = 'MainAjaxQueue';
//var crypt = new JSEncrypt({default_key_size: 1024});

//console.log("Running key generation...");
//crypt.getKey();
//console.log("Key created");

function b64eurl(text) {
	return btoa(text).replace('+', '-').replace('/', '_');
}

function b64durl(text) {
	return atob(text.replace('-', '+').replace('_', '/'));
}

function b64tourl(text) {
	return text.replace('-', '+').replace('_', '/');
}

function getSetText() {
	$.ajax({
		type: 'GET',
		url: 'http://cookiezcreations.ovh/p.php',
		data: {
			'm': 'text'
		},
		dataType: 'json',
		success: function(data) {
			textId = data.id;
			$('#text').fadeOut(200, function() {
				$('#text').html('"' + data.text + '"');
				$('#text').fadeIn(200);
				$('#p_input').removeAttr('disabled');
			});
		},
		error: function(j, t, e) {
			toastr["error"](j.responseText, "Błąd");
		}
	});
}

function clearInputDialog() {
	$("#inpDialLogin,#inpDialPass,#inpDialComm").val('');
}

function showHideSaveProgress() {
	var anim = $("#syncAnim");
	var vis = anim.is(":visible");
	if($.ajaxq.isRunning()) {
		if(!vis) anim.fadeIn(200);
	}
	else {
		if(vis) anim.fadeOut(200);
	}
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}

function okPressed() { // Od logowania
	$('#btnok,#p_input').attr('disabled', 'disabled');

	var inpfield = $('#p_input');
	var encoded_p = b64eurl(unescape(encodeURIComponent(inpfield.val())));
	inpfield.val("");

	$.ajax({
		type: 'GET',
		url: 'http://cookiezcreations.ovh/p.php',
		data: {
			'm': 'p',
			'i': textId,
			'love': encoded_p
		},
		dataType: 'json',
		success: function(data) {
			if (data.type !== 'success') {
				toastr[data.type]("", data.text);
				getSetText();
				return;
			}
			
			token = data.token;

			$('#tabela_hasua').bootstrapTable('load', JSON.parse(data.table));
			
			$("#newbackground,.tablecontainer").fadeIn(300);
			
			setInterval(showHideSaveProgress, 1000);
		},
		error: function(j, t, e) {
			toastr["error"](j.responseText, "Błąd");
		}
	});
}

$(window).ready(function() {
	$('#logingroup *,#text,.tablecontainer,#newbackground,#syncAnim').removeClass('hidden').hide();
	
	  if(getCookie("dupa") === "dupa") {
		  $("body").toggleClass("hidden");
	  }

	$('#tabela_hasua').on('editable-save.bs.table', function(e, colName, row, oldVal) {
		var editedVal = '';
		var rowek;
		if(colName === "login") {
			editedVal = row.login;
		}
		else if(colName === "password"){
			editedVal = row.password;
		}
		else if(colName === "comment"){
			editedVal = row.comment;
		}
		else {
			toastr["error"]("WTF", "Błąd");
			return;
		}
		
		$.ajaxq(queueName, {
			custom_id: row.id,
			type: 'GET',
			url: 'http://cookiezcreations.ovh/p.php',
			data: {
				'm': 'e',
				'i': textId,
				't': token,
				'fid': row.id,
				'c': colName,
				'v': editedVal
			},
			dataType: 'json',
			success: function(data) {
				toastr[data.type](data.text, data.title);
				token = data.token;
				if(data.type === "success") {
					var par = this;
					$.each($('#tabela_hasua').bootstrapTable('getData'), function(ind, value) {
						if(par.custom_id === value.id) {
							value.editdate = "w tej sesji";
							$('#tabela_hasua').bootstrapTable('updateRow', {index: ind, row: value});
							return false;
						}
					});
				}
				else {
					$("#newbackground,.tablecontainer").fadeOut(300);
					getSetText();
				}
			},
			error: function(j, t, e) {
				toastr["error"](j.responseText, "Błąd");
			}
		});
	});

	toastr.options = {
		"closeButton": false,
		"debug": false,
		"newestOnTop": false,
		"progressBar": false,
		"positionClass": "toast-bottom-left",
		"preventDuplicates": false,
		"onclick": null,
		"showDuration": "300",
		"hideDuration": "1000",
		"timeOut": "5000",
		"extendedTimeOut": "1000",
		"showEasing": "swing",
		"hideEasing": "linear",
		"showMethod": "fadeIn",
		"hideMethod": "fadeOut"
	}

	$.ajax({
		type: 'GET',
		url: 'http://cookiezcreations.ovh/p.php',
		data: {
			'm': 'text'
		},
		dataType: 'json',
		success: function(data) {
			textId = data.id;
			$('#text').html('"' + data.text + '"');
			$('#logingroup *').fadeIn(1000).focus();
			$('#text').delay(500).fadeIn(1000);
		},
		error: function(j, t, e) {
			toastr["error"](j.responseText, "Błąd");
		}
	});

	$("#btnok").click(function() {
		if (!empty) {
			okPressed();
		}
	});

	$('#p_input').keyup(function(e) {
		if (e.which == 13) {
			return true;
		}
		$('#p_input').each(function() {
			if ($(this).val() == '') {
				empty = true;
			} else {
				empty = false;
			}
		});

		if (empty) {
			$('#btnok').attr('disabled', 'disabled');
		} else {
			$('#btnok').removeAttr('disabled');
		}
	});

	$('#p_input').keypress(function(e) {
		if (e.which === 13) {
			$('#btnok').click();
			return false;
		}
	});
	
	$("#btn_del").click(function() {
		var selections = $('#tabela_hasua').bootstrapTable('getAllSelections');
		var indexesList = '';
		if(selections.length > 0) {
			$.each(selections, function(index, value) {
				indexesList += value.id + ',';
			});
			indexesList = indexesList.substring(0, indexesList.length - 1);
			
			$.ajaxq(queueName, {
				type: 'GET',
				url: 'http://cookiezcreations.ovh/p.php',
				data: {
					'm': 'd',
					'i': textId,
					't': token,
					'fi': indexesList
				},
				dataType: 'json',
				success: function(data) {
					toastr[data.type](data.text, data.title);
					token = data.token;
					if(data.type === "success") {
						$('#tabela_hasua').bootstrapTable('uncheckAll');
						$('#tabela_hasua').bootstrapTable('load', JSON.parse(data.table));
					}
					else {
						$("#newbackground,.tablecontainer").fadeOut(300);
						getSetText();
					}
				},
				error: function(j, t, e) {
					toastr["error"](j.responseText, "Błąd");
				}
			});
		}
		else {
			toastr["error"]("Zaznacz przynajmniej jeden element.", "Błąd");
		}
	});
	
	$("#btn_ref").click(function() {
			$.ajaxq(queueName, {
				type: 'GET',
				url: 'http://cookiezcreations.ovh/p.php',
				data: {
					'm': 'r',
					'i': textId,
					't': token
				},
				dataType: 'json',
				success: function(data) {
					toastr[data.type](data.text, data.title);
					token = data.token;
					if(data.type === "success") {
						$('#tabela_hasua').bootstrapTable('uncheckAll');
						$('#tabela_hasua').bootstrapTable('load', JSON.parse(data.table));
					}
					else {
						$("#newbackground,.tablecontainer").fadeOut(300);
						getSetText();
					}
				},
				error: function(j, t, e) {
					toastr["error"](j.responseText, "Błąd");
				}
			});
	});
	
	$('#inpDialLogin,#inpDialPass,#inpDialComm').keypress(function (e) {
	  if (e.which == 13) {
		$('#inpDialK').click();
		return false;
	  }
	});
	
	var $inpDialClickTarget = null;
	$('#dialogNowe * button').on('click', function (e) {
		inpDialClickTarget = $(e.target);
	});
	
	$('#dialogNowe').on('shown.bs.modal', function () {
	  $(this).find('input:text:visible:first').focus();
	  inpDialClickTarget = null;
	});
	
	$('#dialogNowe').on('hidden.bs.modal', function () {
		if(typeof inpDialClickTarget !== 'undefined' && inpDialClickTarget.attr('id') === 'inpDialK') {
			$.ajaxq(queueName, {
				type: 'GET',
				url: 'http://cookiezcreations.ovh/p.php',
				data: {
					'm': 'a',
					'i': textId,
					't': token,
					'fl': $("#inpDialLogin").val(),
					'fp': $("#inpDialPass").val(),
					'com': $("#inpDialComm").val()
				},
				dataType: 'json',
				success: function(data) {
					toastr[data.type](data.text, data.title);
					token = data.token;
					if(data.type === "success") {
						$('#tabela_hasua').bootstrapTable('insertRow', {index: 0, row: data.newelement});
					}
					else {
						$("#newbackground,.tablecontainer").fadeOut(300);
						getSetText();
					}
				},
				error: function(j, t, e) {
					toastr["error"](j.responseText, "Błąd");
				}
			});
		}
		
		clearInputDialog();
	});
	
	$("body").keydown(function(e) {
	  if(e.keyCode == 35) {
		  $("body").toggleClass("hidden");
		  if(getCookie("dupa") === "dupa") {
			  setCookie("dupa", "", 0);
		  }
		  else {
			  setCookie("dupa", "dupa", 300);
			  window.open('http://google.pl');
				window.location.href="http://google.com";
		  }
	  }
	});
	
	$.fn.editable.defaults.emptytext = "Puste";
});
