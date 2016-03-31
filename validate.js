(function($) {

    $(document).ready(function() {
        $("#mobileno").blur(function() {
            var phone = $("#mobileno").val();

            var phoneno = /^\d{10}$/;
            if (this.value.match(phoneno))
            {
                jQuery.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: ajaxurl,
                    data: {
                        'action': 'validate_phone',
                        'phone': phone,
                    },
                    success: function(data) {
                        if (data.success)
                        {
                            alert('Phone no. alredy exits');
                            $("input[type=submit]").prop("disabled", true);

                        }
                        else
                        {
                            $("input[type=submit]").prop("disabled", false);
                        }
                    },
                });
                $("input[type=submit]").prop("disabled", false);
            }
            else
            {
                $("input[type=submit]").prop("disabled", true);
                alert("Not a valid Phone Number");
            }
        })
    });

})(jQuery)
