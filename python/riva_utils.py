import base64
import subprocess
import numpy as np
import riva.client


class RivaUtils:
	def __init__(self, riva_url):
		self.URI = riva_url
		self.auth = riva.client.Auth(uri=riva_url)

	def send_request_to_riva(self, text, proto='tts'):
		if proto == 'asr':
			asr_service = riva.client.ASRService(self.auth)

			offline_config = riva.client.RecognitionConfig(
				encoding=riva.client.AudioEncoding.LINEAR_PCM,
				max_alternatives=1,
				enable_automatic_punctuation=True,
				verbatim_transcripts=False,
			)

			my_wav_file = 'input.wav'
			riva.client.add_audio_file_specs_to_config(offline_config, my_wav_file)

			with open(my_wav_file, 'rb') as fh:
				data = fh.read()

			response = asr_service.offline_recognize(data, offline_config)
			print(response)
			try:
				asr_best_transcript = response.results[0].alternatives[0].transcript
			except Exception as e:
				asr_best_transcript = -1
			
			return asr_best_transcript	

		else: 
			tts_service = riva.client.SpeechSynthesisService(self.auth)

			language_code = 'en-US'
			sample_rate_hz = 16000

			resp = tts_service.synthesize(text, language_code=language_code, sample_rate_hz=sample_rate_hz)

			audio_samples = np.frombuffer(resp.audio, dtype=np.int16)
			return audio_samples, sample_rate_hz



	def save_blob_to_file(self, audio_str):
		_, audioB64 = audio_str.split(",")
		decode_string = base64.b64decode(audioB64)
		with open('temp.webm', 'wb') as f:
			f.write(decode_string)
		subprocess.run(["ffmpeg", "-y", "-i", "./temp.webm", "-vn", "./input.wav"])

		message = self.send_request_to_riva(text='', proto='asr')
		return message
