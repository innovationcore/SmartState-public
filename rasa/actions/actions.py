from typing import Text, List, Any, Dict
import json

from rasa_sdk import Tracker, FormValidationAction, Action
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.types import DomainDict
from datetime import datetime as dt
from urllib import request, parse

class ValidateGlucoseForm(FormValidationAction):
    def name(self) -> Text:
        return "validate_glucose_form"


    def validate_last_eaten(
        self,
        slot_value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        """Validate `last_easten` time."""

        # If the last_eaten is "" probably typo
        if len(slot_value) == 0:
            dispatcher.utter_message(text="That must've been a typo.")
            return {"last_eaten": None}
        
        # checking if user needs help
        if tracker.latest_message['intent'].get('name') == "need_help":
            dispatcher.utter_message(text="Please enter the time since you last ate. For example, 10:00am or 30 minutes ago.")
            return {"last_eaten": None}

        try:
            payload = {'locale': 'en_US', 'text': slot_value, 'tz': 'America/Louisville'}
            data = parse.urlencode(payload).encode()
            req =  request.Request("http://duckling:8000/parse", data=data) # this will make the method "POST"
            response = request.urlopen(req)
            raw_data = response.read()
            encoding = response.info().get_content_charset('utf8')  # JSON default
            data = json.loads(raw_data.decode(encoding))
            
            time_str = data[0]['value']['value']

            time_obj = dt.strptime(time_str, "%Y-%m-%dT%H:%M:%S.%f%z")
            time_str_formatted = time_obj.strftime("%I:%M %p, %m/%d/%Y")
            return {"last_eaten": time_str_formatted}
        except:
            dispatcher.utter_message(text="That must have been a typo. Please enter the time since you last ate. For example, 10:00am or 30 minutes ago.")
            return {"last_eaten": None}    
    
    def validate_exercise(
        self,
        slot_value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        """Validate `exercise` value."""

        exercise = tracker.latest_message['intent'].get('name')
        if exercise == "affirm":
            return {"exercise": "Yes"}
        elif exercise == "deny":
            return {"exercise": "No"}
        elif exercise == "need_help":
            dispatcher.utter_message(text="Biking, weight lifting, or even going for a 30-minute walk counts as exercise!")
            return {"exercise": None}
        else:
            dispatcher.utter_message(text="That must've been a typo. Please answer 'Yes' or 'No'.")
            return {"exercise": None}

    
    def validate_stress(
        self,
        slot_value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        """Validate `stress` value."""

        if 'hi' in slot_value and not 'high' in slot_value:
            return {"stress": slot_value.replace('hi', 'high')}

        stress = tracker.latest_message['intent'].get('name')

        if stress == "inform_stress":
            return {"stress": slot_value}
        elif stress == "need_help":
            dispatcher.utter_message(text="Tell me your level of stress.") 
            return {"stress": None}
        else:
            dispatcher.utter_message(text="That must've been a typo. Please try again.")
            return {"stress": None}
    

    def validate_water(
        self,
        slot_value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        """Validate `water` value."""

        water = tracker.latest_message['intent'].get('name')
        if water == "affirm":
            return {"water": "Yes"}
        elif water == "deny":
            return {"water": "No"}
        elif water == "need_help":
            dispatcher.utter_message(text="Doctors recommend drinking about 15.5 cups of water for men and 11.5 cups for women each day. It's okay if you haven't met that goal, everyone needs to start somewhere!")
            return {"water": None}
        else:
            dispatcher.utter_message(text="That must've been a typo. Please answer 'Yes' or 'No'.")
            return {"water": None}
    
    def validate_need_help(
        self,
        slot_value: Text,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: DomainDict,
    ) -> Dict[Text, Any]:
        """Validate `need_help` value."""

        water = tracker.latest_message['intent'].get('name')
        if water == "affirm":
            return {"need_help": "Yes"}
        elif water == "deny":
            return {"need_help": "No"}
        elif water == "need_help":
            dispatcher.utter_message(text="If you would like to discuss these results or ways to stay healthy, a UK healthcare professional would be happy to assist you!")
            return {"need_help": None}
        else:
            dispatcher.utter_message(text="That must've been a typo. Please answer 'Yes' or 'No'.")
            return {"need_help": None}