var $m = jQuery.noConflict();
$m(document).ready(function() {
    $m("#phone").intlTelInput();
    $m("#query_phone").intlTelInput();
    $m("#additional_phone").intlTelInput();
    $m(".navbar a").click(function() {
        $id = $m(this).parent().attr('id');
        setactive($id);
        $href = $m(this).data('method');
        voiddisplay($href);
    });
    $m(".btn-link").click(function() {
        $m(".collapse").slideUp("slow");
        if (!$m(this).next("div").is(':visible')) {
            $m(this).next("div").slideDown("slow");
        }
    });
	$m(".premium").click(function() {
        $m("#pricing-tab > a").click();
    });
	$m("#mo_saml_add_shortcode").change(function(){
		$m("#mo_saml_add_shortcode_steps").slideToggle("slow");
	});
    $m('#error-cancel').click(function() {
        $error = "";
        $m(".error-msg").css("display", "none");
    });
    $m('#success-cancel').click(function() {
        $success = "";
        $m(".success-msg").css("display", "none");
    });
    $m('#cURL').click(function() {
        $m(".help_trouble").click();
        $m("#cURLfaq").click();
    });
	$m('#help_working_title1').click(function() {
        $m("#help_working_desc1").slideToggle("fast");
    });
	$m('#help_working_title2').click(function() {
        $m("#help_working_desc2").slideToggle("fast");
    });
	
});

function setactive($id) {
    $m(".navbar-tabs>li").removeClass("active");
    $id = '#' + $id;
    $m($id).addClass("active");
}

function voiddisplay($href) {
    $m(".page").css("display", "none");
    $m($href).css("display", "block");
}

function mosp_valid(f) {
    !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
}

function showTestWindow() {
	var myWindow = window.open(testURL, "TEST SAML IDP", "scrollbars=1 width=800, height=600");	
}