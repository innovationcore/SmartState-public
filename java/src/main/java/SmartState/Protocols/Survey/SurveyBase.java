package SmartState.Protocols.Survey;
//%% NEW FILE SurveyBase BEGINS HERE %%

/*PLEASE DO NOT EDIT THIS CODE*/
/*This code was generated using the UMPLE 1.35.0.7523.c616a4dce modeling language!*/


import java.util.*;

// line 2 "model.ump"
// line 62 "model.ump"
public class SurveyBase
{

  //------------------------
  // MEMBER VARIABLES
  //------------------------

  //SurveyBase Attributes
  private int deadlineNoon;
  private int deadline6pm;

  //SurveyBase State Machines
  public enum State { initial, noonSurvey, waitFor6pm, survey6pm, waitForNoon, endSurveyProtocol }
  private State state;

  //Helper Variables
  private TimedEventHandler timeoutwaitFor6pmTosurvey6pmHandler;
  private TimedEventHandler timeoutwaitForNoonTonoonSurveyHandler;

  //------------------------
  // CONSTRUCTOR
  //------------------------

  public SurveyBase()
  {
    deadlineNoon = 0;
    deadline6pm = 0;
    setState(State.initial);
  }

  //------------------------
  // INTERFACE
  //------------------------

  public boolean setDeadlineNoon(int aDeadlineNoon)
  {
    boolean wasSet = false;
    deadlineNoon = aDeadlineNoon;
    wasSet = true;
    return wasSet;
  }

  public boolean setDeadline6pm(int aDeadline6pm)
  {
    boolean wasSet = false;
    deadline6pm = aDeadline6pm;
    wasSet = true;
    return wasSet;
  }

  public int getDeadlineNoon()
  {
    return deadlineNoon;
  }

  public int getDeadline6pm()
  {
    return deadline6pm;
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

  public boolean isBeforeNoon()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.waitForNoon);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean isBefore6pm()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case initial:
        setState(State.waitFor6pm);
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
        setState(State.endSurveyProtocol);
        wasEventProcessed = true;
        break;
      case waitFor6pm:
        exitState();
        setState(State.endSurveyProtocol);
        wasEventProcessed = true;
        break;
      case waitForNoon:
        exitState();
        setState(State.endSurveyProtocol);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  private boolean __autotransition4449__()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case noonSurvey:
        setState(State.waitFor6pm);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean timeoutwaitFor6pmTosurvey6pm()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case waitFor6pm:
        exitState();
        setState(State.survey6pm);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  private boolean __autotransition4450__()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case survey6pm:
        setState(State.waitForNoon);
        wasEventProcessed = true;
        break;
      default:
        // Other states do respond to this event
    }

    return wasEventProcessed;
  }

  public boolean timeoutwaitForNoonTonoonSurvey()
  {
    boolean wasEventProcessed = false;

    State aState = state;
    switch (aState)
    {
      case waitForNoon:
        exitState();
        setState(State.noonSurvey);
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
      case waitFor6pm:
        stopTimeoutwaitFor6pmTosurvey6pmHandler();
        break;
      case waitForNoon:
        stopTimeoutwaitForNoonTonoonSurveyHandler();
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
        // line 8 "model.ump"
        stateNotify("initial");
        break;
      case noonSurvey:
        // line 18 "model.ump"
        stateNotify("noonSurvey");
        __autotransition4449__();
        break;
      case waitFor6pm:
        // line 25 "model.ump"
        stateNotify("waitFor6pm");
        startTimeoutwaitFor6pmTosurvey6pmHandler();
        break;
      case survey6pm:
        // line 35 "model.ump"
        stateNotify("survey6pm");
        __autotransition4450__();
        break;
      case waitForNoon:
        // line 41 "model.ump"
        stateNotify("waitForNoon");
        startTimeoutwaitForNoonTonoonSurveyHandler();
        break;
      case endSurveyProtocol:
        // line 50 "model.ump"
        stateNotify("endSurveyProtocol");
        break;
    }
  }

  private void startTimeoutwaitFor6pmTosurvey6pmHandler()
  {
    timeoutwaitFor6pmTosurvey6pmHandler = new TimedEventHandler(this,"timeoutwaitFor6pmTosurvey6pm",deadline6pm);
  }

  private void stopTimeoutwaitFor6pmTosurvey6pmHandler()
  {
    timeoutwaitFor6pmTosurvey6pmHandler.stop();
  }

  private void startTimeoutwaitForNoonTonoonSurveyHandler()
  {
    timeoutwaitForNoonTonoonSurveyHandler = new TimedEventHandler(this,"timeoutwaitForNoonTonoonSurvey",deadlineNoon);
  }

  private void stopTimeoutwaitForNoonTonoonSurveyHandler()
  {
    timeoutwaitForNoonTonoonSurveyHandler.stop();
  }

  public static class TimedEventHandler extends TimerTask
  {
    private SurveyBase controller;
    private String timeoutMethodName;
    private double howLongInSeconds;
    private Timer timer;

    public TimedEventHandler(SurveyBase aController, String aTimeoutMethodName, double aHowLongInSeconds)
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
      if ("timeoutwaitFor6pmTosurvey6pm".equals(timeoutMethodName))
      {
        boolean shouldRestart = !controller.timeoutwaitFor6pmTosurvey6pm();
        if (shouldRestart)
        {
          controller.startTimeoutwaitFor6pmTosurvey6pmHandler();
        }
        return;
      }
      if ("timeoutwaitForNoonTonoonSurvey".equals(timeoutMethodName))
      {
        boolean shouldRestart = !controller.timeoutwaitForNoonTonoonSurvey();
        if (shouldRestart)
        {
          controller.startTimeoutwaitForNoonTonoonSurveyHandler();
        }
        return;
      }
    }
  }

  public void delete()
  {}

  // line 57 "model.ump"
  public boolean stateNotify(String node){
    return true;
  }


  public String toString()
  {
    return super.toString() + "["+
            "deadlineNoon" + ":" + getDeadlineNoon()+ "," +
            "deadline6pm" + ":" + getDeadline6pm()+ "]";
  }
}