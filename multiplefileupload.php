<!DOCTYPE html>
<html>
    <head>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script type="text/javascript">
            var abc = 0; //Declaring and defining global increement variable
            $(document).ready(function () {

//To add new input file field dynamically, on click of "Add More Files" button below function will be executed
                $('#add_more').click(function () {
                    $(this).before($("<div/>", {id: 'filediv'}).fadeIn('slow').append(
                            $("<input/>", {name: 'file[]', type: 'file', id: 'file'}),
                            $("<br/><br/>")
                            ));
                });

//following function will executes on change event of file input to select different file	
                $('body').on('change', '#file', function () {
                    if (this.files && this.files[0]) {
                        abc += 1; //increementing global variable by 1

                        var z = abc - 1;
                        var x = $(this).parent().find('#previewimg' + z).remove();
                        $(this).before("<div id='abcd" + abc + "' class='abcd'><img id='previewimg" + abc + "' src=''/></div>");

                        var reader = new FileReader();
                        reader.onload = imageIsLoaded;
                        reader.readAsDataURL(this.files[0]);

                        $(this).hide();
                        $("#abcd" + abc).append($("<img/>", {id: 'img', src: 'x.png', alt: 'delete'}).click(function () {
                            $(this).parent().parent().remove();
                        }));
                    }
                });

//To preview image     
                function imageIsLoaded(e) {
                    $('#previewimg' + abc).attr('src', e.target.result);
                }
                ;

                $('#upload').click(function (e) {
                    var name = $(":file").val();
                    if (!name)
                    {
                        alert("First Image Must Be Selected");
                        e.preventDefault();
                    }
                });
            });
        </script>
    <body>
        <div id="maindiv">
            <div id="formdiv">
                <h2>Multiple Image Upload Form</h2>
                <form enctype="multipart/form-data" action="" method="post">
                    <div id="filediv"><input name="file[]" type="file" id="file"/></div><br/>
                    <input type="button" id="add_more" class="upload" value="Add More Files"/>
                    <input type="submit" value="Upload File" name="submit" id="upload" class="upload"/>
                </form>
                <br/>
                <br/>
                <!-------Including PHP Script here------>
                <?php
                if (isset($_POST['submit'])) {
                    $j = 0; //Variable for indexing uploaded image 

                    $target_path = "uploads/"; //Declaring Path for uploaded images
                    for ($i = 0; $i < count($_FILES['file']['name']); $i++) {//loop to get individual element from the array
                        $validextensions = array("jpeg", "jpg", "png");  //Extensions which are allowed
                        $ext = explode('.', basename($_FILES['file']['name'][$i])); //explode file name from dot(.) 
                        $file_extension = end($ext); //store extensions in the variable

                        $target_path = $target_path . md5(uniqid()) . "." . $ext[count($ext) - 1]; //set the target path with a new name of image
                        $j = $j + 1; //increment the number of uploaded images according to the files in array       

                        if (($_FILES["file"]["size"][$i] < 100000) //Approx. 100kb files can be uploaded.
                                && in_array($file_extension, $validextensions)) {
                            if (move_uploaded_file($_FILES['file']['tmp_name'][$i], $target_path)) {//if file moved to uploads folder
                                echo $j . ').<span id="noerror">Image uploaded successfully!.</span><br/><br/>';
                            } else {//if file was not moved.
                                echo $j . ').<span id="error">please try again!.</span><br/><br/>';
                            }
                        } else {//if file size and file type was incorrect.
                            echo $j . ').<span id="error">***Invalid file Size or Type***</span><br/><br/>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </body>
</html>
