# Rasa Installation
Please refer to this link to install Rasa on your system: https://learning.rasa.com/
!!! It only works on Python 3.8 !!!

Requirements:
- Python 3.8
- Stack (https://github.com/commercialhaskell/stack) (optional, for time parsing)
- duckling (https://github.com/facebook/duckling) (option, for time parsing)

This guide is helpful for setting up Stack and duckling (https://medium.com/@adboio/using-duckling-to-extract-dates-and-times-in-your-rasa-chatbot-7687f4fde2e0)


## Testing
1. ``` rasa train ``` will create a model in models/
2. In another terminal, run ``` rasa run actions ```, this runs the actions server.
3. In the original terminal, run ``` rasa shell ``` this provides a CLI interaction with the bot.

## Running (assuming already trained)
1. Recommended to run in the background (i.e. using ``` nohup ```)
2. Run ``` rasa run --enable-api ```
3. Also run ``` rasa run actions ```
4. Send well-formatted API requests to localhost:5005/webhooks/rest/webhook (see: https://rasa.com/docs/rasa/connectors/your-own-website/)