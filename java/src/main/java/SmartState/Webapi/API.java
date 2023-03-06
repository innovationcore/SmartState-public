package SmartState.Webapi;

import SmartState.Launcher;
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

        String responseString;
        try {

            String messageId = UUID.randomUUID().toString();
            String participantId = Launcher.dbEngine.getParticipantIdFromPhoneNumber(formParams.get("From").get(0));

            if (participantId != null) {
                String messageDirection = "incoming";

                Map<String, String> formsMap = convertMultiToRegularMap(formParams);
                String json_string = gson.toJson(formsMap);
                logger.info("incomingTextMap: " + json_string);

                String insertQuery = "INSERT INTO messages " +
                        "(message_uuid, participant_uuid, TS, message_direction, message_json)" +
                        " VALUES ('" + messageId + "', '" +
                        participantId + "' , GETUTCDATE(), '" +
                        messageDirection + "', '" + json_string +
                        "')";

                //record incoming
                Launcher.dbEngine.executeUpdate(insertQuery);

                //send to state machine
                ArrayList<String> enrollments = Launcher.dbEngine.getEnrollmentUUID(participantId);
                for (String enrollment: enrollments) {
                    if (Launcher.dbEngine.getEnrollmentName(enrollment).equals("Survey")) {
                        Launcher.surveyWatcher.incomingText(participantId, formsMap);
                    } else {
                        Launcher.readGlucoseWatcher.incomingText(participantId, formsMap);
                    }
                }
                

                Map<String,String> responce = new HashMap<>();
                responce.put("status","ok");
                responseString = gson.toJson(responce);

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
            logger.error("incomingText");
            logger.error(exceptionAsString);

            return Response.status(500).entity(exceptionAsString).build();
        }
        //return accesslog data
        return Response.ok(responseString).header("Access-Control-Allow-Origin", "*").build();
    }

    @POST
    @Path("/message")
    @Consumes({MediaType.APPLICATION_FORM_URLENCODED})
    @Produces(MediaType.APPLICATION_JSON)
    public Response incomingMessage(MultivaluedMap<String, String> formParams) {

        String responseString;
        try {

            String messageId = UUID.randomUUID().toString();
            String participantId = Launcher.dbEngine.getParticipantIdFromPhoneNumber(formParams.get("From").get(0));

            if (participantId != null) {
                String messageDirection = "incoming";

                Map<String, String> formsMap = convertMultiToRegularMap(formParams);
                String json_string = gson.toJson(formsMap);

                String insertQuery = "INSERT INTO messages " +
                        "(message_uuid, participant_uuid, TS, message_direction, message_json)" +
                        " VALUES ('" + messageId + "', '" +
                        participantId + "' , GETUTCDATE(), '" +
                        messageDirection + "', '" + json_string +
                        "')";

                //record incoming
                Launcher.dbEngine.executeUpdate(insertQuery);

                //send to state machine
                ArrayList<String> enrollments = Launcher.dbEngine.getEnrollmentUUID(participantId);
                for (String enrollment: enrollments){
                    String enrollName = Launcher.dbEngine.getEnrollmentName(enrollment);
                    if (enrollName.equals("Survey")){
                        Launcher.surveyWatcher.incomingText(participantId, formsMap);
                    } else if (enrollName.equals("ReadGlucose")){
                        Launcher.readGlucoseWatcher.incomingMessage(participantId, formsMap);
                    }
                }
                
                Map<String,String> responce = new HashMap<>();
                responce.put("status","ok");
                responseString = gson.toJson(responce);

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
    @Path("/get-valid-next-states")
    @Produces(MediaType.APPLICATION_JSON)
    public Response getNextStates(  @QueryParam("participant_uuid") String participantId, 
                                    @QueryParam("protocol") String protocol) {
        String responseString;
        try {
            if (!participantId.equals("")) {
                // this returns a comma delimited list as a string
                String validNextStates = "";
                if (protocol.equals("Survey")){
                    validNextStates = Launcher.surveyWatcher.getValidNextStates(participantId);
                } else if (protocol.equals("ReadGlucose")){
                    validNextStates = Launcher.readGlucoseWatcher.getValidNextStates(participantId);
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

    @GET
    @Path("/next-state")
    @Produces(MediaType.APPLICATION_JSON)
    public Response moveToNextState(@QueryParam("participantUUID") String participantId,
                                    @QueryParam("toState") String nextState,
                                    @QueryParam("protocol") String protocol) {
        String responseString;
        try {

            if (participantId != null) {
                //send to state machine
                String newState = "";
                if (protocol.equals("Survey")){
                    newState = Launcher.surveyWatcher.moveToState(participantId, nextState);
                } else if (protocol.equals("ReadGlucose")){
                    newState = Launcher.readGlucoseWatcher.moveToState(participantId, nextState);
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
