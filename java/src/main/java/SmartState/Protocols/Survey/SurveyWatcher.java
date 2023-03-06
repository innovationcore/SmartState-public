package SmartState.Protocols.Survey;

import SmartState.Launcher;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.PrintWriter;
import java.io.StringWriter;
import java.util.*;
import java.util.concurrent.atomic.AtomicBoolean;

public class SurveyWatcher {
    private final Logger logger;
    private final AtomicBoolean lockSurvey = new AtomicBoolean();
    private final AtomicBoolean lockEpisodeReset = new AtomicBoolean();
    private final Map<String,Survey> surveyMap;

    public SurveyWatcher() {
        this.logger = LoggerFactory.getLogger(SurveyWatcher.class);
        this.surveyMap = Collections.synchronizedMap(new HashMap<>());

        //how long to wait before checking protocols
        long checkdelay = Launcher.config.getLongParam("checkdelay", 5000L);
        long checktimer = Launcher.config.getLongParam("checktimer", 30000L);

        //create timer
        Timer checkTimer = new Timer();
        //set timer
        checkTimer.scheduleAtFixedRate(new startSurvey(), checkdelay, checktimer);
    }

    public void incomingText(String participantId, Map<String,String> incomingMap) {
        try {
            //From
            logger.info("Incoming number: " + incomingMap.get("From") + " parid: " + participantId);

            synchronized (lockSurvey) {
                if(surveyMap.containsKey(participantId)) {
                    surveyMap.get(participantId).incomingText(incomingMap);
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

    public String getValidNextStates(String partUUID){
        String validNextStates = "";
        
        try {
            String currentState = Launcher.dbEngine.getParticipantCurrentState(partUUID, "Survey");

            switch (currentState){
                case "initial":
                    validNextStates = "waitFor6pm,waitForNoon,endSurveyProtocol";
                    break;
                case "waitForNoon":
                    validNextStates = "noonSurvey,endSurveyProtocol";
                    break;
                case "noonSurvey":
                    validNextStates = "waitFor6pm";
                    break;
                case "waitFor6pm":
                    validNextStates = "survey6pm,endSurveyProtocol";
                    break;
                case "survey6pm":
                    validNextStates = "waitForNoon";
                    break;
                case "endSurveyProtocol":
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

    public String moveToState(String participantId, String moveToState) {
        String newState = "";
        try {
            Survey participant = surveyMap.get(participantId);
            switch (participant.getState()){
                case initial:
                    if (moveToState.equals("waitFor6pm")){
                        participant.isBefore6pm();
                        newState = "waitFor6pm";
                    } else if (moveToState.equals("waitForNoon")) {
                        participant.isBeforeNoon();
                        newState = "waitForNoon";
                    } else if (moveToState.equals("endSurveyProtocol")) {
                        participant.receivedEndProtocol();
                        newState = "endSurveyProtocol";
                    } else {
                        // invalid state
                        newState = "initial invalid";
                        break;
                    }
                    break;
                case waitForNoon:
                    if (moveToState.equals("noonSurvey")){
                        participant.timeoutwaitForNoonTonoonSurvey();
                        newState = "noonSurvey";
                    } else if (moveToState.equals("endSurveyProtocol")) {
                        participant.receivedEndProtocol();
                        newState = "endSurveyProtocol";
                    } else {
                        // invalid state
                        newState = "waitForNoon invalid";
                        break;
                    }
                    break;
                case noonSurvey:
                    // invalid state
                    newState = "noonSurvey invalid";
                    break;
                case waitFor6pm:
                    if (moveToState.equals("survey6pm")){ 
                        participant.timeoutwaitFor6pmTosurvey6pm();
                        newState = "survey6pm";
                    } else if (moveToState.equals("endSurveyProtocol")) {
                        participant.receivedEndProtocol();
                        newState = "endSurveyProtocol";
                    } else {
                        newState = "waitFor6pm invalid";
                        // invalid state
                        break;
                    }
                    break;
                case survey6pm:
                    //invalid state
                    newState = "survey6pm invalid";
                    break;
                case endSurveyProtocol:
                    // invalid state
                    newState = "endSurveyProtocol invalid";
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

    class startSurvey extends TimerTask {
        private final Logger logger;
        private List<Map<String,String>> previousMapList;
        public startSurvey() {
            logger = LoggerFactory.getLogger(startSurvey.class);
        }

        public void run() {
            try {
               synchronized (lockEpisodeReset) {
                   List<Map<String,String>> participantMapList = Launcher.dbEngine.getParticipantMapByGroup("Survey");
                   if (previousMapList == null){
                        //first run
                        previousMapList = participantMapList;
                    }

                   if (previousMapList.size() > 0 && participantMapList.size() == 0){
                        // clear anyone in previousMapList
                        for (Map<String,String> previousMap: previousMapList){
                            Survey toRemove = surveyMap.remove(previousMap.get("participant_uuid"));
                            if(toRemove != null){
                                toRemove.receivedEndProtocol();
                                toRemove = null;
                                System.gc();
                            }
                        }
                    }

                   for (Map<String, String> participantMap : participantMapList) {

                       boolean isActive = false;
                       synchronized (lockSurvey) {
                           if(!surveyMap.containsKey(participantMap.get("participant_uuid"))) {
                               isActive = true;
                           } else if (!previousMapList.equals(participantMapList)) {
                                // figure out who go removed
                                // find which participant is in previousMapList but not in participantMapList
                                for (Map<String, String> previousMap : previousMapList) {
                                    if (!participantMapList.contains(previousMap)) {
                                        // removing participant
                                        Survey toRemove = surveyMap.remove(previousMap.get("participant_uuid"));
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
                           Survey p0 = new Survey(participantMap);

                           logger.info("Restoring State for participant_uuid=" + participantMap.get("participant_uuid"));
                           p0.restoreSaveState();

                           synchronized (lockSurvey) {
                               surveyMap.put(participantMap.get("participant_uuid"), p0);
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

} //class
