<html lang="en">
    <head>
        <link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-te/1.4.0/jquery-te.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js" type="text/javascript"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Base64/1.0.0/base64.min.js" type="text/javascript"></script>
        <script src="https://cdn.jsdelivr.net/canvas2image/0.1/canvas2image.min.js" type="text/javascript"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-te/1.4.0/jquery-te.min.js" charset="utf-8"></script>
        <style>
            @import url('https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i');
            @import url('https://fonts.googleapis.com/css?family=Raleway:400,400i,500,500i,600,600i,700,700i');
            body{ font-family: "Roboto",sans-serif;
                  margin: 0;
                  padding: 0 30px;
            }
            .dib{display: inline-block;}
            .left{float: left;}
            .right{float: right;}
            .clear:after{display: block;clear: both;content: "";}
            .card-wrapper{
                background: rgba(0, 0, 0, 0) url("bg.png") no-repeat scroll 0 0 / 100% 100% ;
                border: 1px solid rgb(221, 221, 221);
                border-radius: 10px;
                box-shadow: 0 0 8px rgb(221, 221, 221);
                box-sizing: border-box;
                height: 220px;
                margin: 0 auto;
                position: relative;                    
                padding: 15px;
                width: 400px;
                color:#fff;
            }
            .editable_logo img {
                position: absolute;
                right:15px;
                bottom: 15px;
                height: auto;
                max-width: 100px;
            }
            .editable_title {
                font-size: 24px;
                font-family: 'Raleway', sans-serif;
                font-weight: 600;
            }
            .editable_designation{font-style: italic;}
            .info{position: absolute;left:15px;bottom: 15px;font-weight: 300;font-size: 14px;}
            .tabs {
                background-color: rgb(241, 241, 241);
                border-radius: 2px;
                margin-bottom: 25px;
                padding: 15px 30px;
            }
            #get_file {
                bottom: 15px;
                padding: 15px;
                position: absolute;
                right: 15px;
            }
            #btnSave {
                background-color: rgb(35, 143, 45);
                border: 1px solid rgb(35, 143, 45);
                border-radius: 2px;
                color: rgb(255, 255, 255);
                cursor: pointer;
                padding: 5px 10px;
                text-transform: uppercase;
            }
            .jqte{
                z-index:99999 !important;
                position: relative;
            }
        </style>
    </head>
    <body>
        <div class="site-header">
            <h2>
                Visiting Card
            </h2>
            <div class="tabs clear">
                <div class="save-card dib right">                   
                    <input type="button" id="btnSave" value="Save PNG" class=""/>
                </div>
            </div>
        </div>
        <input type="file" multiple id="my_file" style="display: none;" onchange="loadFile(event)">
        <div class="widget" id="widget">
            <div class="card-wrapper">
                <div id="get_file" class="dib">Your Logo</div>
                <div class="editable_logo"><img id="output"/></div>
                <div class="editable_title">Your Title</div>
                <div class="editable_designation">Your Desgination</div>
                <div class="info">
                    <div class="editable_weblink">www.testweb.com</div>
                    <div class="editable_email">admin@testweb.com</div>
                    <div class="editable_phone">P: 9898989898</div>
                    <div class="editable_telephone">F: 9797979797</div>
                </div>
            </div>
        </div>
        <div id="img-out"></div>
        <script type="text/javascript">
            $(function () {
                $("#btnSave").click(function () {
                    html2canvas($(".card-wrapper"), {
                        allowTaint: true,
                        taintTest: false,
                        "logging": true,
                        onrendered: function (canvas) {
                            var url = canvas.toDataURL("image/png");
                            window.open(url, "_blank");
                            Canvas2Image.saveAsPNG(canvas);
                        }
                    });
                });
            });
            $(document).ready(function () {
                var class_names = "";
                var field_text = "";
                $(document).on("click", ".editable_title, .editable_designation, .editable_weblink, .editable_email, .editable_phone, .editable_telephone ", function () {
                    if ($(".jqte").length == 0) {
                        class_names = $(this).attr("class");
                        $(this).jqte(
                                {
                                    blur: function () {
                                        field_text = $('.jqte_editor').html()
                                        $("<div class='" + class_names + "'>" + field_text + "</div>").insertAfter(".jqte");
                                        $('.jqte').remove();
                                    }
                                }
                        );
                    }
                });
                $('body').click(function (e) {
                    if (!$(e.target).closest('.jqte').length) {
                        field_text = $('.jqte_editor').html();
                        $("<div class='" + class_names + "'>" + field_text + "</div>").insertAfter(".jqte");
                        $('.jqte').remove();
                    }
                });
            });


            document.getElementById('get_file').onclick = function () {
                document.getElementById('my_file').click();
            };
            var loadFile = function (event) {
                var output = document.getElementById('output');
                output.src = URL.createObjectURL(event.target.files[0]);
                $("#get_file").hide();
            };

            $(document).on("click", "#output", function () {
                $("#get_file").trigger("click");
            });
        </script>
    </body>
</html>
