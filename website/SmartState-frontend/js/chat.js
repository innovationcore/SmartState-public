const msgerForm = get(".msger-inputarea");
const msgerInput = get(".msger-input");
const msgerChat = get(".msger-chat");

const BOT_IMG = "https://cdn-icons-png.flaticon.com/512/2021/2021646.png";
const PERSON_IMG = "/img/profile.jpg";
const BOT_NAME = "SmartState";
const PERSON_NAME = "You";
const PERSON_UUID = participantUUID;
// riva_url is defined in views/survey/index 
const RASA_RIVA_API_IP = API_url;

var curr_volume = 1.0
var curr_audio = new Audio();
var audio_queue;



window.onload = function() {
    sendRestartToRasa();
};

function sendRestartToRasa() {
    $.post({
		url: RASA_RIVA_API_IP+"/api/sendmessage",
		data: JSON.stringify({sender: PERSON_UUID, message: "/restart", audio: -1}),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data, ok, code){
			if (code["status"] != 200){
                console.error("Couldn't restart Rasa session.")
            }
		},
		error: function(errMsg) { // error occurred in js
			console.error("Couldn't restart Rasa session.");
		}
	});
}

msgerForm.addEventListener("submit", event => {
  event.preventDefault();

  const msgText = msgerInput.value;
  if (!msgText) return;

  appendMessage(PERSON_NAME, PERSON_IMG, "right", msgText, -1);
  msgerInput.value = "";

  // do ajax here
  	$.post({
		url: RASA_RIVA_API_IP+"/api/sendmessage",
		data: JSON.stringify({sender: PERSON_UUID, message: msgText, audio: -1}),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data, ok, code){
			if (code["status"] == 200){
				audio_queue = []
				$.each(data["response_msg"], function (index, item) {
					if (item["image"] != ""){
						appendMessageImg(BOT_NAME, BOT_IMG, "left", item["image"]);
					}
					else { // text
						appendMessage(BOT_NAME, BOT_IMG, "left", item["text"]);
						audio_queue.push(new Audio(item['audio']))
					}
				});
				play_all(audio_queue)
			}
			else { // error occured in API
				appendMessageError(BOT_NAME, BOT_IMG, "left", data["response_msg"]["text"]);
			}
		},
		error: function(errMsg) { // error occurred in js
			appendMessageError(BOT_NAME, BOT_IMG, "left", "An error occured. Please wait a few minutes or refresh the page.");
		}
	});
  
});

function sendAudioToAPI(audioB64) {
	// do ajax here
	$.post({
		url: RASA_RIVA_API_IP+"/api/audiototext",
		data: JSON.stringify({audio: audioB64}),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data, ok, code){
			if (code["status"] == 200){
				$('#chat-text-area').val(data['response_msg'][0]['text']);
			}
		},
		error: function(errMsg) { // error occurred in js
			appendMessageError(BOT_NAME, BOT_IMG, "left", "An error occured. Please wait a few minutes or refresh the page.");
		}
	});
}


function appendMessageImg(name, img, side, message_img) {
  //   Simple solution for small apps
  let msgHTML = `
	<div class="msg ${side}-msg">
	  <div class="msg-img" style="background-image: url(${img})"></div>

	  <div class="msg-bubble">
		<div class="msg-info">
		  <div class="msg-info-name">${name}</div>
		  <div class="msg-info-time">${formatDate(new Date())}</div>
		</div>

		<div class="msg-text">
		<img src="${message_img}"/>
		</div>
	  </div>
	</div>
  `;

  msgerChat.insertAdjacentHTML("beforeend", msgHTML);
  msgerChat.scrollTop += 500;
}

function appendMessage(name, img, side, text, audio) {
	//   Simple solution for small apps
	let msgHTML = `
	  <div class="msg ${side}-msg">
		<div class="msg-img" style="background-image: url(${img})"></div>
  
		<div class="msg-bubble">
		  <div class="msg-info">
			<div class="msg-info-name">${name}</div>
			<div class="msg-info-time">${formatDate(new Date())}</div>
		  </div>
  
		  <div class="msg-text">
		  ${text}<br>
		  </div>
		</div>
	  </div>
	`;
  
	msgerChat.insertAdjacentHTML("beforeend", msgHTML);
	msgerChat.scrollTop += 500;
  }

function appendMessageError(name, img, side, text) {
	//   Simple solution for small apps
	let msgHTML = `
		<div class="msg ${side}-msg">
		<div class="msg-img" style="background-image: url(${img})"></div>

		<div class="msg-bubble">
			<div class="msg-info">
			<div class="msg-info-name">${name}</div>
			<div class="msg-info-time">${formatDate(new Date())}</div>
			</div>

			<div class="msg-text">
			${text}
			</div>
		</div>
		</div>
	`;

	msgerChat.insertAdjacentHTML("beforeend", msgHTML);
	msgerChat.scrollTop += 500;
}


// Utils
function get(selector, root = document) {
  return root.querySelector(selector);
}

function formatDate(date) {
	var hours = date.getHours();
	var minutes = date.getMinutes();
	var seconds = date.getSeconds();
	var ampm = hours >= 12 ? 'pm' : 'am';
	hours = hours % 12;
	hours = hours ? hours : 12; // the hour '0' should be '12'
	minutes = minutes < 10 ? '0'+minutes : minutes;
	seconds = seconds < 10 ? '0'+seconds : seconds;
	var strTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
	return strTime;
}

$('#mute-sound').click(function(){
	if ($('#mute-sound').hasClass('fa-volume-mute')){
		curr_volume = 1.0
		curr_audio.volume = 1.0;
		$('#mute-sound').removeClass('fa-volume-mute');
		$('#mute-sound').addClass('fa-volume-up');
	} else {
		curr_volume = 0.0;
		curr_audio.volume = 0.0;
		$('#mute-sound').removeClass('fa-volume-up');
		$('#mute-sound').addClass('fa-volume-mute');
	}
});

/////// This queues the audio files for playback
//This plays a file, and call a callback once it completed (if a callback is set)

function play(audio, callback) {
    try {
        audio.volume = curr_volume;
        curr_audio = audio;
        audio.play();
        if (callback) {
            //When the audio object completes it's playback, call the callback
            //provided      
            audio.addEventListener('ended', callback);
        }
    } catch (e) {
		console.error(e);
        console.error("An error occured while playing the sound");
    }

}

//Changed the name to better reflect the functionality
function play_sound_queue(sounds) {
	var index = 0;
	function recursive_play() {	
		//If the index is the last of the table, play the sound
		//without running a callback after       
		if (index + 1 === sounds.length) {
			play(sounds[index], null);
		} else {
			//Else, play the sound, and when the playing is complete
			//increment index by one and play the sound in the 
			//indexth position of the array
			play(sounds[index], function() {
				index++;
				recursive_play();
			});
		}
	}

	//Call the recursive_play for the first time
	recursive_play();
  }

function play_all(the_queue) {
	play_sound_queue(the_queue);
}
///////////end sound stuff


$(function () {
	$('#chatbot-mic-input[data-toggle="tooltip"]').tooltip()
});

//////////////////////////////////

var recordedChunks = [];
var recordedB64String = null;
var startTime, endTime;
$(document).ready(function() {
	streamMicrophoneAudio();
});

function start_timer() {
  startTime = new Date();
};

function end_timer() {
  endTime = new Date();
  var timeDiff = endTime - startTime; //in ms
  timeDiff /= 1000;

  endTime = timeDiff;
}

$('#chatbot-mic-input').on('mousedown touchstart', function() {
	try {
		start_timer();
		recorder.start();
	} catch (error) {
		showError("You must allow microphone access to use this feature.")
	}
});

$('#chatbot-mic-input').on('mouseup mouseleave touchend', function() {
	
	try{
		end_timer()
		recorder.stop();

	} catch (error) {
		//do nothing already warned user
	}
});

async function streamMicrophoneAudio() {
	let stream;
	const constraints = { video: false, audio: true };
	
  
	try {
	  	stream = await navigator.mediaDevices.getUserMedia(constraints);
	} catch (error) {
		console.error("Microphone disabled.")
		return null;
	}

	recorder = new MediaRecorder(stream);
	recorder.ondataavailable = handleDataAvailable;
	return recorder;
  };

  // this is called after ending recording
function handleDataAvailable(event) {
	if (event.data.size > 0 && endTime > 0.1) {
		recordedChunks.push(event.data);
		blobToB64();
	}
}

function download() {
	var blob = new Blob(recordedChunks,
		{ 'type' : 'audio/webm;codecs=opus' });
	var url = URL.createObjectURL(blob);
	var a = document.createElement("a");
	document.body.appendChild(a);
	a.style = "display: none";
	a.href = url;
	a.download = "user.webm";
	a.click();
	window.URL.revokeObjectURL(url);
  }

function blobToB64() {
	var reader = new FileReader();
	var blob = new Blob(recordedChunks,
		{ 'type' : 'audio/webm;codecs=opus' });
    reader.readAsDataURL(blob);
    reader.onloadend = function () {
    	var base64String = reader.result;
		sendAudioToAPI(base64String);
		recordedB64String = base64String
		recordedChunks = []
	}
	
}
