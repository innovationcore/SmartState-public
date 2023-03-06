import base64
import configparser
import io
import json
import numpy as np
from flask import Flask, jsonify, request
from scipy.io import wavfile
from rasa_connector import Rasa_Webhooks
from riva_utils import RivaUtils

CONFIG = configparser.ConfigParser()
CONFIG.read('config.ini')
RIVA_URL_PORT = CONFIG['DEFAULT']['RivaURL'] + ":" + CONFIG['DEFAULT']['RivaPort']
RASA_URL_PORT = CONFIG['DEFAULT']['RasaURL'] + ":" + CONFIG['DEFAULT']['RasaPort']

app = Flask("SmartState Chatbot API")

@app.route('/api/audiototext', methods=['POST'])
def getTextFromAudio():
	riva_utils = RivaUtils(RIVA_URL_PORT)
	message_data = request.get_json()
	return_dict = {}
	return_dict['response_msg'] = []
	
	user_message = riva_utils.save_blob_to_file(audio_str=message_data['audio'])

	return_dict['response_msg'].append({'text': user_message})
	return jsonify(return_dict), 200
	

@app.route('/api/sendmessage', methods=['POST'])
def send_message():
	riva_utils = RivaUtils(RIVA_URL_PORT)
	message_data = request.get_json()
	return_dict = {}
	return_dict['response_msg'] = []
	
	user_message = ""
	if message_data['audio'] != -1:
		user_message = riva_utils.save_blob_to_file(audio_str=message_data['audio'])
	else:
		user_message = message_data['message']

	rasa_hooks = Rasa_Webhooks(url=RASA_URL_PORT)
	resp = []
	if user_message != -1:
		resp, code = rasa_hooks.send_message(json.dumps({'sender': message_data['sender'], 'message': user_message}))
	else:
		code = 200
		resp.append({'text': "Sorry I didn't get that. Can you rephrase?"})
	if code == 200:
		for response in resp:
			the_image = ''
			the_text = ''
			the_audio = ''
			the_user_message = user_message
			if 'image' in response:
				the_image = response['image']
			elif 'text' in response:
				audio, samplerate = riva_utils.send_request_to_riva(text=response['text'], proto='tts')
				if len(audio.shape) > 1:
					audio = np.average(audio, axis=1)
				bytes_wav = bytes()
				byte_io = io.BytesIO(bytes_wav)
				wavfile.write(byte_io, samplerate, audio)
				wav_bytes = byte_io.read()
				the_text = response['text'].replace("\r\n", "<br>").replace("\r","<br>").replace("\n", "<br>")
				the_audio = "data:audio/wav;base64,"+base64.b64encode(wav_bytes).decode("utf-8")
			return_dict['response_msg'].append({'text': the_text, 'audio': the_audio, 'image': the_image, 'sent_message': the_user_message})
			user_message = -1		
		
		return jsonify(return_dict), 200
	else:
		return jsonify({'response_msg': [{'text':'An error occured. Please wait a few minutes or refresh the page.'}]}), 500

@app.after_request
def after_request(response):
	header = response.headers
	header['Access-Control-Allow-Origin'] = '*'
	header['Access-Control-Allow-Headers'] = 'Content-Type, Authorization'
	header['Access-Control-Allow-Methods'] = 'OPTIONS, HEAD, GET, POST, DELETE, PUT'
	return response

if __name__ == '__main__':
	app.run(host='0.0.0.0', port=8181, debug=False, ssl_context=('mycert.crt', 'mycert.key'))
