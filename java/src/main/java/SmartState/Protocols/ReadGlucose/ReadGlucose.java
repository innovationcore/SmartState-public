package SmartState.Protocols.ReadGlucose;

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
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.ScheduledFuture;
import java.util.concurrent.TimeUnit;


public class ReadGlucose extends ReadGlucoseBase {
    private final Type typeOfHashMap = new TypeToken<Map<String, Map<String,Long>>>() { }.getType();
    private final Map<String, String> participantMap;
    private final Map<String,Long> stateMap;
    private long startTimestamp;
    public final TimezoneHelper TZHelper;
    private boolean isRestoring;
    public String stateJSON;
    public ScheduledExecutorService uploadSave;
    private final Gson gson;
    private long startDeadline;
    private long startWarnDeadline;
    private long endOfEpisode;
    private boolean isReset;
    private boolean hasStartedReading;
    private int glucoseCount;

    ScheduledExecutorService executor;
    ScheduledFuture<?> future;
    private static final Logger logger = LoggerFactory.getLogger(ReadGlucose.class.getName());

    public ReadGlucose(Map<String, String> participantMap) {
        this.gson = new Gson();
        this.participantMap = participantMap;
        this.stateMap = new HashMap<>();
        this.isRestoring = false;
        this.startTimestamp = 0;
        this.startDeadline = 0;
        this.startWarnDeadline = 0;
        this.endOfEpisode = 0;
        this.isReset = false;
        this.hasStartedReading = false;
        this.glucoseCount = 0;
        this.executor = Executors.newSingleThreadScheduledExecutor();

        // this initializes the user's and machine's timezone
        this.TZHelper = new TimezoneHelper(participantMap.get("time_zone"), TimeZone.getDefault().getID());

        this.uploadSave = Executors.newScheduledThreadPool(1);
        //set timer
        this.uploadSave.scheduleAtFixedRate(() -> {
            try {
                if (!getState().toString().equals("endProtocol")) {

                    if(startTimestamp > 0) {
                        stateJSON = saveStateJSON();
                        boolean didUpload = Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
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

    // will need to add rasa to this at some point
    public void incomingText(Map<String, String> incomingTextMap){
        if (isHelpMe(incomingTextMap.get("Body"))){
            ArrayList<String> adminNumbers = Launcher.dbEngine.getAdminNumbers();
            // for each admin number, send a message
            for (String number : adminNumbers) {
                Launcher.msgUtils.sendMessage(number, "ReadGlucose: A participant is in need of help!", "ADMIN");
            }
            Launcher.msgUtils.sendMessage(participantMap.get("number"), "We've notified study administrators that you need help. Someone will be contacting you soon.", "Default");
        }
    }


    // API
    public void incomingMessage(Map<String,String> incomingMap) {
        try {
            State state = getState();
            switch (state) {
                case initial:
                    //no timers
                    logger.warn(participantMap.get("participant_uuid") + " initial unexpected message");
                    break;
                case waitStart:
                    if (incomingMap.get("Command").equals("startGlucose")) {
                        receivedStartGlucose();
                        logger.warn(participantMap.get("participant_uuid") + " waitStart STARTED RECEIVING GLUCOSE READING");
                    } else {
                        logger.warn(participantMap.get("participant_uuid") + " waitStart unexpected message");
                    }
                    
                    break;
                case warnStartGlucose:
                    if (incomingMap.get("Command").equals("startGlucose")) {
                        receivedStartGlucose();
                        logger.warn(participantMap.get("participant_uuid") + " warnStartGlucose STARTED RECEIVING GLUCOSE READING");
                    } else {
                        logger.warn(participantMap.get("participant_uuid") + " warnStartGlucose unexpected message");
                    }
                    break;
                case missedStart:
                    // no timers
                    logger.warn(participantMap.get("participant_uuid") + " missedStart unexpected message");
                    break;
                case startReading:
                    if (incomingMap.get("Command").equals("endConnection")){
                        receivedEndConnection();
                    } else {
                        receivedError();
                    }
                    break;
                case finishedReading:
                    // no timers
                    logger.warn(participantMap.get("participant_uuid") + " finishedReading unexpected message");
                    break;
                case notifyAdmin:
                    // no timers
                    logger.warn(participantMap.get("participant_uuid") + " notifyAdmin unexpected message");
                    break;
                case endOfEpisode:
                    // no timers
                    logger.warn(participantMap.get("participant_uuid") + " endOfEpisode unexpected message");
                    break;
                case endReadGlucoseProtocol:
                    logger.warn(participantMap.get("participant_uuid") + " endReadGlucoseProtocol unexpected message.");
                    break;
                default:
                    logger.error("stateNotify: Invalid state: " + getState());
            }


        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("incomingMessage");
            logger.error(exceptionAsString);
        }
    }

    @Override
    public boolean stateNotify(String state){

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
            case waitStart:
                // setting wait timer
                int startWaitDiff;
                // if user is still within the first 15 mins of hour calculate the seconds til 15 after the hour
                if (TZHelper.isTimeWithinFirst15MinutesOfHour(startDeadline)){
                    startWaitDiff = (int) TZHelper.getSecondsToHour15();
                } else {
                    startWaitDiff = 900; //15 mins
                }
                setStartDeadline(startWaitDiff);
                String waitStartMessage = "Hi "+ participantMap.get("first_name") + ", it's time to check you blood glucose level. Please interact with you blood glucose device to start sending readings.";
                if(!this.isRestoring) {
                    Launcher.msgUtils.sendMessage(participantMap.get("number"), waitStartMessage, "Default");
                }
                logger.warn("in waitStart: startDeadline -> " + TZHelper.getDateFromAddingSeconds(startWaitDiff));
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case warnStartGlucose:
                int startWarnDiff;
                // if user is still between 15-30 mins of hour calculate the seconds til 30 after the hour
                if (TZHelper.isTimeWithinFirst1530MinutesOfHour(startWarnDeadline)){
                    startWarnDiff = (int) TZHelper.getSecondsToHour30();
                } else {
                    startWarnDiff = 900; //15 mins
                }
                setStartWarnDeadline(startWarnDiff);
                String warnStartMessage = "We haven't started receiving your glucose measurements yet. Please interact with you blood glucose device to start sending readings. If you need help, please respond with \"help me\".";
                if(!this.isRestoring) {
                    Launcher.msgUtils.sendMessage(participantMap.get("number"), warnStartMessage, "Default");
                }
                logger.warn("in warnStart: startWarnDeadline -> " + TZHelper.getDateFromAddingSeconds(startWarnDiff));
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case startReading:
                String startReadingMessage = "Your glucose readings are being received. We'll let you know your results momentarily.";
                if(!this.isRestoring) {
                    Launcher.msgUtils.sendMessage(participantMap.get("number"), startReadingMessage, "Default");
                }
                logger.warn("in startReading: startReadingMessage -> " + startReadingMessage);
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case missedStart:
                String missedStartReadingMessage = participantMap.get("first_name") + ", we haven't started receiving your glucose readings. We will notify an administrator to assist you.";
                if(!this.isRestoring) {
                    Launcher.msgUtils.sendMessage(participantMap.get("number"), missedStartReadingMessage, "Default");
                }
                logger.warn("in missedStart: missedStartReadingMessage -> " + missedStartReadingMessage);
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case notifyAdmin:
                String notifyAdminMessage = "A participant did not successfully interact with their glucose measurement device within the 30 minute time window or an error occurred. They may require help.";
                ArrayList<String> adminNumbers = Launcher.dbEngine.getAdminNumbers();
                // for each admin number, send a message
                for (String number : adminNumbers) {
                    Launcher.msgUtils.sendMessage(number, notifyAdminMessage, "ADMIN");
                }
                logger.info("in notifyAdmin: notifyAdminMessage -> " + notifyAdminMessage);
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case finishedReading:
                // reset vars and cancel executor
                if (future != null) {
                    hasStartedReading = false;
                    glucoseCount = 0;
                    future.cancel(true);
                }

                String result = Launcher.dbEngine.getGlucoseResults(participantMap.get("participant_uuid"));
                String finishedReadingMessage = "";
                if (result.equals("low")){
                    finishedReadingMessage = "All finished. Your blood glucose is low. Take steps to raise it.";
                } else if (result.equals("high")){
                    finishedReadingMessage = "All finished. Your blood glucose is high. Take steps to lower it.";
                }
                else if (result.equals("normal")) {
                    finishedReadingMessage = "All finished. Your blood glucose is normal. Keep up the good work!";
                }
                if(!this.isRestoring && !finishedReadingMessage.equals("")) {
                    Launcher.msgUtils.sendMessage(participantMap.get("number"), finishedReadingMessage, "Default");
                }
                logger.info("in finishedReading: finishedReadingMessage -> " + finishedReadingMessage);
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case endOfEpisode:
                int secondsTo2Hours = (int) TZHelper.getSecondsUntil2Hours();
                if (secondsTo2Hours < 0) {
                    secondsTo2Hours = 0;
                }
                setEndOfEpisodeDeadline(secondsTo2Hours);
                logger.info("in endOfEpisode: endOfEpisodeDeadline:" + TZHelper.getDateFromAddingSeconds(secondsTo2Hours));
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
                break;
            case endReadGlucoseProtocol:
                logger.warn(participantMap.get("participant_uuid") + " is not longer in ReadGlucose protocol.");
                stateJSON = saveStateJSON();
                Launcher.dbEngine.uploadSaveState(stateJSON, participantMap.get("participant_uuid"), "ReadGlucose");
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
            timerMap.put("startDeadline", (long) getStartDeadline());
            timerMap.put("startWarnDeadline", (long) getStartWarnDeadline());
            timerMap.put("endOfEpisodeDeadline", (long) getEndOfEpisodeDeadline());

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
            String saveStateJSON = Launcher.dbEngine.getSaveState(participantMap.get("participant_uuid"), "ReadGlucose");

            if (isReset) {
                this.isReset = true;
                logger.info("restoreSaveState: resetting participant: " + participantMap.get("participant_uuid"));
                receivedWaitStart(); // initial to waitStart
                this.isReset = false;
            } else {

                if (!saveStateJSON.equals("")) {
                    Map<String, Map<String, Long>> saveStateMap = gson.fromJson(saveStateJSON, typeOfHashMap);

                    Map<String, Long> historyMap = saveStateMap.get("history");
                    Map<String, Long> timerMap = saveStateMap.get("timers");

                    int stateIndex = (int) timerMap.get("stateIndex").longValue();
                    String stateName = State.values()[stateIndex].toString();
                    long startTime = timerMap.get("startTime");
                    long saveCurrentTime = timerMap.get("currentTime");
                    this.startDeadline = timerMap.get("startDeadline");
                    this.startWarnDeadline = timerMap.get("startWarnDeadline");
                    this.endOfEpisode = timerMap.get("endOfEpisodeDeadline");

                    boolean isSameDay = TZHelper.isSameDay(saveCurrentTime);
                    if (!isSameDay) {
                        // if state is endReadGlucoseProtocol, do not restart cycle
                        if (!stateName.equals("endReadGlucoseProtocol")) {
                            stateName = "waitStart";
                        }
                    }

                    switch (State.valueOf(stateName)) {
                        case initial:
                        case missedStart:
                        case notifyAdmin:
                        case finishedReading:
                        case endReadGlucoseProtocol:
                            //no timers
                            break;
                        case waitStart:
                            //resetting wait timer
                            this.isRestoring = true;
                            receivedWaitStart(); // initial to waitStart
                            this.isRestoring = false;
                            break;
                        case warnStartGlucose:
                            //resetting warn timer
                            this.isRestoring = true;
                            receivedWarnStart(); // initial to waitStart
                            this.isRestoring = false;
                            break;
                        case startReading:
                            this.isRestoring = true;
                            receivedStartGlucose();
                            this.isRestoring = false;
                            receivedError(); // was in the middle of a reading when failed, so error and notify admin
                            break;
                        case endOfEpisode:
                            // reset endOfEpisodeDeadline
                            // the quickest path to endOfEpisode, move it but don't save it
                            this.isRestoring = true;
                            receivedEndofEpisode();
                            this.isRestoring = false;
                            break;
                        default:
                            logger.error("restoreSaveState: Invalid state: " + stateName);
                    }
                } else {
                    logger.info("restoreSaveState: no save state found for " + participantMap.get("participant_uuid"));
                    receivedWaitStart(); // initial to waitStart
                }
            }
        } catch (Exception ex) {
            logger.error("restoreSaveState");
            logger.error(ex.getMessage());
            ex.printStackTrace();
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

            messageMap.put("protocol", "ReadGlucose");
            String json_string = gson.toJson(messageMap);

            String insertQuery = "INSERT INTO state_log " +
                    "(participant_uuid, ts, log_json)" +
                    " VALUES ('" + participantMap.get("participant_uuid") + "', " +
                    "NOW(), '" + json_string +
                    "')";

            Launcher.dbEngine.executeUpdate(insertQuery);
        }
    }

    private class GlucoseTimer implements Runnable {
        public void run() {
            // save glucoseCount results
            saveGlucoseResults();
            receivedEndConnection();
        }
    }

    public void handleGlucoseMessage(Map<String, String> decoded) {
        if (getState().toString().equals("waitStart") || getState().toString().equals("warnStartGlucose")){
            if (!hasStartedReading){
                hasStartedReading = true;
            }
            receivedStartGlucose();

            // increment count
            glucoseCount += 1;
            future = executor.scheduleWithFixedDelay(new GlucoseTimer(), 30, 30, TimeUnit.SECONDS);

        } else if (getState().toString().equals("startReading") && hasStartedReading){
            // increment count
            glucoseCount += 1;
            // reset timer (30 sec)
            future.cancel(true);
            future = executor.scheduleWithFixedDelay(new GlucoseTimer(), 30, 30, TimeUnit.SECONDS);

        }
        else {
            logger.error("Received device packet in wrong state.");
        }
    }

    private void saveGlucoseResults() {
        String result;
        if (glucoseCount%3 == 0) {
            result = "low";
        } else if (glucoseCount%3 == 2) {
            result = "high";
        } else {
            result = "normal";
        }
        Launcher.dbEngine.saveGlucoseResults(participantMap.get("participant_uuid"), result);
    }

}
