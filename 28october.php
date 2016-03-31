(function($) {
    $(document).ready(function() {
        $("form").submit(function(e) {
            var phoneno = /^\d{10}$/;
            var mob = $("#mobileno").val();
            if (mob.match(phoneno))
            {
                $("input[type=submit]").prop("disabled", false);
                $("#your-profile").submit();
            }
            else
            {
                e.preventDefault();
                $("input[type=submit]").prop("disabled", true);
                alert("Not a valid Phone Number");
                $("#mobileno").blur(function() {
                    if (this.value.match(phoneno))
                    {
                        $("input[type=submit]").prop("disabled", false);
                    }
                    else
                    {
                        $("input[type=submit]").prop("disabled", true);
                        alert("Not a valid Phone Number");
                    }
                });
            }
        })
    });
})(jQuery)
