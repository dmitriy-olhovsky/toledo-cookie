jQuery(document).ready(function($){
    $('.toledo-cookie-accept').on('click', function(evt){
        evt.preventDefault();

        // Set cookie
        var tc_date = new Date(new Date().getTime() + 30*24*3600*1000); // 1 month
        document.cookie = "tc_cookie_accept=true; path=/; expires=" + tc_date.toUTCString();

        // Hide cookie info banner
        var tc_dom = $('#toledo-cookie-banner');
        tc_dom.fadeOut(300, function() {
            tc_dom.remove();
        });
    });
});