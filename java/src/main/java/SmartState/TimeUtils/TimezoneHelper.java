package SmartState.TimeUtils;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.time.*;
import java.time.format.DateTimeFormatter;

public class TimezoneHelper {
    final int SEC_IN_DAY = 86400;
    private final String userTimezone;
    private final String machineTimezone;

    /**
    * initialize the timezone helper with the user's timezone and the machine's timezone (in seconds)
    */
    public TimezoneHelper(String userTimezone, String machineTimezone) {
        this.userTimezone = userTimezone;
        this.machineTimezone = machineTimezone;

        Integer timezoneDifference = calculateTZOffset();
        Logger logger = LoggerFactory.getLogger(TimezoneHelper.class.getName());
        logger.info("TimezoneHelper initialized with user timezone: " + userTimezone + " and machine timezone: " + machineTimezone + " and timezone difference: " + timezoneDifference);
    }

    /**
    * return the timezone difference in seconds
    * - if behind, + if ahead
    */
    public Integer calculateTZOffset() {

        LocalDateTime dt = LocalDateTime.now();
		ZonedDateTime fromZonedDateTime = dt.atZone(ZoneId.of(this.userTimezone));
		ZonedDateTime toZonedDateTime = dt.atZone(ZoneId.of(this.machineTimezone));
		long diff = Duration.between(fromZonedDateTime, toZonedDateTime).getSeconds();
        return (int) (diff);
    }

    /**
    * return the date string from adding seconds
    */
    public String getDateFromAddingSeconds(long seconds) {
        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        nowUserTimezone = nowUserTimezone.plusSeconds(seconds);
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss z");
        return nowUserTimezone.format(formatter);
    }

    /**
    * return the seconds until Noon for user timezone
    */
    public int getSecondsTo12pm(boolean nextDay) {
        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();
        LocalDateTime userLocalTimeNoon = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), 12, 0, 0);
        long secondsUntilNoon = Duration.between(nowUserLocalTime, userLocalTimeNoon).getSeconds();
        if (secondsUntilNoon < 0 && nextDay) {
            secondsUntilNoon += SEC_IN_DAY;
        }
        return (int) secondsUntilNoon;
    }

    /**
    * return the seconds until Noon for user timezone
    */
    public int getSecondsTo6pm() {
        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();
        LocalDateTime userLocalTime6pm = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), 18, 0, 0);
        long secondsUntil6pm = Duration.between(nowUserLocalTime, userLocalTime6pm).getSeconds();
        return (int) secondsUntil6pm;
    }

    public boolean isTimeWithinFirst15MinutesOfHour(long unixTS) {
        Instant userUTC = Instant.ofEpochMilli(unixTS*1000L);
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime oldUserTimezone = ZonedDateTime.ofInstant(userUTC, userTZ);
        LocalDateTime oldUserLocalTime = oldUserTimezone.toLocalDateTime();

        Instant nowUTC = Instant.now();
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();

        LocalDateTime userLocalTimeHour = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 0, 0);
        LocalDateTime userLocalTimeHour15 = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 15, 0);

        long secondsUntilHour = Duration.between(oldUserLocalTime, userLocalTimeHour).getSeconds();
        long secondsUntilHour15 = Duration.between(oldUserLocalTime, userLocalTimeHour15).getSeconds();
        return secondsUntilHour <= 0 && secondsUntilHour15 >= 0;
    }

    public boolean isTimeWithinFirst1530MinutesOfHour(long unixTS) {
        Instant userUTC = Instant.ofEpochMilli(unixTS*1000L);
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime oldUserTimezone = ZonedDateTime.ofInstant(userUTC, userTZ);
        LocalDateTime oldUserLocalTime = oldUserTimezone.toLocalDateTime();

        Instant nowUTC = Instant.now();
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();

        LocalDateTime userLocalTimeHour15 = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 15, 0);
        LocalDateTime userLocalTimeHour30 = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 30, 0);

        long secondsUntilHour15 = Duration.between(oldUserLocalTime, userLocalTimeHour15).getSeconds();
        long secondsUntilHour30 = Duration.between(oldUserLocalTime, userLocalTimeHour30).getSeconds();
        return secondsUntilHour15 <= 0 && secondsUntilHour30 >= 0;
    }

    public long getSecondsToHour15(){
        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();

        // get the seconds between now and the next hour
        LocalDateTime userLocalTimeHour15 = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 15, 0);
        long secondsUntilHour15 = Duration.between(nowUserLocalTime, userLocalTimeHour15).getSeconds();
        if (secondsUntilHour15 < 0) {
            secondsUntilHour15 = 0;
        }
        return secondsUntilHour15;
    }

    public long getSecondsToHour30(){
        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();

        // get the seconds between now and the next hour
        LocalDateTime userLocalTimeHour30 = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 30, 0);
        long secondsUntilHour30 = Duration.between(nowUserLocalTime, userLocalTimeHour30).getSeconds();
        if (secondsUntilHour30 < 0) {
            secondsUntilHour30 = 0;
        }
        return secondsUntilHour30;
    }

    public long getSecondsUntil2Hours(){
        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();

        // get the seconds between now and the next hour
        LocalDateTime userLocalTime2Hours = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), nowUserLocalTime.getHour(), 0, 0);
        userLocalTime2Hours = userLocalTime2Hours.plusHours(2);

        return Duration.between(nowUserLocalTime, userLocalTime2Hours).getSeconds();
    }

    // is same day <11:59:59pm
    public boolean isSameDay(long lastKnownTime){
        // check if lastKnownTime is on the same day as now and before the next day at 4am

        Instant lastKnownUTC = Instant.ofEpochMilli(lastKnownTime*1000L);
        ZoneId lastKnownTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime lastKnownTimezone = ZonedDateTime.ofInstant(lastKnownUTC, lastKnownTZ);
        LocalDateTime lastKnownLocalTime = lastKnownTimezone.toLocalDateTime();

        Instant nowUTC = Instant.now();
        ZoneId userTZ = ZoneId.of(this.userTimezone);
        ZonedDateTime nowUserTimezone = ZonedDateTime.ofInstant(nowUTC, userTZ);
        LocalDateTime nowUserLocalTime = nowUserTimezone.toLocalDateTime();

        LocalDateTime currentTime12am = LocalDateTime.of(nowUserLocalTime.getYear(), nowUserLocalTime.getMonth(), nowUserLocalTime.getDayOfMonth(), 11, 59, 59);
        long secondsUntil12am = Duration.between(lastKnownLocalTime, currentTime12am).getSeconds();

        return secondsUntil12am <= 86400;
    }

    public boolean isBeforeNoon() {
        int timeToNoon = getSecondsTo12pm(false);
        return timeToNoon > 0;
    } 

    public boolean isBefore6pm() {
        int timeTo6pm = getSecondsTo6pm();
        return timeTo6pm > 0;
    }

}