import requests
import json

#https://rasa.com/docs/rasa/pages/http-api#operation/triggerConversationIntent

class Rasa_Connector:
	def __init__(self, url="localhost", port=5005):
		self.url = str(url)
		self.port = str(port)


	# SERVER INFO
	def health(self):
		response = requests.get("http://"+self.url+":"+self.port+"/")
		return response.text(), response.status_code

	def version(self):
		response = requests.get("http://"+self.url+":"+self.port+"/version")
		return response.json(), response.status_code

	def status(self):
		response = requests.get("http://"+self.url+":"+self.port+"/status")
		return response.json(), response.status_code

	# TRACKER
	def track_convo(self, convo_id):
		response = requests.get("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/tracker")
		return response.json()
	
	def append_tracker_events(self, convo_id, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e
		
		response = requests.post("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/tracker/events", json=data)
		return response.json(), response.status_code
	
	def replace_tracker_event(self, convo_id, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e
		
		response = requests.put("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/tracker/events", json=data)
		return response.json(), response.status_code

	def retrieve_story(self, convo_id):
		response = requests.get("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/story")
		return response.json(), response.status_code
	
	def inject_intent(self, convo_id, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e
		
		response = requests.post("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/trigger_intent", json=data)
		return response.json(), response.status_code

	def predict(self, convo_id):
		response = requests.post("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/predict")
		return response.json(), response.status_code

	def add_tracker_message(self, convo_id, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e
		
		response = requests.post("http://"+self.url+":"+self.port+"/conversations/"+convo_id+"/messages", json=data)
		return response.json(), response.status_code

	# MODEL
	def train(self, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e
		
		response = requests.post("http://"+self.url+":"+self.port+"/model/train", json=data)
		return response.json(), response.status_code
	
	def evaluate_stories(self):
		response = requests.post("http://"+self.url+":"+self.port+"/model/test/stories")
		return response.json(), response.status_code
	
	def evaluate_intent(self, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e

		response = requests.post("http://"+self.url+":"+self.port+"/model/test/intents", json=data)
		return response.json(), response.status_code

	def predict_action(self, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e

		response = requests.post("http://"+self.url+":"+self.port+"/model/predict", json=data)
		return response.json(), response.status_code

	def send_message(self, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e

		response = requests.post("http://"+self.url+":"+self.port+"/model/parse", json=data)
		return response.json(), response.status_code
	
	def replace_model(self, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e
		
		response = requests.put("http://"+self.url+":"+self.port+"/model", json=data)
		return response.json(), response.status_code

	def unload_model(self):
		response = requests.delete("http://"+self.url+":"+self.port+"/model")
		return response.json(), response.status_code
	
	# DOMAIN
	def retrieve_domain(self):
		response = requests.get("http://"+self.url+":"+self.port+"/domain")
		return response.json(), response.status_code


class Rasa_Webhooks:
	def __init__(self, url="localhost:5005"):
		url, port = url.split(":")
		self.url = str(url)
		self.port = str(port)
	
	def send_message(self, data):
		try:
			data = json.loads(data)
		except ValueError as e:
			return e

		response = requests.post("http://"+self.url+":"+self.port+"/webhooks/rest/webhook", json=data)
		return response.json(), response.status_code
	