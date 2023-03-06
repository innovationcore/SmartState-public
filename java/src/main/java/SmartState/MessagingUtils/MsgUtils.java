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

    public void sendMessage(String textTo, String body) {
        Boolean isMessagingDisabled = Launcher.config.getBooleanParam("disable_messaging");
        if (isMessagingDisabled) {
            logger.warn("Messaging is disabled. Messages will be saved, but not sent.");
        }
        else {
            Message.creator(
                            new PhoneNumber(textTo),
                            new PhoneNumber(textFrom),
                            body)
                    .create();
        }
        String messageId = UUID.randomUUID().toString();
        String participantId = Launcher.dbEngine.getParticipantIdFromPhoneNumber(textTo);
        String messageDirection = "outgoing";

        Date date = new Date();
        SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS");
        format.setTimeZone(TimeZone.getTimeZone("UTC"));
        String timestamp = format.format(date);

        Map<String,String> messageMap = new HashMap<>();
        messageMap.put("Body",body);
        String json_string = gson.toJson(messageMap);

        String insertQuery = "INSERT INTO messages " +
                "(message_uuid, participant_uuid, TS, message_direction, message_json)" +
                " VALUES ('" + messageId + "', '" +
                participantId + "' ,'" + timestamp + "', '" +
                messageDirection + "', '" + json_string +
                "')";

        Launcher.dbEngine.executeUpdate(insertQuery);
    }
}
