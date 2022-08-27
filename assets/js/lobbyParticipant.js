/*
 * Welcome to your app's main JavaScript file!
 *
 */
import 'regenerator-runtime/runtime'
import $ from 'jquery';

global.$ = global.jQuery = $;
import * as mdb from 'mdb-ui-kit'; // lib
import {masterNotify, initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId, stopWebcam, toggle, webcamArr} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff, micArr} from './audioUtils'
import {initAjaxSend} from './confirmation'
import {setSnackbar} from './myToastr';
import {initGenerell} from './init';
import {socket} from './websocket';
import {initModeratorIframe,close} from './moderatorIframe'
import {askHangup} from "./jitsiUtils";
initNotofication();

initAjaxSend(confirmTitle, confirmCancel, confirmOk);
function checkClose() {
    echoOff();
    stopWebcam();
    closeBrowser();
    close()

}
initModeratorIframe(checkClose);
var api;
var dataSucess;
var successTimer;
var clickLeave = false;
var microphoneLabel = null;
var cameraLable = null;

function initMercure() {

    socket.on('mercure', function (inData) {
        var data = JSON.parse(inData)
        if (data.type === 'newJitsi') {
            userAccepted(data);
        } else if (data.type === 'endMeeting') {
            hangup()
            $('#jitsiWindow').remove();
        } else if (data.type === 'redirect') {

        }
    })
}

window.onbeforeunload = function (e) {
    if (!clickLeave) {
        closeBrowser();
    }
    return;
};

function closeBrowser() {
    $.ajax({
        url: browserLeave,
        context: document.body
    })
    for (var i = 0; i < 500000000; i++) {
    }
}

initCircle();
var counter = 0;
var interval;
var text;

$('.renew').click(function (e) {
    e.preventDefault();
    if (counter <= 0) {
        counter = reknockingTime;
        text = $(this).text();
        $.get($(this).attr('href'), function (data) {
            interval = setInterval(function () {
                counter = counter - 1;
                $('.renew').text(text + ' (' + counter + ')');
                if (counter <= 0) {
                    $('.renew').text(text);
                    clearInterval(interval);
                }
            }, 1000);
            setSnackbar(data.message, data.color);
        })
    }
})
$('.leave').click(function (e) {
    e.preventDefault();
    clickLeave = true;
    $.get($(this).attr('href'), function (data) {
        window.location.href = "/";
    })
})

function initJitsiMeet(data) {
    cameraLable = webcamArr[choosenId];
    microphoneLabel = micArr[micId];
    stopWebcam();
    echoOff();
    window.onbeforeunload = null;
    window.onbeforeunload = function (e) {
        return '';
    }
    $('body').prepend('<div id="frame"></div>');

    var frameDIv = $('#frame');
    $('#logo_image').prop('href', '#').addClass('stick').prependTo('#jitsiWindow');
    frameDIv.prepend($(data.options.parentNode));
    frameDIv.prepend($('#tagContent').removeClass().addClass('floating-tag'))
    $('#window').remove();
    $('.imageBackground').remove();
    document.title = data.options.roomName
    frameDIv.append('<div id="snackbar" class="bg-success d-none"></div>')

    var options = data.options.options;
    options.device = choosenId;
    //here we set the logo into the jitsi iframe options

    options.parentNode = document.querySelector(data.options.parentNode);
    api = new JitsiMeetExternalAPI(data.options.domain, options);
    api.addListener('chatUpdated', function (e) {
        if (e.isOpen == true) {
            document.querySelector('#logo_image').classList.add('transparent');
        } else {
            document.querySelector('#logo_image').classList.remove('transparent');
        }

    });
    api.addListener('videoConferenceJoined', function (e) {
        if (setTileview === 1) {
            api.executeCommand('setTileView', {enabled: true});
        }
        if (setParticipantsPane === 1) {
            api.executeCommand('toggleParticipantsPane', {enabled: true});
        }
        if (avatarUrl !== '') {
            api.executeCommand('avatarUrl', avatarUrl);
        }
        api.getAvailableDevices().then(devices => {
            api.setVideoInputDevice(cameraLable);
            api.setAudioInputDevice(microphoneLabel);
            swithCameraOn(toggle);
        });
        swithCameraOn(toggle);
    });


    api.addListener('participantKickedOut', function (e) {

        $('#jitsiWindow').remove();
        masterNotify({'type': 'modal', 'content': endModal});
        setTimeout(function () {
            masterNotify({'type': 'endMeeting', 'url': '/'});
        }, popUpDuration)

    });



    $(data.options.parentNode).find('iframe').css('height', '100%');
    window.scrollTo(0, 1)

}


function hangup() {
    api.executeCommand('hangup')
}

function userAccepted(data) {
    dataSucess = data;
    $('#renewParticipant').remove();
    $('.overlay').remove();
    $('.accessAllowed').removeClass('d-none');
    counter = 10;
    $('#lobby_participant_counter').text(counter);
    $('#stopEntry').removeClass('d-none');

    interval = setInterval(function () {
        counter = counter - 1;
        $('#lobby_participant_counter').css('transition', ' opacity 0s');
        $('#lobby_participant_counter').css('opacity', '0');
        setTimeout(function () {
            $('#lobby_participant_counter').css('transition', ' opacity 0.5s');
            $('#lobby_participant_counter').css('opacity', '1');
        }, 1)
        if (counter < 0) {
            clearInterval(interval);
            initJitsiMeet(dataSucess);
        }
        $('#lobby_participant_counter').text(counter);
    }, 1000);


    $('#stopEntry').click(function (e) {
        if (interval) {
            clearInterval(interval);
            interval = null;
            text = $(this).data('alternativ')
            $('.textAllow').remove();
            $(this).text(text);
        } else {
            initJitsiMeet(dataSucess);
        }
    })
}


function swithCameraOn(videoOn) {
    if (videoOn === 1){
        var muted =
            api.isVideoMuted().then(muted => {
                console.log(muted)
                if (muted){
                    api.executeCommand('toggleVideo');
                }
            });
    }else {
        api.isVideoMuted().then(muted => {
            if (!muted){
                api.executeCommand('toggleVideo');
            }

        });
    }
}

$(document).ready(function () {
    initGenerell()
    initAUdio();
    initWebcam();
    initMercure();
    $('#webcamRow').css('height', $('.webcamArea').height());
    var ro = new ResizeObserver(entries => {
        for (let entry of entries) {
            $('#webcamRow').css('height', $('.webcamArea').height());
        }
    });

// Observe one or multiple elements
    ro.observe(document.querySelector('.webcamArea'));

})



