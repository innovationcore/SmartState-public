package SmartState.Protocols.ReadGlucose;

import SmartState.Launcher;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.PrintWriter;
import java.io.StringWriter;
import java.util.*;
import java.util.concurrent.atomic.AtomicBoolean;

public class ReadGlucoseWatcher {
    private final Logger logger;
    private final AtomicBoolean lockReadGlucose = new AtomicBoolean();
    private final AtomicBoolean lockEpisodeReset = new AtomicBoolean();
    private final AtomicBoolean lockGlucoseMessage = new AtomicBoolean();
    private final Map<String,ReadGlucose> readGlucoseMap;

    public ReadGlucoseWatcher() {
        this.logger = LoggerFactory.getLogger(ReadGlucoseWatcher.class);
        this.readGlucoseMap = Collections.synchronizedMap(new HashMap<>());

        //how long to wait before checking protocols
        long checkdelay = Launcher.config.getLongParam("checkdelay", 5000L);
        long checktimer = Launcher.config.getLongParam("checktimer", 30000L);

        //create timer
        Timer checkTimer = new Timer();
        //set timer
        checkTimer.scheduleAtFixedRate(new startReadGlucose(), checkdelay, checktimer);
    }

    public void incomingText(String participantId, Map<String,String> incomingMap) {
        try {

            logger.info("Incoming number: " + incomingMap.get("From") + " partID: " + participantId);

            synchronized (lockReadGlucose) {
                if(readGlucoseMap.containsKey(participantId)) {
                    readGlucoseMap.get(participantId).incomingText(incomingMap);
                }
            }

        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("incomingText");
            logger.error(exceptionAsString);
        }
    }

    public void incomingMessage(String participantId, Map<String,String> incomingMap) {
        try {

            //From
            logger.info("Command: " + incomingMap.get("Command") + " partID: " + participantId);

            synchronized (lockReadGlucose) {
                if(readGlucoseMap.containsKey(participantId)) {
                    readGlucoseMap.get(participantId).incomingMessage(incomingMap);
                }

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

    public String getValidNextStates(String partUUID){
        String validNextStates = "";
        
        try {
            String currentState = Launcher.dbEngine.getParticipantCurrentState(partUUID, "ReadGlucose");

            switch (currentState){
                case "initial":
                    validNextStates = "waitStart,warnStartGlucose,endReadGlucoseProtocol";
                    break;
                case "waitStart":
                    validNextStates = "warnStartGlucose,startReading,endReadGlucoseProtocol";
                    break;
                case "warnStartGlucose":
                    validNextStates = "startReading,missedStart,endReadGlucoseProtocol";
                    break;
                case "startReading":
                    validNextStates = "finishedReading,notifyAdmin,endReadGlucoseProtocol";
                    break;
                case "missedStart":
                    validNextStates = "notifyAdmin";
                    break;
                case "notifyAdmin":
                case "finishedReading":
                    validNextStates = "endOfEpisode";
                    break;
                case "endOfEpisode":
                    validNextStates = "waitStart,endReadGlucoseProtocol";
                    break;
                case "endReadGlucoseProtocol":
                    validNextStates = "";
                    break;
                default:
                    // not in any state?
                    break;
            }
        } catch (Exception ex){
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("getValidNextStates");
            logger.error(exceptionAsString);
        }

        return validNextStates;
    }

    public String moveToState(String participantId, String moveToState, String time) {
        String newState = "";
        try {
            ReadGlucose participant = readGlucoseMap.get(participantId);
            switch (participant.getState()){
                case initial:
                    if (moveToState.equals("waitStart")){
                        participant.receivedWaitStart();
                        newState = "waitStart";
                    } else if (moveToState.equals("warnStartGlucose")) {
                        participant.receivedWarnStart();
                        newState = "warnStartGlucose";
                    } else if (moveToState.equals("endReadGlucoseProtocol")){
                        participant.receivedEndProtocol();
                        newState = "endReadGlucoseProtocol";
                    } else {
                        // invalid state
                        newState = "initial invalid";
                        break;
                    }
                    break;
                case waitStart:
                    if (moveToState.equals("warnStartGlucose")){
                        participant.timeoutwaitStartTowarnStartGlucose();
                        newState = "warnStartGlucose";
                    } else if (moveToState.equals("startReading")) {
                        participant.receivedStartGlucose();
                        newState = "startGlucose";
                    } else if (moveToState.equals("endReadGlucoseProtocol")){
                        participant.receivedEndProtocol();
                        newState = "endReadGlucoseProtocol";
                    } else {
                        // invalid state
                        newState = "waitstart invalid";
                        break;
                    }
                    break;
                case warnStartGlucose:
                    if (moveToState.equals("startReading")) {
                        participant.receivedStartGlucose();
                        // Launcher.dbEngine.saveStartCalTime(participantId, timestamp);
                        newState = "startReading";
                    } else if (moveToState.equals("missedStart")) {
                        participant.timeoutwarnStartGlucoseTomissedStart();
                        newState = "missedStart";
                    } else if (moveToState.equals("endReadGlucoseProtocol")){
                        participant.receivedEndProtocol();
                        newState = "endReadGlucoseProtocol";
                    } else {
                        // invalid state
                        newState = "warnstart invalid";
                        break;
                    }
                    break;
                case startReading:
                    if (moveToState.equals("notifyAdmin")){ 
                        participant.receivedError();
                        // Launcher.dbEngine.saveStartCalTime(participantId, timestamp);
                        newState = "notifyAdmin";
                    } else if (moveToState.equals("finishedReading")){
                        participant.receivedEndConnection();
                        newState = "finishedReading";
                    } else if (moveToState.equals("endReadGlucoseProtocol")){
                        participant.receivedEndProtocol();
                        newState = "endReadGlucoseProtocol";
                    } else {
                        newState = "startReading invalid";
                        // invalid state
                        break;
                    }
                    break;
                case missedStart:
                    if (moveToState.equals("notifyAdmin")){ 
                        // nothing needs to happen here because it will move to next state immediately
                        newState = "notifyAdmin";
                    } else {
                        // invalid state
                        newState = "notifyAdmin invalid";
                        break;
                    }
                    break;
                case notifyAdmin:
                    if (moveToState.equals("endOfEpisode")){ 
                        // nothing needs to happen here because it will move to next state immediately
                        newState = "endOfEpisode";
                    } else {
                        // invalid state
                        newState = "notifyAdmin invalid";
                        break;
                    }
                    break;
                case finishedReading:
                    if (moveToState.equals("endOfEpisode")) {
                        // nothing needs to happen here because it will move to next state immediately
                        newState = "endOfEpisode";
                    } else {
                        // invalid state
                        newState = "finishedReading invalid";
                        break;
                    }
                    break;
                case endOfEpisode:
                    if (moveToState.equals("waitStart")){
                        participant.timeoutendOfEpisodeTowaitStart();
                        newState = "waitStart";
                    } else if (moveToState.equals("endReadGlucoseProtocol")){
                        participant.receivedEndProtocol();
                        newState = "endReadGlucoseProtocol";
                    } else {
                        // invalid state
                        newState = "endOfEpisode invalid";
                        break;
                    }
                    break;
                case endReadGlucoseProtocol:
                    // invalid state
                    newState = "endReadGlucoseProtocol invalid";
                    break;
                default:
                    // invalid currentState
                    newState = "default invalid";
                    break;
            }
        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("moveToState");
            logger.error(exceptionAsString);
        }
        return newState;
    }

    public void receivedGlucoseMessage(String participantUUID, Map<String, String> decoded){
        try{
            synchronized (lockGlucoseMessage) {
                if(readGlucoseMap.containsKey(participantUUID)) {
                    readGlucoseMap.get(participantUUID).handleGlucoseMessage(decoded);
                }
            }
        } catch (Exception ex){
            ex.printStackTrace();
        }

    }

    public void updateTimeZone(String participantId, String tz) {
        synchronized (lockReadGlucose) {
            ReadGlucose toUpdate = readGlucoseMap.get(participantId);
            if (toUpdate == null) {
                logger.warn("Cannot update timezone, participant not in study.");
            } else {
                logger.warn(participantId + ": changed TZ from " + toUpdate.TZHelper.getUserTimezone() + " to " + tz);
                toUpdate.TZHelper.setUserTimezone(tz);
            }
        }
    }

    public void resetStateMachine(String participantId){
        synchronized (lockReadGlucose) {
            // Remove participant from protocol
            ReadGlucose removed = readGlucoseMap.remove(participantId);
            if (removed != null) {
                removed.receivedEndProtocol();
                removed.uploadSave.shutdownNow();
                removed = null;
                System.gc();
            }

            //restart at beginning
            List<Map<String,String>> participantMapList = Launcher.dbEngine.getParticipantMapByGroup("Default", "ReadGlucose");
            //Create person
            Map<String, String> addMap = getHashMapByParticipantUUID(participantMapList, participantId);
            ReadGlucose p0 = new ReadGlucose(addMap);

            p0.restoreSaveState(true);
            readGlucoseMap.put(participantId, p0);
        }
    }

    class startReadGlucose extends TimerTask {
        private final Logger logger;
        private List<Map<String,String>> previousMapList;
        public startReadGlucose() {
            logger = LoggerFactory.getLogger(startReadGlucose.class);
        }

        public void run() {
            try {
               synchronized (lockEpisodeReset) {
                   List<Map<String,String>> participantMapList = Launcher.dbEngine.getParticipantMapByGroup("Default", "ReadGlucose");
                   if (previousMapList == null){
                        //first run
                        previousMapList = participantMapList;
                    }

                    if (previousMapList.size() > 0 && participantMapList.size() == 0){
                        // clear anyone in previousMapList
                        for (Map<String,String> previousMap: previousMapList){
                            ReadGlucose toRemove = readGlucoseMap.remove(previousMap.get("participant_uuid"));
                            if(toRemove != null){
                                toRemove.receivedEndProtocol();
                                toRemove = null;
                                System.gc();
                            }
                        }
                    }

                   for (Map<String, String> participantMap : participantMapList) {
                       boolean isActive = false;
                       synchronized (lockReadGlucose) {
                           if(!readGlucoseMap.containsKey(participantMap.get("participant_uuid"))) {
                               isActive = true;
                           } else if (!previousMapList.equals(participantMapList)) {
                                // figure out who go removed
                                // find which participant is in previousMapList but not in participantMapList
                                for (Map<String, String> previousMap : previousMapList) {
                                    if (!participantMapList.contains(previousMap)) {
                                        // removing participant
                                        ReadGlucose toRemove = readGlucoseMap.remove(previousMap.get("participant_uuid"));
                                        if(toRemove != null){
                                            toRemove.receivedEndProtocol();
                                            toRemove = null;
                                            System.gc();
                                        }
                                    }
                                }
                            }
                       }

                       if(isActive) {
                           logger.info("Creating state machine for participant_uuid=" + participantMap.get("participant_uuid"));
                           //Create person
                           ReadGlucose p0 = new ReadGlucose(participantMap);

                           logger.info("Restoring State for participant_uuid=" + participantMap.get("participant_uuid"));
                           p0.restoreSaveState(false);

                           synchronized (lockReadGlucose) {
                               readGlucoseMap.put(participantMap.get("participant_uuid"), p0);
                           }
                       }

                   }

               }

            } catch (Exception ex) {
                StringWriter sw = new StringWriter();
                ex.printStackTrace(new PrintWriter(sw));
                String exceptionAsString = sw.toString();
                ex.printStackTrace();
                logger.error("startProtocols");
                logger.error(exceptionAsString);
            }
        }
    }

    public Map<String, String> getHashMapByParticipantUUID(List<Map<String, String>> list, String participantUUID) {
        for (Map<String, String> map : list) {
            if (map.containsKey("participant_uuid") && map.get("participant_uuid").equals(participantUUID)) {
                return map;
            }
        }
        return new HashMap<>();
    }

} //class
