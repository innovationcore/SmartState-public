package SmartState.Webapi;

import SmartState.Launcher;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.google.gson.Gson;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import javax.inject.Inject;
import javax.ws.rs.*;
import javax.ws.rs.core.MediaType;
import javax.ws.rs.core.MultivaluedMap;
import javax.ws.rs.core.Response;
import java.io.PrintWriter;
import java.io.StringWriter;
import java.util.*;


@Path("/api")
public class API {

    @Inject
    private javax.inject.Provider<org.glassfish.grizzly.http.server.Request> request;
    private final Gson gson;
    private final Logger logger = LoggerFactory.getLogger(API.class);

    public API() {
        gson = new Gson();
    }

    @POST
    @Path("/incoming")
    @Consumes({MediaType.APPLICATION_FORM_URLENCODED})
    @Produces(MediaType.APPLICATION_JSON)
    public Response incomingText(MultivaluedMap<String, String> formParams) {
        String responseString = null;
        try {

            String messageId;
            Map<String, String> participantIds = Launcher.dbEngine.getParticipantIdFromPhoneNumber(formParams.get("From").get(0));

            if (!participantIds.isEmpty()) {
                String messageDirection = "incoming";

                Map<String, String> formsMap = convertMultiToRegularMap(formParams);
                String json_string = gson.toJson(formsMap);

                // get protocols
                List<String> studies = new ArrayList<>();
                for (Map.Entry<String, String> entry : participantIds.entrySet()) {
                    if (entry.getKey().equals("ADMIN")){
                        continue;
                    }
                    studies.add(Launcher.dbEngine.getStudyFromParticipantId(entry.getValue()));
                }

                for (String study : studies) {
                    messageId = UUID.randomUUID().toString();
                    String participantId = participantIds.get(study);

                    String insertQuery = "INSERT INTO messages " +
                            "(message_uuid, participant_uuid, ts, message_direction, study, message_json)" +
                            " VALUES ('" + messageId + "', '" +
                            participantId + "' , NOW(), '" +
                            messageDirection + "', '" + study + "', '" + json_string +
                            "')";

                    //record incoming
                    Launcher.dbEngine.executeUpdate(insertQuery);

                    //send to state machine
                    Map<String, List<String>> protocol = Launcher.dbEngine.getProtocolFromParticipantId(participantId);
                    switch (study) {
                        case "Default":
                            if (protocol.get(study).contains("ReadGlucose")) {
                                Launcher.readGlucoseWatcher.incomingText(participantId, formsMap);
                            }
                            if (protocol.get(study).contains("Survey")) {
                                // do nothing, survey doesn't receive text messages
                            }
                            if (protocol.get(study).isEmpty()) {
                                logger.error("Text from participant not enrolled in any Default protocol");
                            }
                            break;

                        default:
                            logger.error("Text from participant not enrolled in any study");
                            break;
                    }

                    Map<String, String> response = new HashMap<>();
                    response.put("status", "ok");
                    responseString = gson.toJson(response);
                }

            } else {
                Map<String,String> response = new HashMap<>();
                response.put("status","error");
                response.put("status_desc","participant not found");
                responseString = gson.toJson(response);
            }
        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("incomingText");
            logger.error(exceptionAsString);

            return Response.status(500).entity(exceptionAsString).build();
        }
        //return accesslog data
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    // Received the LoRaWAN messages from the MQTT listener
    @POST
    @Path("/message")
    @Consumes({MediaType.APPLICATION_FORM_URLENCODED})
    @Produces(MediaType.APPLICATION_JSON)
    public Response incomingMessage(MultivaluedMap<String, String> formParams) {
        String responseString = null;
        try {
            String messageId;
            Map<String, String> participantIds = Launcher.dbEngine.getParticipantIdFromPhoneNumber(formParams.get("From").get(0));

            if (!participantIds.isEmpty()) {
                String messageDirection = "incoming";

                Map<String, String> formsMap = convertMultiToRegularMap(formParams);
                String json_string = gson.toJson(formsMap);

                // get protocols
                List<String> studies = new ArrayList<>();
                for (Map.Entry<String, String> entry : participantIds.entrySet()) {
                    if(entry.getKey().equals("ADMIN")){
                        continue;
                    }
                    studies.add(Launcher.dbEngine.getStudyFromParticipantId(entry.getValue()));
                }

                for (String study : studies) {
                    messageId = UUID.randomUUID().toString();
                    String participantId = participantIds.get(study);

                    String insertQuery = "INSERT INTO messages " +
                            "(message_uuid, participant_uuid, ts, message_direction, study, message_json)" +
                            " VALUES ('" + messageId + "', '" +
                            participantId + "' , NOW(), '" +
                            messageDirection + "', '" + study + "', '" + json_string +
                            "')";

                    //record incoming
                    Launcher.dbEngine.executeUpdate(insertQuery);

                    //send to state machine
                    Map<String, List<String>> protocol = Launcher.dbEngine.getProtocolFromParticipantId(participantId);
                    switch (study) {
                        case "Default":
                            if (protocol.get(study).contains("ReadGlucose")) {
                                Launcher.readGlucoseWatcher.incomingMessage(participantId, formsMap);
                            }
                            if (protocol.get(study).contains("Survey")) {
                                // do nothing, survey doesn't receive LoRaWAN messages
                            }
                            if (protocol.get(study).isEmpty()) {
                                logger.error("LoRa message from participant not enrolled in any Default protocol");
                            }
                            break;

                        default:
                            logger.error("LoRa message from participant not enrolled in any study");
                            break;
                    }

                    Map<String, String> responce = new HashMap<>();
                    responce.put("status", "ok");
                    responseString = gson.toJson(responce);
                }

            } else {
                Map<String,String> responce = new HashMap<>();
                responce.put("status","error");
                responce.put("status_desc","participant not found");
                responseString = gson.toJson(responce);
            }

        } catch (Exception ex) {

            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("incomingMessage");
            logger.error(exceptionAsString);

            return Response.status(500).entity(exceptionAsString).build();
        }
        //return accesslog data
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    @GET
    @Path("/check")
    @Produces(MediaType.APPLICATION_JSON)
    public Response getAccessLog() {
        String responseString;
        try {

            //get remote ip address from request
            String remoteIP = request.get().getRemoteAddr();
            //get the timestamp of the request
            long access_ts = System.currentTimeMillis();
            logger.info("IP: " + remoteIP + " Timestamp: " + access_ts);

            Map<String,String> responseMap = new HashMap<>();
            responseMap.put("ip", remoteIP);
            responseMap.put("timestamp", String.valueOf(access_ts));

            responseString = gson.toJson(responseMap);


        } catch (Exception ex) {

            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();

            return Response.status(500).entity(exceptionAsString).build();
        }
        //return accesslog data
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    @GET
    @Path("/get-valid-next-states/{participant_uuid}/{participant_study}/{participant_protocol}")
    @Produces(MediaType.APPLICATION_JSON)
    public Response getNextStates(@PathParam("participant_uuid") String participantId,
                                  @PathParam("participant_study") String participantStudy,
                                  @PathParam("participant_protocol") String participantProtocol) {
        String responseString;
        try {
            if (!participantId.equals("")) {
                // this returns a comma delimited list as a string
                String validNextStates = "";
                if (participantStudy.equals("Default")) {
                    if (participantProtocol.equals("ReadGlucose")) {
                        validNextStates = Launcher.readGlucoseWatcher.getValidNextStates(participantId);
                    } else if (participantProtocol.equals("Survey")) {
                        validNextStates = Launcher.surveyWatcher.getValidNextStates(participantId);
                    }
                }

                Map<String,String> response = new HashMap<>();
                response.put("status","ok");
                response.put("valid_states", validNextStates);
                responseString = gson.toJson(response);

            } else {
                Map<String,String> response = new HashMap<>();
                response.put("status","error");
                response.put("status_desc","participant not found");
                responseString = gson.toJson(response);
            }

        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("getNextStates");
            logger.error(exceptionAsString);
            return Response.status(500).entity(exceptionAsString).build();
        }
        //return state moved to
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    @POST
    @Consumes(MediaType.APPLICATION_FORM_URLENCODED)
    @Path("/next-state")
    @Produces(MediaType.APPLICATION_JSON)
    public Response moveToNextState(MultivaluedMap<String, String> data) {

        String participantId = data.get("participantUUID").get(0);
        String nextState = data.get("toState").get(0);
        String time = data.get("time").get(0);
        String study = data.get("study").get(0);
        String protocol = data.get("protocol").get(0);
        String responseString = "";
        try {

            if (participantId != null) {
                //send to state machine
                String newState = "";
                if (study.equals("Default")) {
                    if (protocol.equals("ReadGlucose")) {
                        newState = Launcher.readGlucoseWatcher.moveToState(participantId, nextState, time);
                    } else if (protocol.equals("Survey")) {
                        newState = Launcher.surveyWatcher.moveToState(participantId, nextState, time);
                    }
                }

                Map<String,String> response = new HashMap<>();
                response.put("status","ok");
                response.put("moved_to_state", newState);
                responseString = gson.toJson(response);

            } else {
                Map<String,String> response = new HashMap<>();
                response.put("status","error");
                response.put("status_desc","participant not found");
                responseString = gson.toJson(response);
            }

        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("moveToNextState");
            logger.error(exceptionAsString);

            return Response.status(500).entity(exceptionAsString).build();
        }
        //return state moved to
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }


    @POST
    @Path("/reset-machine")
    @Consumes(MediaType.APPLICATION_FORM_URLENCODED)
    @Produces(MediaType.APPLICATION_JSON)
    public Response resetStateMachine(MultivaluedMap<String, String> data) {

        String responseString;
        try {
            String participantId = data.get("uuid").get(0);
            String study = data.get("study").get(0);
            String protocol = data.get("protocol").get(0);
            if (participantId != null) {
                if (study.equals("Default")) {
                    if (protocol.equals("ReadGlucose")) {
                        Launcher.readGlucoseWatcher.resetStateMachine(participantId);
                    } else if (protocol.equals("Survey")) {
                        Launcher.surveyWatcher.resetStateMachine(participantId);
                    } else {
                        logger.error("Cannot reset machine, participant not in an active protocol.");
                    }
                }

                Map<String,String> response = new HashMap<>();
                response.put("status", "ok");
                responseString = gson.toJson(response);

            } else {
                Map<String,String> response = new HashMap<>();
                response.put("status","error");
                response.put("status_desc","participant not found");
                responseString = gson.toJson(response);
            }

        } catch (Exception ex) {

            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("resetStateMachine");
            logger.error(exceptionAsString);

            return Response.status(500).entity(exceptionAsString).build();
        }
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    @POST
    @Path("/update-timezone")
    @Consumes(MediaType.APPLICATION_FORM_URLENCODED)
    @Produces(MediaType.APPLICATION_JSON)
    public Response updateTimeZone(MultivaluedMap<String, String> data) {

        String responseString;
        try {
            String participantId = data.get("uuid").get(0);
            String tz = data.getFirst("tz");
            String study = data.getFirst("study");
            String protocolsJson = data.getFirst("protocols");
            ObjectMapper objectMapper = new ObjectMapper();
            List<String> protocols = objectMapper.readValue(protocolsJson, List.class);
            if (participantId != null) {
                for(int i = 0; i < protocols.size(); i++) {
                    if (study.equals("Default")) {
                        if (protocols.get(i).equals("ReadGlucose")) {
                            Launcher.readGlucoseWatcher.updateTimeZone(participantId, tz);
                        } else if (protocols.get(i).equals("Survey")) {
                            Launcher.surveyWatcher.updateTimeZone(participantId, tz);
                        }
                    }
                }

                Map<String,String> response = new HashMap<>();
                response.put("status", "ok");
                responseString = gson.toJson(response);

            } else {
                Map<String, String> response = new HashMap<>();
                response.put("status", "error");
                response.put("status_desc", "participant not found");
                responseString = gson.toJson(response);
            }
        } catch (Exception ex) {
            StringWriter sw = new StringWriter();
            ex.printStackTrace(new PrintWriter(sw));
            String exceptionAsString = sw.toString();
            ex.printStackTrace();
            logger.error("updateTimeZone");
            logger.error(exceptionAsString);

            return Response.status(500).entity(exceptionAsString).build();
        }
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    private Map<String, String> convertMultiToRegularMap(MultivaluedMap<String, String> m) {
        Map<String, String> map = new HashMap<>();
        if (m == null) {
            return map;
        }
        for (Map.Entry<String, List<String>> entry : m.entrySet()) {
            StringBuilder sb = new StringBuilder();
            for (String s : entry.getValue()) {
                if (sb.length() > 0) {
                    sb.append(',');
                }
                sb.append(s);
            }
            map.put(entry.getKey(), sb.toString());
        }
        return map;
    }
}
