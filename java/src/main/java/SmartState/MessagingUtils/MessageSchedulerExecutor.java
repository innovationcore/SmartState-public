package SmartState.MessagingUtils;

import SmartState.Launcher;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.logging.Level;
import java.util.logging.Logger;

public class MessageSchedulerExecutor {

    private static final Logger LOGGER = Logger.getLogger(MessageSchedulerExecutor.class.getName());
    private static final ScheduledExecutorService SCHEDULER = Executors.newSingleThreadScheduledExecutor();
    private static final int INITIAL_DELAY = 0;
    private static final int INTERVAL = 5;

    public static void startWatcher() {
        SCHEDULER.scheduleAtFixedRate(MessageSchedulerExecutor::checkDatabase, INITIAL_DELAY, INTERVAL, TimeUnit.SECONDS);
    }

    private static void checkDatabase() {
        try {
            Launcher.dbEngine.checkQueuedMessageDatabase();
        } catch (Exception e) {
            LOGGER.log(Level.SEVERE, "Error checking queued messages", e);
        }
    }
}
