 $('#registration').submit(function (e)
            {
                var pass_length = $('#pass_match').val();
                if (pass_length.length < 8)
                {
                    alert('Password must contain atleast 8 characters');
                    e.preventDefault();
                }
            });
