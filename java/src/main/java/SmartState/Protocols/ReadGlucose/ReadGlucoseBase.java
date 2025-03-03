package SmartState.Protocols.ReadGlucose;
//%% NEW FILE ReadGlucoseBase BEGINS HERE %%

/*PLEASE DO NOT EDIT THIS CODE*/
/*This code was generated using the UMPLE 1.35.0.7523.c616a4dce modeling language!*/


import java.util.*;

// line 2 "model.ump"
// line 92 "model.ump"
public class ReadGlucoseBase
{

  //------------------------
  // MEMBER VARIABLES
  //------------------------

  //ReadGlucoseBase Attributes
  private int startDeadline;
  private int startWarnDeadline;
  private int endOfEpisodeDeadline;

  //ReadGlucoseBase State Machines
  public enum State { initial, waitStart, warnStartGlucose, startReading, finishedReading, missedStart, notifyAdmin, endOfEpisode, endReadGlucoseProtocol }
  private State state;

  //Helper Variables
  private TimedEventHandler timeoutwaitStartTowarnStartGlucoseHandler;
  private TimedEventHandler timeoutwarnStartGlucoseTomissedStartHandler;
  private TimedEventHandler timeoutendOfEpisodeTowaitStartHandler;

  //------------------------
  // CONSTRUCTOR
  //------------------------

  public ReadGlucoseBase()
  {
    startDeadline = 0;
    startWarnDeadline = 0;
    endOfEpisodeDeadline = 0;
    setState(State.initial);
  }

  //------------------------
  // INTERFACE
  //------------------------

  public boolean setStartDeadline(int aStartDeadline)
  {
    boolean wasSet = false;
    startDeadline = aStartDeadline;
    wasSet = true;
    return wasSet;
  }

  public boolean setStartWarnDeadline(int aStartWarnDeadline)
  {
    boolean wasSet = false;
    startWarnDeadline = aStartWarnDeadline;
    wasSet = true;
    return wasSet;
  }

  public boolean setEndOfEpisodeDeadline(int aEndOfEpisodeDeadline)
  {
    boolean wasSet = false;
    endOfEpisodeDeadline = aEndOfEpisodeDeadline;
    wasSet = true;
    return wasSet;
  }

  public int getStartDeadline()
  {
    return startDeadline;
  }

  public int getStartWarnDeadline()
  {
    return startWarnDeadline;
  }

  public int getEndOfEpisodeDeadline()
  {
    return endOfEpisodeDeadline;
  }

  public String getStateFullName()
  {
    String answer = state.toString();
    return answer;
  }

  public State getState()
  {
    return state;
  }

  public boolean receivedWaitStart()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.waitStart);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean receivedWarnStart()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.warnStartGlucose);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean receivedStartGlucose()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.startReading);
        wasEventProcessed = true;
        break;
      case waitStart:
        exitState();
        setState(State.startReading);
        wasEventProcessed = true;
        break;
      case warnStartGlucose:
        exitState();
        setState(State.startReading);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean receivedEndofEpisode()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.endOfEpisode);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean receivedEndProtocol()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.endReadGlucoseProtocol);
        wasEventProcessed = true;
        break;
      case waitStart:
        exitState();
        setState(State.endReadGlucoseProtocol);
        wasEventProcessed = true;
        break;
      case warnStartGlucose:
        exitState();
        setState(State.endReadGlucoseProtocol);
        wasEventProcessed = true;
        break;
      case startReading:
        setState(State.endReadGlucoseProtocol);
        wasEventProcessed = true;
        break;
      case endOfEpisode:
        exitState();
        setState(State.endReadGlucoseProtocol);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean timeoutwaitStartTowarnStartGlucose()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case waitStart:
        exitState();
        setState(State.warnStartGlucose);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean timeoutwarnStartGlucoseTomissedStart()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case warnStartGlucose:
        exitState();
        setState(State.missedStart);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean receivedEndConnection()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case startReading:
        setState(State.finishedReading);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean receivedError()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case startReading:
        setState(State.notifyAdmin);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  private boolean __autotransition4397__()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case finishedReading:
        setState(State.endOfEpisode);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  private boolean __autotransition4398__()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case missedStart:
        setState(State.notifyAdmin);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  private boolean __autotransition4399__()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case notifyAdmin:
        setState(State.endOfEpisode);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean timeoutendOfEpisodeTowaitStart()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case endOfEpisode:
        exitState();
        setState(State.waitStart);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  private void exitState()
  {
    switch(state)
    {
      case waitStart:
        stopTimeoutwaitStartTowarnStartGlucoseHandler();
        break;
      case warnStartGlucose:
        stopTimeoutwarnStartGlucoseTomissedStartHandler();
        break;
      case endOfEpisode:
        stopTimeoutendOfEpisodeTowaitStartHandler();
        break;
    }
  }

  private void setState(State aState)
  {
    state = aState;

    // entry actions and do activities
    switch(state)
    {
      case initial:
        // line 11 "model.ump"
        stateNotify("initial");
        break;
      case waitStart:
        // line 23 "model.ump"
        stateNotify("waitStart");
        startTimeoutwaitStartTowarnStartGlucoseHandler();
        break;
      case warnStartGlucose:
        // line 32 "model.ump"
        stateNotify("warnStartGlucose");
        startTimeoutwarnStartGlucoseTomissedStartHandler();
        break;
      case startReading:
        // line 41 "model.ump"
        stateNotify("startReading");
        break;
      case finishedReading:
        // line 50 "model.ump"
        stateNotify("finishedReading");
        __autotransition4397__();
        break;
      case missedStart:
        // line 56 "model.ump"
        stateNotify("missedStart");
        __autotransition4398__();
        break;
      case notifyAdmin:
        // line 63 "model.ump"
        stateNotify("notifyAdmin");
        __autotransition4399__();
        break;
      case endOfEpisode:
        // line 70 "model.ump"
        stateNotify("endOfEpisode");
        startTimeoutendOfEpisodeTowaitStartHandler();
        break;
      case endReadGlucoseProtocol:
        // line 78 "model.ump"
        stateNotify("endReadGlucoseProtocol");
        break;
    }
  }

  private void startTimeoutwaitStartTowarnStartGlucoseHandler()
  {
    timeoutwaitStartTowarnStartGlucoseHandler = new TimedEventHandler(this,"timeoutwaitStartTowarnStartGlucose",startDeadline);
  }

  private void stopTimeoutwaitStartTowarnStartGlucoseHandler()
  {
    timeoutwaitStartTowarnStartGlucoseHandler.stop();
  }

  private void startTimeoutwarnStartGlucoseTomissedStartHandler()
  {
    timeoutwarnStartGlucoseTomissedStartHandler = new TimedEventHandler(this,"timeoutwarnStartGlucoseTomissedStart",startWarnDeadline);
  }

  private void stopTimeoutwarnStartGlucoseTomissedStartHandler()
  {
    timeoutwarnStartGlucoseTomissedStartHandler.stop();
  }

  private void startTimeoutendOfEpisodeTowaitStartHandler()
  {
    timeoutendOfEpisodeTowaitStartHandler = new TimedEventHandler(this,"timeoutendOfEpisodeTowaitStart",endOfEpisodeDeadline);
  }

  private void stopTimeoutendOfEpisodeTowaitStartHandler()
  {
    timeoutendOfEpisodeTowaitStartHandler.stop();
  }

  public static class TimedEventHandler extends TimerTask
  {
    private ReadGlucoseBase controller;
    private String timeoutMethodName;
    private double howLongInSeconds;
    private Timer timer;

    public TimedEventHandler(ReadGlucoseBase aController, String aTimeoutMethodName, double aHowLongInSeconds)
    {
      controller = aController;
      timeoutMethodName = aTimeoutMethodName;
      howLongInSeconds = aHowLongInSeconds;
      timer = new Timer();
      timer.schedule(this, (long)howLongInSeconds*1000);
    }

    public void stop()
    {
      timer.cancel();
    }

    public void run ()
    {
      if ("timeoutwaitStartTowarnStartGlucose".equals(timeoutMethodName))
      {
        boolean shouldRestart = !controller.timeoutwaitStartTowarnStartGlucose();
        if (shouldRestart)
        {
          controller.startTimeoutwaitStartTowarnStartGlucoseHandler();
        }
        return;
      }
      if ("timeoutwarnStartGlucoseTomissedStart".equals(timeoutMethodName))
      {
        boolean shouldRestart = !controller.timeoutwarnStartGlucoseTomissedStart();
        if (shouldRestart)
        {
          controller.startTimeoutwarnStartGlucoseTomissedStartHandler();
        }
        return;
      }
      if ("timeoutendOfEpisodeTowaitStart".equals(timeoutMethodName))
      {
        boolean shouldRestart = !controller.timeoutendOfEpisodeTowaitStart();
        if (shouldRestart)
        {
          controller.startTimeoutendOfEpisodeTowaitStartHandler();
        }
        return;
      }
    }
  }

  public void delete()
  {}

  // line 85 "model.ump"
  public boolean stateNotify(String node){
    return true;
  }


  public String toString()
  {
    return super.toString() + "["+
            "startDeadline" + ":" + getStartDeadline()+ "," +
            "startWarnDeadline" + ":" + getStartWarnDeadline()+ "," +
            "endOfEpisodeDeadline" + ":" + getEndOfEpisodeDeadline()+ "]";
  }
}