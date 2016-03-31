<!DOCTYPE html>
<html lang="en">
    <head>
        <style type="text/css">
            body{
                background: #68b8c4;
            }

            body, button{
                font-family: 'Helvetica Neue', arial, sans-serif;
                color: white;
                -webkit-font-smoothing: antialiased;
            }

            /**
             * Form & Checkbox Styles
             */

            h4{
                font-weight: 700;
                margin-bottom: .5em;
            }

            label{
                font-weight: 300;
            }

            button{
                display: inline-block;
                vertical-align: top;
                padding: .4em .8em;
                margin: 0;
                background: #68b8c4;
                border: 0;
                color: #333;
                font-size: 16px;
                font-weight: 700;
                border-radius: 4px;
                cursor: pointer;
            }

            button:focus{
                outline: 0 none;
            }

            .controls{
                background: #333;
                padding: 2%;
            }

            fieldset{
                display: inline-block;
                vertical-align: top;
                margin: 0 1em 0 0;
                background: #666;
                padding: .5em;
                border-radius: 3px;
            }

            .checkbox{
                display: block;
                position: relative;
                cursor: pointer;
                margin-bottom: 8px;
            }

            .checkbox input[type="checkbox"]{
                position: absolute;
                display: block;
                top: 0;
                left: 0;
                height: 100%;
                width: 100%;
                cursor: pointer;
                margin: 0;
                opacity: 0;
                z-index: 1;
            }

            .checkbox label{
                display: inline-block;
                vertical-align: top;
                text-align: left;
                padding-left: 1.5em;
            }

            .checkbox label:before,
            .checkbox label:after{
                content: '';
                display: block;
                position: absolute;
            }

            .checkbox label:before{
                left: 0;
                top: 0;
                width: 18px;
                height: 18px;
                margin-right: 10px;
                background: #ddd;
                border-radius: 3px;
            }

            .checkbox label:after{
                content: '';
                position: absolute;
                top: 4px;
                left: 4px;
                width: 10px;
                height: 10px;
                border-radius: 2px;
                background: #68b8c4;
                opacity: 0;
                pointer-events: none;
            }

            .checkbox input:checked ~ label:after{
                opacity: 1;
            }

            .checkbox input:focus ~ label:before{
                background: #eee;
            }

            /**
             * Container/Target Styles
             */

            .container{
                padding: 2%;
                min-height: 400px;
                text-align: justify;
                position: relative;
            }

            .container .mix,
            .container .gap{
                width: 100px;
                display: inline-block;
                margin: 0 5%;
            }

            .container .mix{
                width: 100px;
                height: 100px;
                margin: 5%;
                background: white;
                display: none;
            }

            .container .mix.green{
                background: #a6e6a7;
            }

            .container .mix.blue{
                background: #6bd2e8;
            }

            .container .mix.circle{
                border-radius: 999px;
            }

            .container .mix.triangle{
                width: 0;
                height: 0;
                border: 50px solid transparent;
                border-top-color: #68b8c4;
                border-left-color: #68b8c4;
            }

            .container .mix.sm{
                width: 50px;
                height: 50px;
            }

            /**
             * Fail message styles
             */

            .container .fail-message{
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                text-align: center;
                opacity: 0;
                pointer-events: none;

                -webkit-transition: 150ms;
                -moz-transition: 150ms;
                transition: 150ms;
            }

            .container .fail-message:before{
                content: '';
                display: inline-block;
                vertical-align: middle;
                height: 100%;
            }
            .container .fail-message span{
                display: inline-block;
                vertical-align: middle;
                font-size: 20px;
                font-weight: 700;
            }

            .container.fail .fail-message{
                opacity: 1;
                pointer-events: auto;
            }

        </style>
        <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.12.0.min.js"></script>  
        <script src="jquery.mixitup.js">
            
        </script>
        <script type="text/javascript">
            // To keep our code clean and modular, all custom functionality will be contained inside a single object literal called "checkboxFilter".

            var checkboxFilter = {
                // Declare any variables we will need as properties of the object

                $filters: null,
                $reset: null,
                groups: [],
                outputArray: [],
                outputString: '',
                // The "init" method will run on document ready and cache any jQuery objects we will need.

                init: function () {
                    var self = this; // As a best practice, in each method we will asign "this" to the variable "self" so that it remains scope-agnostic. We will use it to refer to the parent "checkboxFilter" object so that we can share methods and properties between all parts of the object.

                    self.$filters = $('#Filters');
                    self.$reset = $('#Reset');
                    self.$container = $('#Container');

                    self.$filters.find('fieldset').each(function () {
                        self.groups.push({
                            $inputs: $(this).find('input'),
                            active: [],
                            tracker: false
                        });
                    });

                    self.bindHandlers();
                },
                // The "bindHandlers" method will listen for whenever a form value changes. 

                bindHandlers: function () {
                    var self = this;

                    self.$filters.on('change', function () {
                        self.parseFilters();
                    });

                    self.$reset.on('click', function (e) {
                        e.preventDefault();
                        self.$filters[0].reset();
                        self.parseFilters();
                    });
                },
                // The parseFilters method checks which filters are active in each group:

                parseFilters: function () {
                    var self = this;

                    // loop through each filter group and add active filters to arrays

                    for (var i = 0, group; group = self.groups[i]; i++) {
                        group.active = []; // reset arrays
                        group.$inputs.each(function () {
                            $(this).is(':checked') && group.active.push(this.value);
                        });
                        group.active.length && (group.tracker = 0);
                    }

                    self.concatenate();
                },
                // The "concatenate" method will crawl through each group, concatenating filters as desired:

                concatenate: function () {
                    var self = this,
                            cache = '',
                            crawled = false,
                            checkTrackers = function () {
                                var done = 0;

                                for (var i = 0, group; group = self.groups[i]; i++) {
                                    (group.tracker === false) && done++;
                                }

                                return (done < self.groups.length);
                            },
                            crawl = function () {
                                for (var i = 0, group; group = self.groups[i]; i++) {
                                    group.active[group.tracker] && (cache += group.active[group.tracker]);

                                    if (i === self.groups.length - 1) {
                                        self.outputArray.push(cache);
                                        cache = '';
                                        updateTrackers();
                                    }
                                }
                            },
                            updateTrackers = function () {
                                for (var i = self.groups.length - 1; i > -1; i--) {
                                    var group = self.groups[i];

                                    if (group.active[group.tracker + 1]) {
                                        group.tracker++;
                                        break;
                                    } else if (i > 0) {
                                        group.tracker && (group.tracker = 0);
                                    } else {
                                        crawled = true;
                                    }
                                }
                            };

                    self.outputArray = []; // reset output array

                    do {
                        crawl();
                    }
                    while (!crawled && checkTrackers());

                    self.outputString = self.outputArray.join();

                    // If the output string is empty, show all rather than none:

                    !self.outputString.length && (self.outputString = 'all');

                    //console.log(self.outputString); 

                    // ^ we can check the console here to take a look at the filter string that is produced

                    // Send the output string to MixItUp via the 'filter' method:

                    if (self.$container.mixItUp('isLoaded')) {
                        self.$container.mixItUp('filter', self.outputString);
                    }
                }
            };

// On document ready, initialise our code.

            $(function () {

                // Initialize checkboxFilter code

                checkboxFilter.init();

                // Instantiate MixItUp

                $('#Container').mixItUp({
                    controls: {
                        enable: false // we won't be needing these
                    },
                    animation: {
                        easing: 'cubic-bezier(0.86, 0, 0.07, 1)',
                        duration: 600
                    }
                });
            });
        </script>
    </head>
    <body>

        <form class="controls" id="Filters">
            <!-- We can add an unlimited number of "filter groups" using the following format: -->

            <fieldset>
                <h4>Shapes</h4>
                <div class="checkbox">
                    <input type="checkbox" value=".square"/>
                    <label>Square</label>
                </div>
                <div class="checkbox">
                    <input type="checkbox" value=".circle"/>
                    <label>Circle</label>
                </div>
                <div class="checkbox">
                    <input type="checkbox" value=".triangle"/>
                    <label>Triangle</label>
                </div>
            </fieldset>

            <fieldset>
                <h4>Colours</h4>
                <div class="checkbox">
                    <input type="checkbox" value=".white"/>
                    <label>White</label>
                </div>
                <div class="checkbox">
                    <input type="checkbox" value=".green"/>
                    <label>Green</label>
                </div>
                <div class="checkbox">
                    <input type="checkbox" value=".blue"/>
                    <label>Blue</label>
                </div>
            </fieldset>

            <button id="Reset">Clear Filters</button>
        </form>

        <div id="Container" class="container">
            <div class="fail-message"><span>No items were found matching the selected filters</span></div>

            <div class="mix triangle white"></div>
            <div class="mix square white "></div>
            <div class="mix circle green "></div>
            <div class="mix triangle blue"></div>
            <div class="mix square white "></div>
            <div class="mix circle blue "></div>
            <div class="mix triangle green"></div>
            <div class="mix square blue"></div>
            <div class="mix circle white"></div>

            <div class="gap"></div>
            <div class="gap"></div>
            <div class="gap"></div>
            <div class="gap"></div>
        </div>

    </body>
</html>
