var last_added_product = null;
var last_removed_product = null;

var wish_event_simple_added = new Event('wish_was_added_simple');
var wish_event_simple_removed = new Event('wish_was_removed_simple');

var remove_from_compare_list_removed = new Event('removed_from_compare_list');
// var wish_list_remove_event = new Event('rm_wish_list');

// var single_wwl_rm = new Event('ev_single_wwl_rm');
// var single_wwl_add = new Event('ev_single_wwl_add');


document.querySelector('body').addEventListener('wish_was_added_simple', wwl_state_to_remove);

function wwl_state_to_remove(){
	alert('Пробукт был добавлен в список желаний!');
	document.querySelector('[data-item-id="' + last_added_product + '"]').setAttribute('data-wm-wwl', 'remove');
	document.querySelector('.shop-icons .likes .number').innerText = document.querySelector('.shop-icons .likes .number').innerText * 1 + 1;
}

document.querySelector('body').addEventListener('wish_was_removed_simple', wwl_state_to_add);

function wwl_state_to_add(){
	alert('Пробукт был удален из списка желаний!');
	document.querySelector('[data-item-id="' + last_removed_product + '"]').setAttribute('data-wm-wwl', 'add');
	document.querySelector('.shop-icons .likes .number').innerText = document.querySelector('.shop-icons .likes .number').innerText * 1 - 1;
}

document.querySelector('body').addEventListener('removed_from_compare_list', wwl_rm_wish_list);

function wwl_rm_wish_list(){
	alert('Продукт был удален из избранного!');
	document.querySelector('[data-wm-prod-id="'+last_removed_product+'"]').classList.add('wm-hid');
}

// document.querySelector('body').addEventListener('ev_single_wwl_rm', fn_single_wwl_rm);

// function fn_single_wwl_rm(){
// 	alert('Продукт был добавлен в избраное!');
// 	document.querySelector('[data-wm-wwl-single="add"]').setAttribute('data-wm-wwl-single', 'remove');
// 	document.querySelector('.shop-icons .likes .number').innerText = document.querySelector('.shop-icons .likes .number').innerText * 1 + 1;
// }

// document.querySelector('body').addEventListener('ev_single_wwl_add', fn_single_wwl_add);

// function fn_single_wwl_add(){
// 	alert('Продукт был удален из избранного!');
// 	document.querySelector('[data-wm-wwl-single="remove"]').setAttribute('data-wm-wwl-single', 'add');
// 	document.querySelector('.shop-icons .likes .number').innerText = document.querySelector('.shop-icons .likes .number').innerText * 1 - 1;
// }

function wish_controller(e){
	let id = e.target.getAttribute('data-item-id');
	// try{
	// 	var id = e.target.closest('.catalog-item.hi-1').getAttribute('data-wm-prod-id');
	// } catch (er){
	// 	var id = e.target.closest('.hit-item.hi-1').getAttribute('data-wm-prod-id');
	// }
	if (e.target.getAttribute('data-wm-wwl') == 'add') {
		add_to_wish(id, e.target.getAttribute('data-event-after') + '_added');
	} else {
		remove_from_wish(id, e.target.getAttribute('data-event-after') + '_removed');
	}
}

// function single_controller(e){
// 	var id = e.target.closest('[data-wm-prod-id]').getAttribute('data-wm-prod-id');
// 	if (e.target.getAttribute('data-wm-wwl-single') == 'add') {
// 		add_to_wish(id, 'single_wwl_rm');
// 	} else {
// 		remove_from_wish(id, 'single_wwl_add');
// 	}
// }

// function wish_list_remove(e, type){
// 	var id = e.target.closest('[data-wm-prod-id]').getAttribute('data-wm-prod-id');
// 	remove_from_wish(id, type);
// }

function add_to_wish(id, type = false){
	var xhttp = new XMLHttpRequest();
	xhttp.open('POST', my_ajax_url.ajax_url +"?action=add_to_wish" , true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send('id=' + id + '&type=' + type);
	xhttp.onreadystatechange = function() {
		if (xhttp.readyState == 4) {
			if (xhttp.status == 200) {
				response = JSON.parse(xhttp.response );
				if(response.success){
					last_added_product = response.last_added_product;
					document.querySelector('body').dispatchEvent( eval( response.event ) );
				} else {
					alert('Что-то пошло не так, попробуйте позже.')
				}
			} else {
				alert('Что-то пошло не так, попробуйте позже.')
			}
		}
	}
}

// function get_all_wishd(){
// 	var xhttp = new XMLHttpRequest();
// 	xhttp.open('POST', my_ajax_url.ajax_url +"?action=get_all_wishd" , true);
// 	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
// 	xhttp.send();
// 	xhttp.onreadystatechange = function() {
// 		if (xhttp.readyState == 4) {
// 			if (xhttp.status == 200) {
// 				response = JSON.parse(xhttp.response );
// 				console.log(response);
// 				if(response.success){
// 					all_prods = response;
// 				} else {
// 					alert('Что-то пошло не так, попробуйте позже.')
// 				}
// 			} else {
// 				alert('Что-то пошло не так, попробуйте позже.')
// 			}
// 		}
// 	}	
// }

function remove_from_wish(id, type = false){
	var xhttp = new XMLHttpRequest();
	xhttp.open('POST', my_ajax_url.ajax_url +"?action=remove_from_wish" , true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.send('id=' + id + '&type=' + type);
	xhttp.onreadystatechange = function() {
		if (xhttp.readyState == 4) {
			if (xhttp.status == 200) {
				response = JSON.parse(xhttp.response );
				if(response.success){
					// rm_id(response.last_removed_product);
					last_removed_product = response.last_removed_product;
					document.querySelector('body').dispatchEvent( eval( response.event ) );
				} else {
					alert('Что-то пошло не так, попробуйте позже.')
				}
			} else {
				alert('Что-то пошло не так, попробуйте позже.')
			}
		}
	}
}



document.querySelector('body').addEventListener('click', function (e){
	if (e.target.hasAttribute('data-wm-wwl')) {
		wish_controller(e);
		return;
	}
	// if (e.target.hasAttribute('data-wm-wwl-compared-list')) {
	// 	wish_list_remove(e);
	// 	return;
	// }
	// if (e.target.hasAttribute('data-wm-wwl')) {
	// 	wish_controller(e);
	// 	return;
	// }
	// if (e.target.hasAttribute('data-wm-wwl-single')) {
	// 	single_controller(e);
	// 	return;
	// }

});




function getCookie(name) {
  var matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options) {
  options = options || {};

  var expires = options.expires;

  if (typeof expires == "number" && expires) {
    var d = new Date();
    d.setTime(d.getTime() + expires * 1000);
    expires = options.expires = d;
  }
  if (expires && expires.toUTCString) {
    options.expires = expires.toUTCString();
  }

  value = encodeURIComponent(value);

  var updatedCookie = name + "=" + value;

  for (var propName in options) {
    updatedCookie += "; " + propName;
    var propValue = options[propName];
    if (propValue !== true) {
      updatedCookie += "=" + propValue;
    }
  }

  document.cookie = updatedCookie;
}