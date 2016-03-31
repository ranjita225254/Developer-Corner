<?php get_header() ?>
<br/><br/>
<div>
    <h1 style="text-align: center">Submit Form Using Ajax and save values in Database</h1>
    <form method="POST" id="form-settings" action="" >  

        <label for="name">Name:</label><br/>
        <input name="name" type="text" id="name"  /><br/>

        <label for="email">Email:</label><br/>
        <input name="email" type="text" id="email" /><br/>

        <label for="phone">Phone:</label><br/>
        <input name="phone" type="text" id="phone"/><br/>

        <label for="address">Address:</label><br/>
        <input name="address" type="text" id="address" /><br/>
        <label for="captcha">Captcha:</label><br/>
            <img src="<?php echo site_url('captcha'); ?>"/><input type="text" name="captcha_code">
        <br/>
        <button id="submitme" name="save-form">Save</button>

</div>
<br/><br/>
<div id="feedback"></div>
</center>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript">
    jQuery(document).on('click', '#submitme', function(event) {
        event.preventDefault();
//        var formData = $('#form-settings').serializeArray();
        var name = $('#name').val();
        var email = $('#email').val();
        var phone = $('#phone').val();
        var address = $('#address').val();
        jQuery.ajax({
            type: "POST",
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: {
                'action': 'ajax_form',
                'name': name,
                'email': email,
                'phone': phone,
                'address': address
            },
            success: function(data) {
                jQuery("#feedback").html(data);
            },
            error: function() {
                alert('error')
            },
        });
    });
</script>
