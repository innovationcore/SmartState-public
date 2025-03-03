package SmartState.MessagingUtils;

// Install the Java helper library from twilio.com/docs/java/install

import SmartState.Launcher;
import com.google.gson.Gson;
import com.twilio.Twilio;
import com.twilio.rest.api.v2010.account.Message;
import com.twilio.type.PhoneNumber;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.text.SimpleDateFormat;
import java.time.ZoneId;
import java.time.ZonedDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

public class MsgUtils {
    // Find your Account SID and Auth Token at twilio.com/console
    // and set the environment variables. See http://twil.io/secure
    private final String textFrom;
    private final Logger logger;
    private final Gson gson;

    public MsgUtils() {
        logger = LoggerFactory.getLogger(MsgUtils.class);
        textFrom = Launcher.config.getStringParam("twilio_from_number");
        gson = new Gson();
        Twilio.init(Launcher.config.getStringParam("twilio_account_sid"), Launcher.config.getStringParam("twilio_auth_token"));
    }

    public void sendMessage(String textTo, String body, String study) {
        try {
            Map<String, String> participantIds = Launcher.dbEngine.getParticipantIdFromPhoneNumber(textTo);
            String participantId = participantIds.get(study);
            Boolean isMessagingDisabled = Launcher.config.getBooleanParam("disable_messaging");

            if (isMessagingDisabled) {
                logger.warn("Messaging is disabled. Messages will be saved, but not sent.");
            } else {
                String toNumber;
                // you can set the below equal to a Message object for later use
                toNumber = textTo;

                Message.creator(
                                new PhoneNumber(toNumber),
                                new PhoneNumber(textFrom),
                                body)
                        .create();
            }

            String messageId = UUID.randomUUID().toString();
            String messageDirection = "outgoing";

            Date date = new Date();
            SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS");
            format.setTimeZone(TimeZone.getTimeZone("UTC"));
            String timestamp = format.format(date);

            Map<String, String> messageMap = new HashMap<>();
            String strippedBody = body.replaceAll("[\\n\\r\\t]", ""); // Strip newlines, carriage returns, tabs
            messageMap.put("Body", strippedBody);
            String json_string = gson.toJson(messageMap);
            logger.info(json_string);

            String insertQuery = "INSERT INTO messages " +
                    "(message_uuid, participant_uuid, TS, message_direction, message_json, study)" +
                    " VALUES ('" + messageId + "', '" +
                    participantId + "' ,'" + timestamp + "', '" +
                    messageDirection + "', '" + json_string + "', '" + study + "')";


            Launcher.dbEngine.executeUpdate(insertQuery);
        } catch (Exception e) {
            logger.error("Exception occurred trying to send a message...");
            e.printStackTrace();
        }
    }

    public void sendScheduledMessage(String textTo, String body, ZonedDateTime dateTime, String study) {
        try {
            Map<String, String> participantIds = Launcher.dbEngine.getParticipantIdFromPhoneNumber(textTo);
            String participantId = participantIds.get(study);

            String messageId = UUID.randomUUID().toString();

            ZonedDateTime dateTimeWithTimezone = dateTime.withZoneSameInstant(ZoneId.of("UTC"));

            String scheduledFor = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss VV").format(dateTimeWithTimezone);

            Map<String, String> messageMap = new HashMap<>();
            String strippedBody = body.replaceAll("[\\n\\r\\t]", ""); // Strip newlines, carriage returns, tabs
            messageMap.put("Body", strippedBody);
            String json_string = gson.toJson(messageMap);
            logger.info("Message queued for: " + scheduledFor + ", Message: " + json_string);

            String insertQuery = "INSERT INTO queued_messages " +
                    "(message_uuid, participant_uuid, toNumber, fromNumber, scheduledFor, message_json, study)" +
                    " VALUES ('" + messageId + "', '" + participantId + "','" + textTo + "','" + textFrom + "','" + scheduledFor + "','" + json_string + "','" + study + "')";

            Launcher.dbEngine.executeUpdate(insertQuery);
        } catch (Exception e) {
            logger.error("Exception occurred trying to send scheduled message...");
            e.printStackTrace();
        }
    }
}
