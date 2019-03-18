define([
    'jquery',
], function ($) {
    'use strict';

    $.widget('boxalinoOverlay.js', {
        _create: function() {
            var basketTotal = this.options['basketTotal'];
            var parameters = this.options['parameters'];
            var controllerUrl = this.options['controllerUrl'];
            var lang = this.options['lang'];

            function timerIncrementIdle(){
                idleTime++;
                if (idleTime > overlayIdleTimeout) {
                    showOverlay();
                    idleTime = 0;
                }

                //Zero the idle timer on mouse movement.
                document.addEventListener('mousemove', function (e) {
                    idleTime = 0;
                });
                document.addEventListener('keypress', function (e) {
                    idleTime = 0;
                });
            }

            function showOverlay() {
                if (overlayMaxFrequency !== 0 && frequency < overlayMaxFrequency) {
                    if (parameters['minCart'] < basketTotal && basketTotal < parameters['maxCart']) {
                        $(".bxOverlay").fadeIn("slow");
                        $(".bxOverlay-content").addClass("bxOverlay-display");

                        compileSimpleOverlay();

                        // Increase the frequency counter when the the overlay is displayed
                        frequency++;
                        callController();
                        clearInterval(refreshIntervalId);
                    }
                }
            }

            //Add function to plugin
            function compileSimpleOverlay(){
                if (basketTotal > parameters['altTextSwitch']) {
                    overlayText = parameters['overlayTextAlt'][lang];
                }
                $('#bxOverlayUrl').attr('href', overlayUrl);
                $('#bxOverlayBackgroundImage').attr('src', overlayBackground);
                $('#bxOverlayTitleImage').attr('src', overlayTitle);
                $('#bxOverlayText').text(overlayText);
                $('#bxOverlayButton').attr('src', overlayButton);
            }

            function callController(){
                var paramString = [];
                var params = ""; //add extra parameters if you want (comma seperated)
                if (overlayExtraParams != 0) {
                    paramString = overlayExtraParams.split(',').join('&');
                }

                if (params != "") {
                    params = params.split(',');
                    params.forEach(function(param){
                        paramString = paramString + "&" + param;
                    });
                }

                var controllerurlwithparams = controllerUrl + "?" + paramString;
                $.ajax({
                    url: controllerurlwithparams
                });
            }

            try {
                var trigger = parameters['trigger'];
                var overlayMaxFrequency = parameters['overlayMaxFrequency'];
                var overlayExtraParams = parameters['callControllerParams'];
                var overlayTimeout = parameters['overlayTimeout'];
                var overlayExitIntendTimeout = parameters['overlayExitIntendTimeout'];
                var overlayIdleTimeout = parameters['overlayIdleTimeout'];
                var overlayUrl = parameters['overlayUrl'][lang];
                var overlayBackground = parameters['overlayBackground'];
                var overlayTitle = parameters['overlayTitle'][lang];
                var overlayText = parameters['overlayText'][lang];
                var overlayButton = parameters['overlayButton'][lang];
                var basketValue = null;
                var frequency = 0;

                if (overlayText.includes('BXBASKETVALUE')){
                    basketValue = eval(99 - basketTotal);
                    basketValue = basketValue .toFixed(2);
                    overlayText = overlayText.replace('BXBASKETVALUE', basketValue);
                }

                var triggers = trigger.split("-");
                for(var i=0; i<triggers.length; i++) {
                    switch(triggers[i]) {
                        case 'wait':
                            // show overlay after set timeout
                            $(document).ready(function() {
                                window.setTimeout(function(){
                                    showOverlay();
                                }, overlayTimeout);
                            });
                            break;
                        case 'idle':
                            var idleTime = 0;
                            var refreshIntervalId = 0;

                            refreshIntervalId = setInterval(timerIncrementIdle, 1000); // 1 second
                            $('button[id^="bxOverlayExitButton"]').on('click', function(){
                                clearInterval(refreshIntervalId);
                                refreshIntervalId = setInterval(timerIncrementIdle, 1000); // 1 second
                            });
                            break;
                        case 'addtobasket':
                            $(document).ajaxComplete(function (event, xhr, settings) {
                                if (settings.url.indexOf("customer/section/load/?sections=cart") > 0) {
                                    cartObj = xhr.responseJSON;
                                    if (basketTotal <= 150) {
                                        basketValue = eval(150 - (basketTotal + cartObj.cart.subtotal));
                                        overlayText = overlayText.replace('BXBASKETVALUE', basketValue);
                                        showOverlay();
                                    }
                                }
                            });
                            break;
                        case 'exit':
                            var idleTime = 0;
                            var frequency = 0;
                            var mouseLocation = 'in';
                            var refreshIntervalId = 0;
                            // Start action when the mouse leaves the window
                            document.addEventListener('mouseleave', function(){
                                // set state of mouse to out
                                mouseLocation = 'out';
                                // Increase the idle time counter every second.
                                refreshIntervalId = setInterval(function() {
                                    idleTime++;
                                    // check if the idleTime is below the defined time
                                    if (idleTime > overlayExitIntendTimeout && mouseLocation == 'out') {
                                        //idleTime before showOverlay
                                        clearInterval(refreshIntervalId);
                                        // shows overlay
                                        basketValue = eval(parameters['altTextSwitch'] - basketTotal);
                                        overlayText = overlayText.replace('BXBASKETVALUE', basketValue);
                                        showOverlay();
                                    }
                                }, 1000); // 1 second

                            });

                            // When Mouse enters window, reset the counter and set state of mouse on 'in'
                            document.addEventListener('mouseenter', function(){
                                idleTime = 0;
                                mouseLocation = 'in';
                                clearInterval(refreshIntervalId);
                            });
                            break;
                        default:
                        // no visualisation
                    }
                }

                // hides the overlay when the exit button or outside of the overlay is clicked
                $('#bxOverlayExitButton').on('click', function(){
                    $('.bxOverlay').hide();
                    $('.bxOverlay-content').removeClass("bxOverlay-display");
                });
                $('#bxOverlayShadow').on('click', function(){
                    $('.bxOverlay').hide();
                    $('.bxOverlay-content').removeClass("bxOverlay-display");
                });
            } catch (e) {
                console.log(e.message);
            }
        }
    });

    return $.boxalinoOverlay.js;
});