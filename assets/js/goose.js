/* ------------------------------------------------------------------------------------
*  Goosle - The fast, privacy oriented search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2024 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any 
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */

/*--------------------------------------
 Share magnet results to the clipboard
--------------------------------------*/
function clipboard(id) {
	// Get the text field element and set up a response
	var share_string = document.getElementById(id);
	var success, message;
	
	// Select the text field
	share_string.select();
	share_string.setSelectionRange(0, share_string.value.length);
	
	// Copy the text inside the text field to the clipboard
	success = navigator.clipboard.writeText(share_string.value);
	
	// Visual response
	if(success) {
		message = "<span class=\"success green\">Link copied to the clipboard.</span><br />Paste the link anywhere you want with ctrl+v (or cmd+v on macOS).<br />Or use the paste function in your app."
	} else {
		message = "<span class=\"fail red\">Copying is not supported or got blocked.</span><br />Copy the link by pressing ctrl+c (or cmd+c on macOS).<br />Or use the select and copy functions from the context menu."
	}
	response = document.getElementById(id + '-response');
	response.innerHTML = message;

	// Set up a timer to remove the visual response after a few seconds
	setTimeout(function() {
		response.innerHTML = "";
	}, 10000);
}

/*--------------------------------------
 Handle magnet share, highlight and box office popups
--------------------------------------*/
function openpopup(id) {
    document.getElementById(id).classList.add('open');
    document.body.classList.add('goosebox-open');
}

function closepopup() {
    document.querySelector('.goosebox.open').classList.remove('open');
    document.body.classList.remove('goosebox-open');
}

// close modals on background click
window.addEventListener('load', function() {
    document.addEventListener('click', event => {
        if (event.target.classList.contains('goosebox')) {
            closepopup();
        }
    });
});