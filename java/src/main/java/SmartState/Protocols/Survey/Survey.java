package SmartState.Protocols.Survey;

import SmartState.Launcher;
import SmartState.TimeUtils.TimezoneHelper;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.PrintWriter;
import java.io.StringWriter;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.TimeZone;


public class Survey extends SurveyBase {
    private final Type typeOfHashMap = new TypeToken<Map<String, Map<String,Long>>>() { }.getType();
    private final Map<String, String> participantMap;
    private final Map<String,Long> stateMap;
    private long startTimestamp;
    private final TimezoneHelper TZHelper;
    private boolean isBeingRestored;
    public String stateJSON;
    private final Gson gson;
    private static final Logger logger = LoggerFactory.getLogger(Survey.class.getName());

    public Survey(Map<String, String> participantMap) {
        this.gson = new Gson();
        this.participantMap = participantMap;
        this.stateMap = new HashMap<>();
        this.isBeingRestored = false;
        this.startTimestamp = 0;

        // this initializes the user's and machine's timezone
        this.TZHelper = new TimezoneHelper(participantMap.get("time_zone"), TimeZone.getDefault().getID());

        new Thread(() -> {
            try {
                while (true) {
                    if(startTimestamp > 0) {
                        stateJSON = saveStateJSON();
                        Launcher.dbEngine.uploadSaveState(stateJSON, "Survey", participantMap.get("participant_uuid"));
                    }
                    Thread.sleep(900000);
                }
            } catch (Exception ex) {
                logger.error("protocols.Survey Thread: " + ex);
                StringWriter sw = new StringWriter();
                PrintWriter pw = new PrintWriter(sw);
                ex.printStackTrace(pw);
                logger.error(pw.toString());
            }
        }).start();

    }

    public void incomingText(Map<String, String> incomingTextMap){
        if (isHelpMe(incomingTextMap.get("Body"))){
            ArrayList<String> adminNumbers = Launcher.dbEngine.getAdminNumbers();
            // for each admin number, send a message
            for (String number : adminNumbers) {
                Launcher.msgUtils.sendMessage(number, "Survey: Participant " + participantMap.get("first_name") + " " + participantMap.get("last_name") +" ("+participantMap.get("number")+") is in need of help!");
            }
            Launcher.msgUtils.sendMessage(participantMap.get("number"), "We've notified study administrators that you need help. Someone will be contacting you soon.");
        }
    }

    @Override
    public boolean stateNotify(String state){
        String surveyToken;

        //save change to state log
        logState(state);

        if(stateMap != null) {
            stateMap.put(state, System.currentTimeMillis() / 1000);
        }
        if(startTimestamp == 0) {
            startTimestamp = System.currentTimeMillis() / 1000;
        } else {
            stateJSON = saveStateJSON();
        }


        switch (State.valueOf(state)) {
            case initial:
                //no timers
                break;
            case waitForNoon:
                //set timer for noon
                int secondsTilNoon = TZHelper.getSecondsTo12pm(false);
                if(secondsTilNoon < 0) {
                    secondsTilNoon = TZHelper.getSecondsTo12pm(true);
                }
                setDeadlineNoon(secondsTilNoon);
                break;
            case noonSurvey:
                surveyToken = Launcher.dbEngine.generateAndSaveToken(participantMap.get("participant_uuid"));
                String noonSurveyMsg = "Hello! Please take a moment to complete the following survey about your health: "+Launcher.surveyURL+surveyToken+"/"+participantMap.get("participant_uuid");
                logger.info(noonSurveyMsg);
                Launcher.msgUtils.sendMessage(participantMap.get("number"), noonSurveyMsg);
                break;
            case waitFor6pm:
                int secondsTil6pm = TZHelper.getSecondsTo6pm();
                if(secondsTil6pm < 0) {
                    secondsTil6pm = 0;
                }
                setDeadline6pm(secondsTil6pm);
                break;
            case survey6pm:
                surveyToken = Launcher.dbEngine.generateAndSaveToken(participantMap.get("participant_uuid"));
                String survey6pmMsg = "Good Evening! Please take a moment to complete the following survey about your health: "+Launcher.surveyURL+surveyToken+"/"+participantMap.get("participant_uuid");
                logger.info(survey6pmMsg);
                Launcher.msgUtils.sendMessage(participantMap.get("number"), survey6pmMsg);
                break;
            case endSurveyProtocol:
                logger.warn(participantMap.get("participant_uuid") + " is not longer in Survey protocol.");
                break;
            default:
                logger.error("stateNotify: Invalid state: " + state);
        }

        return true;
    }

    private boolean isHelpMe(String messageBody) {
        boolean isHelp = false;
        try {
            isHelp = messageBody.toLowerCase().contains("help") || messageBody.toLowerCase().contains("help me");

        } catch (Exception ex) {
            logger.error("isHelp(): " + ex.getMessage());
            StringWriter sw = new StringWriter();
            PrintWriter pw = new PrintWriter(sw);
            ex.printStackTrace(pw);
            logger.error(pw.toString());
        }
        return isHelp;
    }

    public String saveStateJSON() {
        String stateJSON = null;
        try {

            Map<String,Long> timerMap = new HashMap<>();
            timerMap.put("stateIndex", (long) getState().ordinal());
            timerMap.put("startTime", startTimestamp);
            timerMap.put("currentTime", System.currentTimeMillis() / 1000);
            timerMap.put("deadlineNoon", (long) getDeadlineNoon());
            timerMap.put("deadline6pm", (long) getDeadline6pm());

            Map<String,Map<String,Long>> stateSaveMap = new HashMap<>();
            stateSaveMap.put("history",stateMap);
            stateSaveMap.put("timers", timerMap);

            stateJSON = gson.toJson(stateSaveMap);


        } catch (Exception ex) {
            logger.error("saveStateJSON: " + ex.getMessage());
            StringWriter sw = new StringWriter();
            PrintWriter pw = new PrintWriter(sw);
            ex.printStackTrace(pw);
            logger.error(pw.toString());

        }
        return stateJSON;
    }

    public void restoreSaveState() {
        try{
            String saveStateJSON = Launcher.dbEngine.getSaveState(participantMap.get("participant_uuid"), "Survey");

            if (!saveStateJSON.equals("")){
                Map<String, Map<String,Long>> saveStateMap = gson.fromJson(saveStateJSON,typeOfHashMap);

                Map<String,Long> historyMap = saveStateMap.get("history");
                Map<String,Long> timerMap = saveStateMap.get("timers");

                int stateIndex = (int) timerMap.get("stateIndex").longValue();
                String stateName = State.values()[stateIndex].toString();
                long saveCurrentTime = timerMap.get("currentTime");

                if(!stateName.equals("endSurveyProtocol")){
                    if (TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()){
                        stateName = "waitForNoon";
                    } else if (!TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()){
                        stateName = "waitFor6pm";
                    } else if (!TZHelper.isBeforeNoon() && !TZHelper.isBefore6pm()){
                        stateName = "waitForNoon";
                    }
                }
                
                switch (State.valueOf(stateName)) {
                    case initial:
                    case noonSurvey:
                    case survey6pm:
                    case endSurveyProtocol:
                        //no timers
                        break;
                    case waitForNoon:
                        this.isBeingRestored = true;
                        isBeforeNoon();
                        this.isBeingRestored = false;
                        break;
                    case waitFor6pm:
                        this.isBeingRestored = true;
                        isBefore6pm();
                        this.isBeingRestored = false;
                        break;
                    default:
                        logger.error("restoreSaveState: Invalid state: " + stateName);
                }
            }
            else {
                logger.info("restoreSaveState: no save state found for " + participantMap.get("participant_uuid"));
                // see if current time is before noon or before 6pm
                if (TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()){
                    isBeforeNoon();
                } else if (!TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()){
                    isBefore6pm();
                } else if (!TZHelper.isBeforeNoon() && !TZHelper.isBefore6pm()){
                    isBeforeNoon();
                }
            }

        } catch (Exception ex) {
            logger.error("restoreSaveState");
            logger.error(ex.getMessage());
        }
    }

    public void logState(String state) {
        if(gson != null) {
            Map<String,String> messageMap = new HashMap<>();
            messageMap.put("state",state);
            if (this.isBeingRestored) {
                messageMap.put("restored","true");
            }
            messageMap.put("protocol", "Survey");
            String json_string = gson.toJson(messageMap);

            String insertQuery = "INSERT INTO state_log " +
                    "(participant_uuid, TS, log_json)" +
                    " VALUES ('" + participantMap.get("participant_uuid") + "', " +
                    "GETUTCDATE(), '" + json_string +
                    "')";

            Launcher.dbEngine.executeUpdate(insertQuery);
        }
    }
}
