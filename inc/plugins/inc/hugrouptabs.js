

$( document ).ready(function() {

/*
	$('#manageGroups').click(function(){
	  var settings = "width=800,height=600,scrollbars=yes";
	  var windowName = "create/manage groups";
	  var url = 'https://isites.harvard.edu/groups';
	  tb_remove();
	  window.open(url, windowName, settings);
	}); 

	$('#tabs-3').focus(function(){
		$('#manageGroups').css('display','block');
	});

	$('#emails-input-results').html("");
     
      $("#lookup-form").submit(function() {  
        // validate and process form here  
        	$('#emails-input-results').html("");
        	var data = $('#lookup-form').serialize();
        
        	//alert(dataString);
        
        	var data = {
				action: 'hu_perm_form_lookup',
				emails: data
			};
						
			$.post(ajaxurl, data, function(response) {
				//alert('Got this from the server: ' + response);
				
				$("#lookup-form-div").css("display","none");
				$('#emails-input-results').html(response);
				
				$('#cancel-search').click(function(){
					$("#lookup-form-div").css("display","block");
					$('#emails-input-results').html("");
					tb_remove();
				});
								
				$("#search-results-div").css("display","block");
				
				$("#TB_overlay").click(function(){
				
					$("#lookup-form-div").css("display","block");
					$('#emails-input-results').html("");
				
				});
				
				tb_show("", "#TB_inline?&width=450&height=400&inlineId=emails_input_results", "");
				
			});
		return false;
      });  
      
      */
   
	//var lcheck = checkLogin();
	var grouppickerlist="";
	var grouppickerlist_top = "";
	var grouppickerlist_bot = "";
	var lGrouplist = [];
	var icGrouplist = [];
	var courseGrouplist = [];
	var generalGrouplist = [];
	
    //tests for splitting groups into tab categories
    var generaltest1 = /^IcGroup/;
    var generaltest2 = /^[0-9]+$/;
    var scaletest = /^ScaleSchool/;
    var coursetest = /^ScaleCourse/;
    var ldaptest = /^LdapGroup/;
	var passed = false;
	var commissue = false;
	var appId = $('#appId').text();

	if ((go == null) || (typeof go == "undefined")) {
		$('#hugrouplist').html("<p style='margin-top:100px;margin-left:40px;font-size:16px;color:#777'><h2>Add Group Permissions is temporarily unavailable.</h2>We've encountered an error retrieving the list of iSites groups available to you. Please try again in a few minutes. If the problem persists, please contact your iCommons liaison.</p>");
	}
	else {

		groupobject = go.groups.sort(comparator);

		//test that we're getting valid data
		if ((generaltest1.test(groupobject[0].idType)) || scaletest.test(groupobject[0].idType) || coursetest.test(groupobject[0].idType) || ldaptest.test(groupobject[0].idType))  {
			passed = true;
		}

		if (passed == true) {

			//parse through sorted json
			for (var i=0; i < groupobject.length; i++) {
				var fields = "";
				var groupidentifier = groupobject[i].idType + ":" + groupobject[i].idValue;
				groupobject[i].description = "null";
				
				//translate single quotes/apostrophes since this string might be added to the db
				var groupname = groupobject[i].name; //.replace(/\'/g,"*apos*");
				var groupname_safe = groupobject[i].name.replace(/\'/g,"#039");
				
				//translate ampersands to 'and' as we use ampersands to split fields
				groupname_safe = groupname_safe.replace(/\&/g,"and");
				fields +=  groupidentifier;

				if ((generaltest1.test(groupobject[i].idType)) && (groupobject[i].idValue > -4) && (groupobject[i].idValue != -1)) {

					if (! generaltest2.test(groupobject[i].idValue)) {
						generalGrouplist.push("<input class='hugroupcb' type='checkbox' name='selectedgroups[]' value='" +
											fields + "'><span class='groupn'>" + groupname + "</span> <br />") ;
					}
					else {
						icGrouplist.push("<input class='hugroupcb' type='checkbox' name='selectedgroups[]' value='" +
											fields + "'><span class='groupn'>" + groupname + "</span> <br />") ;
					}
				}

				if (groupobject[i].idType == "LdapGroup") {
					lGrouplist.push("<input class='hugroupcb' type='checkbox' name='selectedgroups[]' value='" +
											fields + "'><span class='groupn'>" + groupname + "</span> <br />") ;
				}

				if (scaletest.test(groupobject[i].idType)) {
					lGrouplist.push("<input class='hugroupcb' type='checkbox' name='selectedgroups[]' value='" +
											fields + "'><span class='groupn'>" + groupname + "</span> <br />") ;
				}

				if (coursetest.test(groupobject[i].idType)) {
					var grouptype = "";
					grouptype = groupobject[i].idType.replace(/ScaleCourse/,"");
					//override fields here -- need to append grouptype to groupname
					if (grouptype == "Enroll") { grouptype += "ee"; }
						//fields = groupobject[i].idType + "&" + groupobject[i].idValue + "&" + groupname + " " + grouptype + "&" + groupidentifier + "&" + groupobject[i].description
						fields =  groupidentifier;

						courseGrouplist.push("<input type='hidden' value='" + groupname + " " + grouptype + "'><input class='hugroupcb' type='checkbox' name='selectedgroups[]' value='" + fields + "'><span class='groupn'>" + groupname + " " + grouptype + "</span> <br />") ;
					}
				}

			$('#tabs-1').html(generalGrouplist.join(""));
			$('#tabs-2').html(lGrouplist.join(""));
			$('#tabs-3').append(icGrouplist.join(""));
			$('#tabs-4').html(courseGrouplist.join(""));
			}
			else {
				commissue = true;
			}
		 }

	if (commissue) {
		$('#hugrouplist').html("<p style='margin-top:100px;margin-left:40px;font-size:16px;color:#777'><h2>Sorry!</h2>We've encounered an error retrieving the list of iSites groups available to you. Please try again in a few minutes. If the problem persists, please contact the administrators.</p>");
	}

	$(function() {
		$( "#hu-groups-tabs" ).tabs();
	});

});

function comparator (a,b) {
        return ( (a.name.toLowerCase() == b.name.toLowerCase()) ? 0: ((a.name.toLowerCase() > b.name.toLowerCase()) ? 1 : -1));
}
