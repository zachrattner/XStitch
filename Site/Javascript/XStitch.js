/* The minimum width of a pattern, in stitches. */
var MIN_WIDTH  = 10;

/* The minimum height of a pattern, in stitches. */
var MIN_HEIGHT = 10;

/* The maximum width of a pattern, in stitches. */
var MAX_WIDTH  = 1000;

/* The maximum height of a pattern, in stitches. */
var MAX_HEIGHT = 1000;

/* The amount of time to spend at each stage in the pattern generation process,
   in ms. */
var DEFAULT_PATTERN_PERIOD = 2800;

/* Preload the loading animation. */
var Loading = new Image(16, 16);
Image.src = "Images/Loading.gif";

/* The steps that are displayed when a preview is being generated. */
var PREVIEW_GENERATION_STEPS = 
[
    "Resizing image",
    "Optimizing color palette",
    "Finding best thread colors",
    "Generating preview image",
    "Building thread table",
    "Finishing up"
];

/* The steps that are displayed when a pattern is being generated. */
var PATTERN_GENERATION_STEPS = 
[
    "Analyzing image",
    "Determining page count",
    "Devising symbols",
    "Drawing pattern grid",
    "Building thread table",
    "Finishing up"
];

/* Scroll the browser window to a specific DOM element. */
function scrollTo($Element)
{
    $("html, body").animate(
    {
        scrollTop: $Element.offset().top
    }, 
    {
        duration: "slow",
        easing:   "swing"
    });
}

/* Handle external links an a valid HTML5 way -- write invalid HTML with
   Javascript so the validator can't see it */
function parseLinks()
{
	$("a").each(function()
	{
		if ($(this).attr("href").indexOf("://") >= 0)
		{
			$(this).attr("target", "_blank");
		}
	});
}

$(document).ready(function()
{
    /* Initialize the step counters with their container elements. */
    var PreviewCounter = new StepCounter($("div#PreviewStepWrapper"), PREVIEW_GENERATION_STEPS);
    var PatternCounter = new StepCounter($("div#PatternStepWrapper"), PATTERN_GENERATION_STEPS);

    /* Initialize the color slider. */
    $("div#Colors").slider(
    {
        min:   1,
        max:   100,
        value: 50
    });
    
    /* The button that generates previews. */
    $("div#PreviewButton").click(function()
    {
        /* Prevent concurrent requests. */
        if ($(this).hasClass("Disabled"))
        {
            return false;
        }
        
        /* Lock the UI */
        var $This = $(this).addClass("Disabled");
        
        /* Extract parameters from the page. */
        var WebLink = $("input#ImagePath").val();
        var Width   = parseInt($("input#Width").val());
        var Height  = parseInt($("input#Height").val());
        var Colors  = parseInt($("div#Colors").slider("option", "value"));
        
        /* Hide future steps, in case they were displayed in a previous run. */
        $("div#PreviewStep, div#GenerateStep").slideUp();
        
        /* Hide the old status, in case it was set in a previous run. */
        $("div#Status").slideUp();
        
        /* Verify the input. */
        var Errors = [];
        
        if (isNaN(Width))
        {
            Errors.push("Please enter a width.");
        }
        else if (Width < MIN_WIDTH)
        {
            Errors.push("Please enter a width of at least " + String(MIN_WIDTH) + ".");
        }
        else if (Width > MAX_WIDTH)
        {
            Errors.push("Images are limited to " + String(MAX_WIDTH) + " stitches wide. Please use a narrower size.");
        }
        
        if (isNaN(Height))
        {
            Errors.push("Please enter a height.");
        }
        else if (Height < MIN_HEIGHT)
        {
            Errors.push("Please enter a height of at least " +  String(MIN_HEIGHT) + ".");
        }
        else if (Height > MAX_HEIGHT)
        {
            Errors.push("Images are limited to " + String(MAX_HEIGHT) + " stitches wide. Please use a shorter size.");
        }
        
        /* Exit early if the input failed verification. */
        if (Errors.length)
        {
            $("div#Status").addClass("Error").html(Errors.join("<br />")).slideDown();
            scrollTo($("div#Status"));
            $This.removeClass("Disabled");
            return;
        }
        
        /* Update the UI to reflect the request in progress. */
        $This.html("<img src='Images/Loading.gif' />");
        PreviewCounter.setPeriod(Math.ceil((0.50 * Height * Width) / PREVIEW_GENERATION_STEPS.length));
        PreviewCounter.start();
        
        /* Submit the request. */
        $.ajax
        ({
            type:     "post",
            dataType: "json",
            url:      "AJAX/GeneratePreview.ajax.php",
            data: 
            {
                WebLink: WebLink,
                Width:   Width,
                Height:  Height,
                Colors:  Colors
            },
            success: function(ResponseData)
            {
                /* Update the UI to reflect the request's completion. */
                $This.text("Make Preview").removeClass("Disabled");
                PreviewCounter.clear();
                
                if (ResponseData.Success)
                {
                    /* Populate the next step with the preview information. */
                    $("div#Status").removeClass("Error").html("").slideUp();
                    $("div#Preview").html(ResponseData.Preview);
                    $("div#Table").html(ResponseData.Table);
                    $("div#PreviewStep, div#GenerateStep").slideDown();
                    scrollTo($("div#PreviewStep"));
                }
                else
                {
                    /* Explain what went wrong. */
                    scrollTo($("div#Status"));
                    $("div#Status").addClass("Error").html(ResponseData.Message).slideDown();
                }
            }
        });
    });
    
    /* The button that generates patterns. */
    $("div#PatternButton").click(function()
    {
        /* Prevent concurrent requests. */
        if ($(this).hasClass("Disabled"))
        {
            return false;
        }
    
        /* Lock the UI */
        var $This   = $(this).addClass("Disabled");
        
        /* Extract parameters from the page. */
        var WebLink = $("input#ImagePath").val();
        var Width   = parseInt($("input#Width").val());
        var Height  = parseInt($("input#Height").val());
        var Colors  = parseInt($("div#Colors").slider("option", "value"));
        
        /* Hide the old status, in case it was set in a previous run. */
        $("div#Status").slideUp();
        
        /* Verify the input. */
        var Errors = [];
        
        if (isNaN(Width))
        {
            Errors.push("Please enter a width.");
        }
        else if (Width < MIN_WIDTH)
        {
            Errors.push("Please enter a width of at least " + String(MIN_WIDTH) + ".");
        }
        else if (Width > MAX_WIDTH)
        {
            Errors.push("Images are limited to " + String(MAX_WIDTH) + " stitches wide. Please use a narrower size.");
        }
        
        if (isNaN(Height))
        {
            Errors.push("Please enter a height.");
        }
        else if (Height < MIN_HEIGHT)
        {
            Errors.push("Please enter a height of at least " +  String(MIN_HEIGHT) + ".");
        }
        else if (Height > MAX_HEIGHT)
        {
            Errors.push("Images are limited to " + String(MAX_HEIGHT) + " stitches wide. Please use a shorter size.");
        }
        
        /* Exit early if the input failed verification. */
        if (Errors.length)
        {
            $("div#Status").addClass("Error").html(Errors.join("<br />")).slideDown();
            scrollTo($("div#Status"));
            $This.removeClass("Disabled");
            return;
        }
    
        /* Update the UI to reflect the request in progress. */
        $This.html("<img src='Images/Loading.gif' />");
        $("div#ChoosePictureStep, div#SetLimitsStep, div#MakePreviewStep").slideUp();
        PatternCounter.setPeriod(DEFAULT_PATTERN_PERIOD);
        PatternCounter.start();
        
        /* Submit the request. */
        $.ajax
        ({
            type:     "post",
            dataType: "json",
            url:      "AJAX/GeneratePattern.ajax.php",
            data: 
            {
                WebLink: WebLink,
                Width:   Width,
                Height:  Height,
                Colors:  Colors
            },
            success: function(ResponseData)
            {
                /* Update the UI to reflect the request's completion. */
                $This.text("Make Pattern").removeClass("Disabled");
                PatternCounter.clear();
                
                if (ResponseData.Success)
                {
                    /* Populate the next step with the preview information. */
                    $("div#Status").removeClass("Error").html(ResponseData.Message).slideDown();
                    parseLinks();
                }
                else
                {
                    /* Explain what went wrong. */
                    $("div#Status").addClass("Error").html(ResponseData.Message).slideDown();
                }
                
                /* Scroll back to the status field, since it was populated in both cases. */
                scrollTo($("div#Status"));
            }
        });
    });
});
