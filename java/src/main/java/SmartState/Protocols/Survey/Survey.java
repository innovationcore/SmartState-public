package SmartState.Protocols.Survey;

import SmartState.Launcher;
import SmartState.TimeUtils.TimezoneHelper;
import SmartState.Webapi.LlmConnector;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import org.json.JSONArray;
import org.json.JSONObject;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.PrintWriter;
import java.io.StringWriter;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.TimeZone;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;


public class Survey extends SurveyBase {
    private final Type typeOfHashMap = new TypeToken<Map<String, Map<String,Long>>>() { }.getType();
    private final Map<String, String> participantMap;
    private final Map<String,Long> stateMap;
    private long startTimestamp;
    public final TimezoneHelper TZHelper;
    private boolean isRestoring;
    private boolean isReset;
    private final boolean useLlmForDataCollection = true; //todo false
    public String stateJSON;
    public ScheduledExecutorService uploadSave;
    public LlmConnector llm;
    public JSONArray llmMessages;
    public int numLlmRetries;
    public String currentSurveyToken;
    private final Gson gson;
    private static final Logger logger = LoggerFactory.getLogger(Survey.class.getName());

    public Survey(Map<String, String> participantMap) {
        this.gson = new Gson();
        this.participantMap = participantMap;
        this.stateMap = new HashMap<>();
        this.isRestoring = false;
        this.isReset = false;
        this.startTimestamp = 0;
        this.llm = new LlmConnector();
        this.llmMessages = new JSONArray();
        this.numLlmRetries = 0;
        this.currentSurveyToken = null;

        // this initializes the user's and machine's timezone
        this.TZHelper = new TimezoneHelper(participantMap.get("time_zone"), TimeZone.getDefault().getID());

        //create timer
        this.uploadSave = Executors.newScheduledThreadPool(1);
        //set timer
        this.uploadSave.scheduleAtFixedRate(() -> {
            try {
                if (!getState().toString().equals("endProtocol")) {

                    if(startTimestamp > 0) {
                        stateJSON = saveStateJSON();
                        boolean didUpload = Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "Survey");
                        if(!didUpload){
                            logger.error("saveState failed to upload for participant: " + participantMap.get("participant_uuid"));
                        }
                    }

                    String currentTimezone = Launcher.dbEngine.getParticipantTimezone(participantMap.get("participant_uuid"));
                    if (!participantMap.get("time_zone").equals(currentTimezone) && !currentTimezone.equals("")){
                        participantMap.put("time_zone", currentTimezone);
                        TZHelper.setUserTimezone(currentTimezone);
                    }
                }
            } catch (Exception ex) {
                logger.error("protocols.Baseline Thread");
                logger.error(ex.getMessage());
            }
        }, 30, 900, TimeUnit.SECONDS); //900 sec is 15 mins

    }

    public void incomingText(Map<String, String> incomingTextMap){
        State state = getState();
        if (isHelpMe(incomingTextMap.get("Body"))){
            ArrayList<String> adminNumbers = Launcher.dbEngine.getAdminNumbers();
            // for each admin number, send a message
            for (String number : adminNumbers) {
                Launcher.msgUtils.sendMessage(number, "Survey: A participant is in need of help!", "ADMIN");
            }
            Launcher.msgUtils.sendMessage(participantMap.get("number"), "We've notified study administrators that you need help. Someone will be contacting you soon.", "Default");
        } else if (state.equals(State.noonSurvey) || state.equals(State.survey6pm)){
            if (this.useLlmForDataCollection){
                llmSurvey(incomingTextMap.get("Body"));
            }
        }
    }

    @Override
    public boolean stateNotify(String state){
        String surveyToken, surveyURL;

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
                logger.info("in waitForNoon -> deadlineNoon = " + TZHelper.getDateFromAddingSeconds(secondsTilNoon));
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "Survey");
                break;
            case noonSurvey:
                surveyToken = Launcher.dbEngine.generateAndSaveToken(participantMap.get("participant_uuid"));
                this.currentSurveyToken = surveyToken;
                String noonSurveyMsg;
                if (this.useLlmForDataCollection) {
                    noonSurveyMsg = "How often do you exercise per week?";
                } else {
                    surveyURL = Launcher.surveyURL + surveyToken + "/" + participantMap.get("participant_uuid");
                    noonSurveyMsg = "Hello! Please take a moment to complete the following survey about your health: " + surveyURL;
                }
                Launcher.msgUtils.sendMessage(participantMap.get("number"), noonSurveyMsg, "Default");
                logger.info("in noonSurvey -> survey" + noonSurveyMsg);
                llmSurvey("100 times per week");
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "Survey");
                break;
            case waitFor6pm:
                int secondsTil6pm = TZHelper.getSecondsTo6pm();
                if(secondsTil6pm < 0) {
                    secondsTil6pm = 0;
                }
                setDeadline6pm(secondsTil6pm);
                logger.info("in waitFor6pm -> deadline6pm = " + TZHelper.getDateFromAddingSeconds(secondsTil6pm));
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "Survey");
                break;
            case survey6pm:
                surveyToken = Launcher.dbEngine.generateAndSaveToken(participantMap.get("participant_uuid"));
                this.currentSurveyToken = surveyToken;
                String survey6pmMsg;
                if (this.useLlmForDataCollection) {
                    survey6pmMsg = "How often do you exercise per week?";
                } else {
                    surveyURL = Launcher.surveyURL + surveyToken + "/" + participantMap.get("participant_uuid");
                    survey6pmMsg = "Good Evening! Please take a moment to complete the following survey about your health: " + surveyURL;
                }
                Launcher.msgUtils.sendMessage(participantMap.get("number"), survey6pmMsg, "Default");
                logger.info("in survey6pm -> survey " + survey6pmMsg);
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "Survey");
                break;
            case endSurveyProtocol:
                logger.warn(participantMap.get("participant_uuid") + " is not longer in Survey protocol.");
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "Survey");
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

    public void restoreSaveState(boolean isReset) {
        try{
            String saveStateJSON = Launcher.dbEngine.getSaveState(participantMap.get("participant_uuid"), "Survey");

            if (isReset) {
                this.isReset = true;
                logger.info("restoreSaveState: resetting participant: " + participantMap.get("participant_uuid"));
                if (TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()) {
                    isBeforeNoon();
                } else if (!TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()) {
                    isBefore6pm();
                } else if (!TZHelper.isBeforeNoon() && !TZHelper.isBefore6pm()) {
                    isBeforeNoon();
                }
                this.isReset = false;
            } else {

                if (!saveStateJSON.equals("")) {
                    Map<String, Map<String, Long>> saveStateMap = gson.fromJson(saveStateJSON, typeOfHashMap);

                    Map<String, Long> historyMap = saveStateMap.get("history");
                    Map<String, Long> timerMap = saveStateMap.get("timers");

                    int stateIndex = (int) timerMap.get("stateIndex").longValue();
                    String stateName = State.values()[stateIndex].toString();
                    long saveCurrentTime = timerMap.get("currentTime");

                    if (!stateName.equals("endSurveyProtocol")) {
                        if (TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()) {
                            stateName = "waitForNoon";
                        } else if (!TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()) {
                            stateName = "waitFor6pm";
                        } else if (!TZHelper.isBeforeNoon() && !TZHelper.isBefore6pm()) {
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
                            this.isRestoring = true;
                            isBeforeNoon();
                            this.isRestoring = false;
                            break;
                        case waitFor6pm:
                            this.isRestoring = true;
                            isBefore6pm();
                            this.isRestoring = false;
                            break;
                        default:
                            logger.error("restoreSaveState: Invalid state: " + stateName);
                    }
                } else {
                    logger.info("restoreSaveState: no save state found for " + participantMap.get("participant_uuid"));
                    // see if current time is before noon or before 6pm
                    if (TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()) {
                        isBeforeNoon();
                    } else if (!TZHelper.isBeforeNoon() && TZHelper.isBefore6pm()) {
                        isBefore6pm();
                    } else if (!TZHelper.isBeforeNoon() && !TZHelper.isBefore6pm()) {
                        isBeforeNoon();
                    }
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
            if (this.isRestoring) {
                messageMap.put("restored","true");
            }
            if (this.isReset) {
                messageMap.put("RESET", "true");
            }
            messageMap.put("protocol", "Survey");
            String json_string = gson.toJson(messageMap);

            String insertQuery = "INSERT INTO state_log " +
                    "(participant_uuid, ts, log_json)" +
                    " VALUES ('" + participantMap.get("participant_uuid") + "', " +
                    "NOW(), '" + json_string +
                    "')";

            Launcher.dbEngine.executeUpdate(insertQuery);
        }
    }

    public void llmSurvey(String response) {
        ArrayList<String> validCategories = new ArrayList<>();
        validCategories.add("never");
        validCategories.add("1-2 times");
        validCategories.add("3-4 times");
        validCategories.add("5+ times");

        String systemPrompt = "You must determine if the user submits a valid response. The user will be " +
                "answering the question: \"How often do you exercise per week?\". If you are able to fit the" +
                " users response into one of the following categories, then the response is valid. The categories are:" +
                " \"Never\", \"1-2 times\", \"3-4 times\", and \"5+ times\". You then" +
                " must return the category that the fits the users response. For example, if the user responds" +
                " with \"I exercise twice a week\", you would respond with \"1-2 times\". If you cannot " +
                "determine which category fits the users response, list the categories and ask the question again." +
                "Respond only with one of the categories and no other text.";
        JSONObject systemJson = new JSONObject();
        systemJson.put("role", "system");
        systemJson.put("content", systemPrompt);
        llmMessages.put(systemJson);

        String noonSurveyMsg = "How often do you exercise per week?";
        JSONObject assistantMessage = new JSONObject();
        assistantMessage.put("role", "assistant");
        assistantMessage.put("content", noonSurveyMsg);
        llmMessages.put(assistantMessage);

        JSONObject userMessage = new JSONObject();
        userMessage.put("role", "user");
        userMessage.put("content", response);
        llmMessages.put(userMessage);

        JSONObject responseJson = llm.query(llmMessages);
        String llmResponse = responseJson.getString("content");

        if (validCategories.contains(llmResponse.toLowerCase())) {
            assistantMessage = new JSONObject();
            assistantMessage.put("role", "assistant");
            assistantMessage.put("content", llmResponse);
            llmMessages.put(assistantMessage);

            JSONObject answers = new JSONObject();
            answers.put("exercise", llmResponse);
            answers.put("diet", "Average");     // demo, replace with actual response
            answers.put("smoke", "smoke-no");   // demo, replace with actual response
            answers.put("sleep", "4-6 hours");  // demo, replace with actual response
            answers.put("water", "1-2 liters"); // demo, replace with actual response
            answers.put("comments", "N/a");     // demo, replace with actual response

            Launcher.dbEngine.saveSurveyResponses(answers, this.currentSurveyToken);

            // when completely through survey questioning call the following
            this.currentSurveyToken = null;
            llmMessages.clear();
            numLlmRetries = 0;

        } else {
            numLlmRetries++;
        }
    }
}
