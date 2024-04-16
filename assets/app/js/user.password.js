
/**
* user.password.js
* Author: JCm
*/

$(document).ready(function(){
	
	var sDomain       = document.domain;
    var sSiteBasePath = '/tablet/index.php/';
    if(sDomain === 'localhost' || sDomain === '127.0.0.1'){
		sSiteBasePath = '/tablet/index.php/';
    }
    if(document.location !== undefined && location.port){
        sDomain = document.domain + ':' + location.port
    }
    var sUrl = mysite +'user_password/';
	
	//Send asyncronous request to the server, vanilla ajax support
    //-------------------------------------
	var ajax = {};
	ajax.x = function() {
		if (typeof XMLHttpRequest !== 'undefined') {
			return new XMLHttpRequest();  
		}
		var versions = [
			"MSXML2.XmlHttp.6.0",
			"MSXML2.XmlHttp.5.0",   
			"MSXML2.XmlHttp.4.0",  
			"MSXML2.XmlHttp.3.0",   
			"MSXML2.XmlHttp.2.0",  
			"Microsoft.XmlHttp"
		];

		var xhr;
		for(var i = 0; i < versions.length; i++) {
			try {  
				xhr = new ActiveXObject(versions[i]);  
				break;  
			} catch (e) {
			}  
		}
		return xhr;
	};

	ajax.send = function(url, callback, method, data, sync) {
		var x = ajax.x();
		x.open(method, url, sync);
		x.onreadystatechange = function() {
			if (x.readyState == 4) {
				callback(jQuery.parseJSON(x.responseText), x.status, x);
			}
		};
		if (method == 'POST') {
			x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		}
		x.send(data)
	};

	ajax.get = function(url, data, callback, sync) {
		var query = [];
		for (var key in data) {
			query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
		}
		ajax.send(url + (query.length ? '?' + query.join('&') : ''), callback, 'GET', null, sync)
	};

	ajax.post = function(url, data, callback, sync) {
		var query = [];
		for (var key in data) {
			query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
		}
		ajax.send(url, callback, 'POST', query.join('&'), sync)
	};
	
	//Send asyncronous request to the server
    //-------------------------------------
    var ajaxRequest = function(type, url, pData, successCallback, completeCallback, errorCallback, pAsync){
        
        pData = pData || null;                       //data to be transmitted asyncronously
        successCallback  = successCallback || null;  //call back function when ajax request completed
        completeCallback = completeCallback || null; //call back function when ajax request completed
        errorCallback = errorCallback || ajaxOnError;
		if(typeof pAsync === 'undefined'){
			pAsync = true;
		}
        
        $.ajax({
            type: type,
            data: pData,
            url: url,
			async: pAsync,
            success: function(data, status, xhr){
                if(status){
                    if(successCallback){
                        successCallback(data, status, xhr);
                    }
                }else{
                    //Show XHR response text
					alert(status);
                }
            },
            error: function(xhr, status, errStr){
				if(errorCallback){
					errorCallback(xhr, status, errStr);
				}
            },
            complete: function(xhr, status){
                //completeCallback(xhr, status);
                if(completeCallback){
                    completeCallback(xhr, status);
                }
            },
            cache: false,
            contentType: false,
            processData: false
        });
    };
	
	//--------------------------
	ajaxRequest('GET', sUrl + 'checkResetPasswordBit', null,
		function(data, status, xhr){
			if(data){
				//user change password form modal
				bootbox.dialog({
					message: '<p>Your System Administrator is requiring you to change your password. Please enter a NEW password in the fields below.'+
							 '<br><i class="text-muted note-text">Note: We recommend combination of letters and numbers in your new password.</i></p>'+
							 '<form id="password-api" class="form-horizontal" role="form">'+
							 '<div class="form-group">'+
							 '	<label class="control-label col-sm-4" for="new-password">New password:</label>'+
							 '	<div class="col-sm-8">'+
							 '		<input type="password" class="form-control" id="new-password" name="new-password" placeholder="Enter new password">'+
							 '	</div>'+
							 '</div>'+
							 '<div class="form-group">'+
							 '	<label class="control-label col-sm-4" for="confirm-password">Confirm password:</label>'+
							 '	<div class="col-sm-8">'+
							 '		<input type="password" class="form-control" id="confirm-password" name="confirm-password" placeholder="Confirm password">'+
							 '	</div>'+
							 '</div>'+
							 '</form>'+
							 '<div class="row">'+
							 '	<div class="col-sm-offset-4 col-sm-8" id="notif"></div>'+
							 '</div>',
					title: 'Change Password',
					onEscape: function() {
						return false;
					},
					closeButton: false,
					className: "user-password-modal",
					buttons: {
						Submit:{
							label: 'Submit',
							callback: function(e){
								//e.preventDefault();
								$("#notif").html('');
								$("#notif").removeClass('text-danger text-success');
								$(e.target).html('Saving. . .');
								$(e.target).addClass('disabled');
								
								var passwordChanged = false;
								var newPassword  = $("#new-password").val();
								var confPassword = $("#confirm-password").val();
								
								if(!validateForm(newPassword, confPassword)){
									$(e.target).html('Submit');
									$(e.target).removeClass('disabled');
									//return passwordChanged;
								}else{
									
									var async = true; //false; //do not send data asynchronously
									if(typeof FormData === 'undefined'){
										var formData = {'new-password': newPassword, 'confirm-password': confPassword};
										ajax.post(sUrl + 'changePassword', formData, 
											function(data, status, xhr){
												if(status == 200){
													if(data.status){
														passwordChanged = true;
														$("#notif").addClass('text-success');
														$("#notif").html(data.message);
													}else{
														$("#notif").addClass('text-danger');
														$("#notif").html(data.message);
													}
													
													if(!passwordChanged){
														$(e.target).html('Submit');
														$(e.target).removeClass('disabled');
													}else{
														setTimeout(function(){
															$('.user-password-modal').modal('hide');
															//check if user is org1 or awacs1
															checkUser();
														}, 1000);
													}
												}else{
													
												}
												
											}
										);
									}else{
										var formData = new FormData();
										formData.append('new-password', newPassword);
										formData.append('confirm-password', confPassword);
										
										ajaxRequest('POST', sUrl + 'changePassword', formData, 
											function(data, status, xhr){
												if(data.status){
													passwordChanged = true;
													$("#notif").addClass('text-success');
													$("#notif").html(data.message);
												}else{
													$("#notif").addClass('text-danger');
													$("#notif").html(data.message);
												}
											},
											function(){
												if(!passwordChanged){
													$(e.target).html('Submit');
													$(e.target).removeClass('disabled');
												}else{
													setTimeout(function(){
														$('.user-password-modal').modal('hide');
														//check if user is org1 or awacs1
														checkUser();
													}, 1000);
												}
											},
											function(xhr, status, errStr){
												$("#notif").addClass('text-danger');
												$("#notif").html('<i class="glyphicon glyphicon-warning-sign"></i> Server error:<br>' + status + ': ' + errStr);
											},
											async
										);
									}
									
									
									
									//return passwordChanged;
								}
								return passwordChanged;
							}
						}
					}
				});
			}else{
				//check if user is org1 or awacs1
				checkUser();
			}
		},
		null,
		function(xhr, status, errStr){
			alert('User-Password Module server error.\n'+ status +': '+ errStr +'\nPlease contact JCm');
		}
	);
	
	//--------------------------
	function validatePassword(pNewPassword, pConfPassword){
		var isValid = false;
		if(pNewPassword == pConfPassword){
			isValid = true;
		}
		return isValid
	}
		
	//--------------------------
	function validateForm(newPassword, confPassword){
		var isSuccessful = false;
		if(newPassword && confPassword){
			if(validatePassword(newPassword, confPassword)){
				isSuccessful = true
			}else{
				$("#notif").addClass('text-danger');
				$("#notif").html('Password did not matched.');
			}
		}else{
			$("#notif").addClass('text-danger');
			$("#notif").html('All fields are required');
		}
		
		return isSuccessful;
	}
	
	
	/**
	*Check for users ORG1 and AWACS1
	*
	*/
	function checkUser(pChangeOrg){
		
		pChangeOrg = pChangeOrg || false;
		changeOrgURL = (pChangeOrg? sUrl + 'checkUser?change_org=1':sUrl + 'checkUser' );
		
		ajaxRequest('GET', changeOrgURL, null, 
			function(data, status, xhr){
				if(data.status){
					//populate org list
					bootbox.dialog({
						message: '<form class="form-horizontal" role="form">'+
								 '<div class="form-group">'+
								 '	<label class="control-label col-sm-2" for="new-password">Organization:</label>'+
								 '	<div class="col-sm-10">'+
								 '		<select class="form-control" id="org-list">'+ data.org_list_html +'</select>'+
								 '	</div>'+
								 '</div>'+
								 '</form>'+
								 '<div class="row">'+
								 '	<div class="col-sm-offset-2 col-sm-8" id="notif"></div>'+
								 '</div>',
						title: 'Select Organization',
						onEscape: function() {
							return true;
						},
						closeButton: true,
						className: "select-org-modal",
						buttons: {
							Submit:{
								label: 'Submit',
								callback: function(e){
									e.preventDefault();
									var orgid = $("#org-list").val();
									$("#notif").html('');
									
									if(orgid){
										$(e.target).html('Saving. . .');
										$(e.target).addClass('disabled');
										
										ajaxRequest('GET', sUrl + 'setOrgID?orgid=' + orgid, null,
											function(data, status, xhr){
												if(data.status){
													$("#notif").addClass('text-success');
													if(pChangeOrg){
														//redirect page to schedule
														$("#notif").html(data.message + ' Please wait, page will redirect to main page.');
													}else{
														$("#notif").html(data.message);
													}
                          $('.select-org-modal').modal('hide');
													setTimeout(function(){
														window.location = mysite+'schedule';
													}, 1000);
												}else{
													$("#notif").addClass('text-danger');
													$("#notif").html(data.message);
												}
											},
											function(){
												$(e.target).html('Submit');
												$(e.target).removeClass('disabled');	
												$("#notif").removeClass('text-danger text-success');											
											},
											function(xhr, status, errStr){
												//error
												$("#notif").addClass('text-danger');
												alert('User-Password Module server error.\n'+ status +': '+ errStr +'\nPlease contact JCm');
											}
										);
									}else{
										$("#notif").addClass('text-danger');
										$("#notif").html('Please select and Organization.');
									}
									
									return false;
								}
							}
						}
					});
				}else{
					console.log(data.message);
				}
			}, 
			null, 
			function(xhr, status, errStr){
				//error
				alert('User-Password Module server error.\n'+ status +': '+ errStr +'\nPlease contact JCm');
			}, 
			true
		);
	}
	
	function changeOrg(){
		var cleared = false;
		ajaxRequest('GET', sUrl + 'changeOrg', null, 
			function(data, status, xhr){
				if(data.status){
					checkUser();
				}else{
					//error
					alert('Oops something is wrong with the session variables.' +'\nPlease contact JCm');
				}
			},
			null,
			function(xhr, status, errStr){
				//error
				alert('User-Password Module server error.\n'+ status +': '+ errStr +'\nPlease contact JCm');
			}, 
			false
		);
	}
	
	$("#change-org").click(function(){
		//changeOrg();
		checkUser(true);
	});
	
});