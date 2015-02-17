/* The StepCounter class abstracts the functionality for displaying sequences
   of steps in the UI during long AJAX calls. */
function StepCounter($Container, Steps)
{
    /* Store the object instance in a member variable so it can be accessed via
       setInterval. */
    var _this = this;
    
    /* The DOM entity to store the current step inside. */
    _this.$Container = $Container;
    
    /* The current index of the Steps array. */
    _this.CurrentStep = 0;
    
    /* The time to spend at each step, in ms. */
    _this.Period = 0;
    
    /* The array of strings containing each step of the process. */
    _this.Steps = Steps;
    
    /* The timer instance that manages incrementing the step counter and
       updating the UI. */
    _this.Timer = null;
    
    /* Set the time to spend at each step, in ms. */
    this.setPeriod = function(Period)
    {
        _this.Period = parseInt(Period);
    }
    
    /* Start the timer and set the UI to contain the first step. */
    this.start = function()
    {
        _this.CurrentStep = 0;
        
        if (_this.Period)
        {
            _this.incrementStep();
            _this.Timer = setInterval(_this.incrementStep, _this.Period);
        }
        else
        {
            _this.Timer = null;
        }
    }
    
    /* Update to the next step if there is one. Otherwise, clear the timer and
       display the last step. */
    this.incrementStep = function()
    {            
        if (_this.CurrentStep < _this.Steps.length)
        {
            _this.$Container.html
            (
                _this.Steps[_this.CurrentStep] + 
                "..."
            );
            
            _this.CurrentStep++;
        }
        else
        {
            _this.cancel();
        }
    }
    
    /* Stop the timer and leave the UI in its present state. */
    this.cancel = function()
    {
        if (_this.Timer != null)
        {
            clearInterval(_this.Timer);
            _this.Timer = null;
        }
    }
    
    /* Stop the timer and empty the current step from the UI. */
    this.clear = function()
    {
        this.cancel();
        _this.$Container.html("");
    }
}
