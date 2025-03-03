package SmartState;

import SmartState.Configs.Config;
import SmartState.Configs.FileConfig;
import SmartState.Database.DBEngine;
import SmartState.LoRaWAN.Listener;
import SmartState.MessagingUtils.MsgUtils;
import SmartState.Webapi.LlmConnector;
import SmartState.Protocols.ReadGlucose.ReadGlucoseWatcher;
import SmartState.Protocols.Survey.SurveyWatcher;
import SmartState.Protocols.Testing;
import SmartState.MessagingUtils.MessageSchedulerExecutor;
import org.glassfish.grizzly.http.server.HttpServer;
import org.glassfish.jersey.grizzly2.httpserver.GrizzlyHttpServerFactory;
import org.glassfish.jersey.server.ResourceConfig;

import javax.ws.rs.core.UriBuilder;
import java.io.File;
import java.io.IOException;
import java.net.URI;
import java.net.URISyntaxException;
import java.nio.file.Paths;
import java.util.HashMap;
import java.util.Map;


public class Launcher {
    public static Config config;
    public static DBEngine dbEngine;
    public static String surveyURL;
    public static MsgUtils msgUtils;
    public static LlmConnector llmConnector;
    public static ReadGlucoseWatcher readGlucoseWatcher;
    public static SurveyWatcher surveyWatcher;
    public static Testing testing;
    public static Listener listener;
    public static String[] LoRaTopics = {"join", "up", "down", "multidown", "error", "ack"};

    public static void main(String[] argv) {

        try {

            //get config info
            String configPath = "config.ini";
            Map<String, Object> fileConfigMap;
            fileConfigMap = initConfigMap(configPath);
            config = new Config(fileConfigMap);
            surveyURL = config.getStringParam("survey_url");
            testing = new Testing();

            //init db engine
            dbEngine = new DBEngine();

            //init message utils
            msgUtils = new MsgUtils();

            //init LLM connector
            llmConnector = new LlmConnector();

            // init MQTT LoRaWAN listener
            //startListener();

            //Embedded HTTP initialization
            startServer();

            // start protocols
            readGlucoseWatcher = new ReadGlucoseWatcher();
            surveyWatcher = new SurveyWatcher();

            // start watching the queued messages database
            MessageSchedulerExecutor.startWatcher();

        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    private static void startServer() {

        final ResourceConfig rc = new ResourceConfig()
                .packages("SmartState.Webapi");

        System.out.println("Starting Web Server...");
        int web_port = config.getIntegerParam("web_port",9000);
        URI BASE_URI = UriBuilder.fromUri("http://0.0.0.0/").port(web_port).build();
        HttpServer httpServer = GrizzlyHttpServerFactory.createHttpServer(BASE_URI, rc);

        try {
            httpServer.start();
            System.out.println("Web Server Started...");
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private static void startListener() throws URISyntaxException {
        listener = new Listener(
                config.getStringParam("activeMQTT_user"),
                config.getStringParam("activeMQTT_pass"),
                config.getStringParam("activeMQTT_host"),
                config.getIntegerParam("activeMQTT_port")
        );

        for (String dest : LoRaTopics){
            listener.subscribeToTopic(config.getStringParam("topic_prefix") + dest);
        }
    }

    private static Map<String,Object> initConfigMap(String configName) {
        Map<String, Object> configParams = null;
        try {

            configParams = new HashMap<>();

            File configFile = Paths.get(configName).toFile();
            FileConfig config;
            if (configFile.isFile()) {
                //config.SmartState.Config
                config = new FileConfig(configFile.getAbsolutePath());
                configParams = config.getConfigMap();

            }

        } catch (Exception ex) {
            ex.printStackTrace();
            System.exit(0);
        }
        return configParams;
    }
} //main class
